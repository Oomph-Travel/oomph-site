<?php
/**
 * Enrichment Sync — admin screen that PULLS payloads from GitHub.
 *
 * Group Cruises → Enrichment Sync. Lists `enrichment/*.json` from the public
 * repo (no credentials — the repo is public and SiteGround's outbound requests
 * to GitHub are ordinary web traffic; this exists because inbound GitHub
 * Actions runners are blocked by SiteGround). Each file maps to a sailing by
 * slug: `enrich-<slug>.json` → oomph_cruise post with that slug.
 *
 * Flow mirrors Import Sailings: fetch list → per-file status + dry-run
 * preview → "Apply new payloads" writes via Enrich_Engine (same guarantees:
 * never publishes, never overwrites human copy). Applied files are logged by
 * GitHub blob SHA, so a re-edited file shows as new again while an unchanged
 * one stays quiet.
 *
 * @package OomphTravel\Core
 */

declare( strict_types=1 );

namespace OomphTravel\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Enrich_Sync {

	private const REPO       = 'Oomph-Travel/oomph-site';
	private const BRANCH     = 'main';
	private const DIR        = 'enrichment';
	private const OPTION     = 'oomph_enrich_applied';   // filename => [sha, time, post_id]
	private const TRANSIENT  = 'oomph_enrich_listing';
	private const CACHE_TTL  = 5 * MINUTE_IN_SECONDS;
	private const MAX_BYTES  = 262144;                   // 256 KB per payload — plenty, and a guard.

	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
	}

	public static function menu(): void {
		add_submenu_page(
			'edit.php?post_type=' . CPT_Cruise::POST_TYPE,
			__( 'Enrichment Sync', 'oomph-travel-core' ),
			__( 'Enrichment Sync', 'oomph-travel-core' ),
			'manage_options',
			'oomph-enrich-sync',
			array( __CLASS__, 'render' )
		);
	}

	/** GitHub contents listing for the enrichment dir. */
	private static function fetch_listing( bool $fresh = false ): array {
		if ( ! $fresh ) {
			$cached = get_transient( self::TRANSIENT );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		$url      = sprintf( 'https://api.github.com/repos/%s/contents/%s?ref=%s', self::REPO, self::DIR, self::BRANCH );
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
				'headers' => array(
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => 'oomph-travel-core',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array( 'error' => $response->get_error_message() );
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 404 === $code ) {
			return array( 'error' => 'No enrichment/ folder found on ' . self::BRANCH . ' yet.' );
		}
		if ( 200 !== $code ) {
			return array( 'error' => 'GitHub answered HTTP ' . $code . ' — try again in a few minutes.' );
		}

		$items = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $items ) ) {
			return array( 'error' => 'Unexpected response from GitHub.' );
		}

		$files = array();
		foreach ( $items as $item ) {
			$name = (string) ( $item['name'] ?? '' );
			if ( 'file' !== ( $item['type'] ?? '' ) || ! preg_match( '/^enrich-[a-z0-9-]+\.json$/', $name ) ) {
				continue;
			}
			if ( (int) ( $item['size'] ?? 0 ) > self::MAX_BYTES ) {
				continue;
			}
			$files[] = array(
				'name'         => $name,
				'sha'          => (string) ( $item['sha'] ?? '' ),
				'download_url' => (string) ( $item['download_url'] ?? '' ),
			);
		}

		$listing = array( 'files' => $files );
		set_transient( self::TRANSIENT, $listing, self::CACHE_TTL );
		return $listing;
	}

	/** Download and decode one payload file. Returns array payload or ['error' => …]. */
	private static function fetch_payload( string $download_url ): array {
		if ( 0 !== strpos( $download_url, 'https://raw.githubusercontent.com/' . self::REPO . '/' ) ) {
			return array( 'error' => 'Refused: unexpected download host.' );
		}
		$response = wp_remote_get( $download_url, array( 'timeout' => 15, 'headers' => array( 'User-Agent' => 'oomph-travel-core' ) ) );
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return array( 'error' => 'Could not download the payload.' );
		}
		$payload = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $payload ) ) {
			return array( 'error' => 'Payload is not valid JSON.' );
		}
		return array( 'payload' => $payload );
	}

	/** filename "enrich-<slug>.json" → slug. */
	private static function slug_from_name( string $name ): string {
		return (string) preg_replace( '/^enrich-|\.json$/', '', $name );
	}

	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'oomph-travel-core' ) );
		}

		$applied = get_option( self::OPTION, array() );
		$results = array();

		// --- Apply (POST) --------------------------------------------------
		if ( isset( $_POST['oomph_enrich_apply'] ) ) {
			check_admin_referer( 'oomph_enrich_sync' );
			$listing = self::fetch_listing( true );
			foreach ( ( $listing['files'] ?? array() ) as $file ) {
				$log = $applied[ $file['name'] ] ?? null;
				if ( is_array( $log ) && ( $log['sha'] ?? '' ) === $file['sha'] ) {
					continue; // Unchanged since last apply.
				}
				$slug = self::slug_from_name( $file['name'] );
				$post = Enrich_Engine::find_by_slug( $slug );
				if ( ! $post ) {
					$results[] = array( 'file' => $file['name'], 'ok' => false, 'msg' => 'No sailing with slug "' . $slug . '" — skipped.' );
					continue;
				}
				$fetched = self::fetch_payload( $file['download_url'] );
				if ( isset( $fetched['error'] ) ) {
					$results[] = array( 'file' => $file['name'], 'ok' => false, 'msg' => $fetched['error'] );
					continue;
				}
				$res = Enrich_Engine::apply( $post->ID, $fetched['payload'], false, false );
				if ( null !== $res['error'] ) {
					$results[] = array( 'file' => $file['name'], 'ok' => false, 'msg' => $res['error'] );
					continue;
				}
				$applied[ $file['name'] ] = array( 'sha' => $file['sha'], 'time' => time(), 'post_id' => $post->ID );
				$msg = $res['changes'] ? 'Wrote ' . implode( ', ', $res['changes'] ) : 'Nothing to write';
				if ( $res['warnings'] ) {
					$msg .= ' · ' . implode( ' ', $res['warnings'] );
				}
				$results[] = array( 'file' => $file['name'], 'ok' => true, 'msg' => $msg . ' → "' . $post->post_title . '" (' . $post->post_status . ')' );
			}
			update_option( self::OPTION, $applied, false );
			delete_transient( self::TRANSIENT );
		}

		$fresh   = isset( $_GET['refresh'] ) || ! empty( $results );
		$listing = self::fetch_listing( $fresh );

		echo '<div class="wrap"><h1>' . esc_html__( 'Enrichment Sync', 'oomph-travel-core' ) . '</h1>';
		echo '<p>' . esc_html__( 'Pulls prepared itinerary payloads from the enrichment/ folder of the GitHub repo and applies them to matching sailings. Drafts stay drafts; written blurbs are never overwritten.', 'oomph-travel-core' ) . '</p>';

		foreach ( $results as $r ) {
			printf(
				'<div class="notice notice-%1$s"><p><strong>%2$s</strong> — %3$s</p></div>',
				$r['ok'] ? 'success' : 'warning',
				esc_html( $r['file'] ),
				esc_html( $r['msg'] )
			);
		}

		if ( isset( $listing['error'] ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html( $listing['error'] ) . '</p></div></div>';
			return;
		}

		$files   = $listing['files'];
		$pending = 0;

		echo '<table class="widefat striped" style="max-width:900px;margin-top:12px;"><thead><tr>';
		echo '<th>' . esc_html__( 'Payload file', 'oomph-travel-core' ) . '</th><th>' . esc_html__( 'Sailing', 'oomph-travel-core' ) . '</th><th>' . esc_html__( 'Status', 'oomph-travel-core' ) . '</th>';
		echo '</tr></thead><tbody>';

		if ( ! $files ) {
			echo '<tr><td colspan="3">' . esc_html__( 'No payload files found in enrichment/ yet.', 'oomph-travel-core' ) . '</td></tr>';
		}

		foreach ( $files as $file ) {
			$slug = self::slug_from_name( $file['name'] );
			$post = Enrich_Engine::find_by_slug( $slug );
			$log  = $applied[ $file['name'] ] ?? null;

			if ( is_array( $log ) && ( $log['sha'] ?? '' ) === $file['sha'] ) {
				$status = esc_html__( 'Applied', 'oomph-travel-core' ) . ' ✓ · ' . esc_html( date_i18n( 'M j, H:i', (int) $log['time'] ) );
			} elseif ( ! $post ) {
				$status = '<span style="color:#9A2A26;">' . esc_html__( 'No sailing with this slug', 'oomph-travel-core' ) . '</span>';
			} else {
				$status = '<strong>' . esc_html( is_array( $log ) ? __( 'Updated — will re-apply', 'oomph-travel-core' ) : __( 'New — will apply', 'oomph-travel-core' ) ) . '</strong>';
				$pending++;
			}

			printf(
				'<tr><td><code>%1$s</code></td><td>%2$s</td><td>%3$s</td></tr>',
				esc_html( $file['name'] ),
				$post ? '<a href="' . esc_url( (string) get_edit_post_link( $post->ID ) ) . '">' . esc_html( $post->post_title ) . '</a> <em>(' . esc_html( $post->post_status ) . ')</em>' : '—',
				wp_kses_post( $status )
			);
		}
		echo '</tbody></table>';

		echo '<form method="post" style="margin-top:16px;">';
		wp_nonce_field( 'oomph_enrich_sync' );
		printf(
			'<button type="submit" name="oomph_enrich_apply" value="1" class="button button-primary" %s>%s</button> ',
			$pending ? '' : 'disabled',
			esc_html( $pending ? sprintf( _n( 'Apply %d new payload', 'Apply %d new payloads', $pending, 'oomph-travel-core' ), $pending ) : __( 'Nothing new to apply', 'oomph-travel-core' ) )
		);
		echo '<a class="button" href="' . esc_url( add_query_arg( 'refresh', '1' ) ) . '">' . esc_html__( 'Refresh from GitHub', 'oomph-travel-core' ) . '</a>';
		echo '</form>';

		echo '<p style="margin-top:16px;color:#6B675B;">' . esc_html__( 'After applying: open each sailing, read the itinerary, publish when happy. Departed sailings are retired by the monthly import as usual.', 'oomph-travel-core' ) . '</p></div>';
	}
}
