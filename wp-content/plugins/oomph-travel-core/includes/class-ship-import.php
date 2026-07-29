<?php
/**
 * wp-admin screen: Ships -> Import Ships.
 *
 * Bulk-loads Ship Library records from a "ship pack": a .zip holding the
 * ship-library.xlsx workbook plus one folder of photos per ship (folder name
 * = exact ship name). Preview shows what would change; Import creates or
 * updates the Ship posts, sideloads the photos into the media library
 * (deduped by content hash, so re-imports never duplicate media), sets alt
 * text from the workbook's Photos tab (or derives it from the filename),
 * and fills the ACF fields: line, credit, quick facts, gallery.
 *
 * A bare .xlsx (no photos) is also accepted for text-only updates — in that
 * mode only rows whose Status is "Approved" or "Imported" are written, so
 * half-drafted rows never leak onto the site.
 *
 * Guarantees, matching the site's other importers:
 * - Preview never changes anything.
 * - Idempotent: re-importing an unchanged pack changes nothing.
 * - Non-destructive: empty workbook cells never blank out existing content;
 *   only oomph_ship posts are ever written.
 *
 * @package OomphTravel\Core
 */

declare( strict_types=1 );

namespace OomphTravel\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Ship_Import {

	private const CAP       = 'edit_others_posts';
	private const SLUG      = 'oomph-import-ships';
	private const NONCE     = 'oomph_ship_import';
	private const MAX       = 104857600; // 100 MB zip. Server limits may be lower — split the batch if the upload fails.
	private const SHEET     = 'Ships';
	private const SHEET_PH  = 'Photos';
	private const HASH_META = '_oomph_ship_img_hash';
	private const IMG_EXT   = array( 'jpg', 'jpeg', 'png', 'webp' );
	private const MAX_PHOTOS = 12; // Matches the ACF gallery cap.

	/** Fact columns in display-priority order: normalized header => default row label. */
	private const FACTS = array(
		'guests'           => 'Guests',
		'crew'             => 'Crew',
		'crewtoguest'      => 'Crew-to-guest',
		'suitesstaterooms' => 'Staterooms', // becomes "Suites" when the value says all-suite.
		'shipclass'        => 'Class',
		'launched'         => 'Launched',
		'refurbished'      => 'Refurbished',
		'tonnage'          => 'Tonnage',
		'length'           => 'Length',
		'decks'            => 'Decks',
		'spaceratio'       => 'Space ratio',
		'godmother'        => 'Godmother',
	);
	private const MAX_FACT_ROWS = 6; // ACF repeater max.

	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
	}

	public static function menu(): void {
		add_submenu_page(
			'edit.php?post_type=' . CPT_Ship::POST_TYPE,
			'Import Ships',
			'Import Ships',
			self::CAP,
			self::SLUG,
			array( __CLASS__, 'render' )
		);
	}

	// --- screen -------------------------------------------------------------

	public static function render(): void {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( 'You do not have permission to import ships.' );
		}
		self::cleanup_old_temp();

		echo '<div class="wrap"><h1>Import Ships</h1>';
		echo '<p style="max-width:70ch">Upload a <strong>ship pack</strong>: a .zip of the <code>ship-library.xlsx</code> workbook plus one folder of photos per ship (folder name = exact ship name, numbered files set the gallery order). Click <strong>Preview</strong> first, then <strong>Import</strong>. Every current and future sailing of an imported ship picks up its &ldquo;Life on board&rdquo; section automatically. A bare <code>.xlsx</code> works too for text-only updates (Approved/Imported rows only).</p>';

		$step = isset( $_POST['oomph_step'] ) ? sanitize_key( wp_unslash( $_POST['oomph_step'] ) ) : '';

		if ( 'preview' === $step || 'import' === $step ) {
			check_admin_referer( self::NONCE );
			$dry = ( 'preview' === $step );

			$src = $dry
				? self::handle_upload()
				: self::resolve_token( isset( $_POST['oomph_token'] ) ? sanitize_file_name( wp_unslash( $_POST['oomph_token'] ) ) : '' );

			if ( is_wp_error( $src ) ) {
				printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $src->get_error_message() ) );
			} else {
				$res = self::run( $src['path'], $dry );
				self::render_results( $res, $dry, $src['token'] );
				if ( ! $dry ) {
					wp_delete_file( $src['path'] );
				}
			}
		}

		self::render_form();
		echo '</div>';
	}

	private static function render_form(): void {
		?>
		<hr>
		<form method="post" enctype="multipart/form-data" style="max-width:70ch">
			<?php wp_nonce_field( self::NONCE ); ?>
			<input type="hidden" name="oomph_step" value="preview">
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="ship_pack">Ship pack</label></th>
					<td>
						<input type="file" id="ship_pack" name="ship_pack" accept=".zip,.xlsx" required>
						<p class="description">A .zip (workbook + photo folders, 3&ndash;6 ships per batch keeps it quick) or a bare .xlsx for text-only updates. Up to 100&nbsp;MB.</p>
					</td>
				</tr>
			</table>
			<?php submit_button( 'Preview', 'secondary', 'submit', false ); ?>
			<p class="description" style="margin-top:8px">Preview never changes anything &mdash; it just shows what an import would do.</p>
		</form>
		<?php
	}

	// --- upload / temp handling (mirrors Admin_Import) ----------------------

	/** @return array{path:string,token:string}|\WP_Error */
	private static function handle_upload() {
		if ( empty( $_FILES['ship_pack'] ) || ! isset( $_FILES['ship_pack']['error'] ) ) {
			return new \WP_Error( 'no_file', 'No file was uploaded.' );
		}
		$file = $_FILES['ship_pack']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- validated below.
		if ( UPLOAD_ERR_OK !== (int) $file['error'] ) {
			return new \WP_Error( 'upload_err', 'The file did not upload correctly. If it is large, the server may have a lower upload limit — split the batch and try again.' );
		}
		if ( (int) $file['size'] > self::MAX || (int) $file['size'] <= 0 ) {
			return new \WP_Error( 'too_big', 'That file is too large (or empty). Split the batch into fewer ships.' );
		}
		if ( ! is_uploaded_file( $file['tmp_name'] ) ) {
			return new \WP_Error( 'bad_upload', 'Upload could not be verified.' );
		}

		$ext = strtolower( pathinfo( sanitize_file_name( (string) $file['name'] ), PATHINFO_EXTENSION ) );
		if ( ! in_array( $ext, array( 'zip', 'xlsx' ), true ) ) {
			return new \WP_Error( 'bad_ext', 'Please upload a .zip ship pack or a .xlsx workbook.' );
		}

		$dir = self::temp_dir();
		if ( is_wp_error( $dir ) ) {
			return $dir;
		}
		$stamp = (string) getmypid() . '-' . substr( md5( uniqid( (string) mt_rand(), true ) ), 0, 12 );
		$path  = $dir . '/ship-' . $stamp . '.' . $ext;
		if ( ! move_uploaded_file( $file['tmp_name'], $path ) ) {
			return new \WP_Error( 'move_fail', 'Could not save the uploaded file.' );
		}
		return array( 'path' => $path, 'token' => basename( $path ) );
	}

	/** @return array{path:string,token:string}|\WP_Error */
	private static function resolve_token( string $token ) {
		$token = basename( $token );
		if ( ! preg_match( '/^ship-[0-9a-f\-]+\.(zip|xlsx)$/', $token ) ) {
			return new \WP_Error( 'bad_token', 'The upload session expired. Please upload the file again.' );
		}
		$dir = self::temp_dir();
		if ( is_wp_error( $dir ) ) {
			return $dir;
		}
		$path = $dir . '/' . $token;
		if ( ! is_file( $path ) ) {
			return new \WP_Error( 'gone', 'The uploaded file is no longer available. Please upload it again.' );
		}
		return array( 'path' => $path, 'token' => $token );
	}

	/** Same protected temp dir the sailings import uses. */
	private static function temp_dir() {
		$up  = wp_upload_dir();
		$dir = trailingslashit( $up['basedir'] ) . 'oomph-import-tmp';
		if ( ! wp_mkdir_p( $dir ) ) {
			return new \WP_Error( 'no_dir', 'Could not create a temporary folder for the upload.' );
		}
		if ( ! file_exists( $dir . '/.htaccess' ) ) {
			file_put_contents( $dir . '/.htaccess', "Deny from all\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
		if ( ! file_exists( $dir . '/index.php' ) ) {
			file_put_contents( $dir . '/index.php', "<?php // Silence.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
		return $dir;
	}

	private static function cleanup_old_temp(): void {
		$up  = wp_upload_dir();
		$dir = trailingslashit( $up['basedir'] ) . 'oomph-import-tmp';
		if ( ! is_dir( $dir ) ) {
			return;
		}
		foreach ( glob( $dir . '/ship-*.{zip,xlsx,csv}', GLOB_BRACE ) ?: array() as $f ) {
			if ( is_file( $f ) && ( time() - (int) filemtime( $f ) ) > HOUR_IN_SECONDS ) {
				wp_delete_file( $f );
			}
		}
	}

	// --- engine -------------------------------------------------------------

	/**
	 * @return array{error:?string,results:array<int,array<string,mixed>>,warnings:array<int,string>}
	 */
	private static function run( string $path, bool $dry ): array {
		@set_time_limit( 300 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors

		$is_zip = 'zip' === strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
		$zip    = null;

		if ( $is_zip ) {
			if ( ! class_exists( 'ZipArchive' ) ) {
				return self::err( 'The server is missing PHP ZipArchive.' );
			}
			$zip = new \ZipArchive();
			if ( true !== $zip->open( $path ) ) {
				return self::err( 'That file could not be opened as a zip.' );
			}
			$xlsx = self::extract_workbook( $zip );
			if ( is_wp_error( $xlsx ) ) {
				$zip->close();
				return self::err( $xlsx->get_error_message() );
			}
		} else {
			$xlsx = $path;
		}

		$book = self::parse_workbook( $xlsx );
		if ( $is_zip ) {
			wp_delete_file( $xlsx ); // Extracted temp copy.
		}
		if ( is_wp_error( $book ) ) {
			if ( $zip ) {
				$zip->close();
			}
			return self::err( $book->get_error_message() );
		}

		$warnings = $book['warnings'];
		$results  = array();

		if ( $is_zip ) {
			$folders = self::scan_zip( $zip );
			if ( ! $folders ) {
				$zip->close();
				return self::err( 'No ship photo folders were found in the zip. Each ship needs a folder named exactly like the ship, with its photos inside.' );
			}
			foreach ( $folders as $ship => $entries ) {
				$row = $book['ships'][ self::norm( $ship ) ] ?? null;
				if ( ! $row ) {
					$results[] = array(
						'action' => 'skipped',
						'ship'   => $ship,
						'photos' => count( $entries ),
						'facts'  => 0,
						'notes'  => 'No matching row in the workbook — check the folder name against the Ships tab.',
					);
					continue;
				}
				$results[] = self::import_ship( $row, $entries, $zip, $dry, $book['photos'] );
			}
		} else {
			foreach ( $book['ships'] as $row ) {
				$status = strtolower( (string) ( $row['status'] ?? '' ) );
				if ( ! in_array( $status, array( 'approved', 'imported' ), true ) ) {
					continue; // Text-only mode writes signed-off rows only.
				}
				$results[] = self::import_ship( $row, array(), null, $dry, $book['photos'] );
			}
			if ( ! $results ) {
				$warnings[] = 'No rows with Status "Approved" or "Imported" were found — nothing to update in text-only mode.';
			}
		}

		if ( $zip ) {
			$zip->close();
		}

		return array( 'error' => null, 'results' => $results, 'warnings' => $warnings );
	}

	/** @return array{error:string,results:array,warnings:array} */
	private static function err( string $msg ): array {
		return array( 'error' => $msg, 'results' => array(), 'warnings' => array() );
	}

	/** Pull the first .xlsx out of the zip into the temp dir. @return string|\WP_Error */
	private static function extract_workbook( \ZipArchive $zip ) {
		$best = null;
		for ( $i = 0; $i < $zip->numFiles; $i++ ) {
			$name = (string) $zip->getNameIndex( $i );
			$base = basename( $name );
			if ( ! preg_match( '/\.xlsx$/i', $base ) || str_starts_with( $name, '__MACOSX' ) || str_starts_with( $base, '.' ) ) {
				continue;
			}
			if ( 'ship-library.xlsx' === strtolower( $base ) ) {
				$best = $i;
				break;
			}
			$best = $best ?? $i;
		}
		if ( null === $best ) {
			return new \WP_Error( 'no_book', 'No .xlsx workbook found in the zip — include ship-library.xlsx.' );
		}
		$dir = self::temp_dir();
		if ( is_wp_error( $dir ) ) {
			return $dir;
		}
		$out  = $dir . '/ship-book-' . substr( md5( uniqid( (string) mt_rand(), true ) ), 0, 12 ) . '.xlsx';
		$data = $zip->getFromIndex( $best );
		if ( false === $data || false === file_put_contents( $out, $data ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions
			return new \WP_Error( 'book_fail', 'Could not read the workbook from the zip.' );
		}
		return $out;
	}

	/**
	 * Read the Ships (+ optional Photos) sheets.
	 *
	 * @return array{ships:array<string,array<string,string>>,photos:array<string,array<string,array{alt:string,order:int}>>,warnings:array<int,string>}|\WP_Error
	 */
	private static function parse_workbook( string $xlsx ) {
		$dir = self::temp_dir();
		if ( is_wp_error( $dir ) ) {
			return $dir;
		}
		$stamp = substr( md5( $xlsx . microtime() ), 0, 12 );

		$csv = XLSX::to_csv( $xlsx, self::SHEET, $dir . '/ship-rows-' . $stamp . '.csv' );
		if ( is_wp_error( $csv ) ) {
			return $csv;
		}
		$rows = self::read_csv( $csv );
		wp_delete_file( $csv );
		if ( count( $rows ) < 2 ) {
			return new \WP_Error( 'empty', 'The Ships sheet has no data rows.' );
		}

		$head = array_map( array( __CLASS__, 'norm' ), array_shift( $rows ) );
		$col  = array();
		foreach ( $head as $i => $h ) {
			if ( '' === $h ) {
				continue;
			}
			if ( str_starts_with( $h, 'intro' ) ) {
				$h = 'intro';
			}
			if ( str_starts_with( $h, 'notes' ) ) {
				$h = 'notes';
			}
			$col[ $h ] = $col[ $h ] ?? $i;
		}
		$warnings = array();
		foreach ( array( 'ship', 'cruiseline' ) as $need ) {
			if ( ! isset( $col[ $need ] ) ) {
				return new \WP_Error( 'no_col', 'The Ships sheet is missing a required column: ' . $need . '. Is this the ship-library workbook?' );
			}
		}

		$ships = array();
		foreach ( $rows as $r ) {
			$get  = static fn( string $k ): string => trim( (string) ( $r[ $col[ $k ] ?? -1 ] ?? '' ) );
			$name = $get( 'ship' );
			if ( '' === $name ) {
				continue;
			}
			$row = array( 'ship' => $name );
			foreach ( array_merge( array( 'cruiseline', 'status', 'intro', 'photocredit', 'custom1label', 'custom1value', 'custom2label', 'custom2value' ), array_keys( self::FACTS ) ) as $k ) {
				$row[ $k ] = $get( $k );
			}
			$ships[ self::norm( $name ) ] = $row;
		}

		// Optional Photos sheet: ship => filename => alt/order.
		$photos = array();
		$pcsv   = XLSX::to_csv( $xlsx, self::SHEET_PH, $dir . '/ship-photos-' . $stamp . '.csv' );
		if ( ! is_wp_error( $pcsv ) ) {
			$prow = self::read_csv( $pcsv );
			wp_delete_file( $pcsv );
			if ( count( $prow ) > 1 ) {
				$phead = array_map( array( __CLASS__, 'norm' ), array_shift( $prow ) );
				$pcol  = array();
				foreach ( $phead as $i => $h ) {
					if ( str_starts_with( $h, 'alttext' ) ) {
						$h = 'alt';
					}
					$pcol[ $h ] = $pcol[ $h ] ?? $i;
				}
				if ( isset( $pcol['ship'], $pcol['filename'] ) ) {
					foreach ( $prow as $r ) {
						$ship = self::norm( trim( (string) ( $r[ $pcol['ship'] ] ?? '' ) ) );
						$file = strtolower( trim( (string) ( $r[ $pcol['filename'] ] ?? '' ) ) );
						if ( '' === $ship || '' === $file ) {
							continue;
						}
						$photos[ $ship ][ $file ] = array(
							'alt'   => trim( (string) ( $r[ $pcol['alt'] ?? -1 ] ?? '' ) ),
							'order' => (int) ( $r[ $pcol['order'] ?? -1 ] ?? 0 ),
						);
					}
				}
			}
		}

		return array( 'ships' => $ships, 'photos' => $photos, 'warnings' => $warnings );
	}

	/** @return array<int,array<int,string>> */
	private static function read_csv( string $path ): array {
		$rows = array();
		$fh   = fopen( $path, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( false === $fh ) {
			return $rows;
		}
		while ( ( $line = fgetcsv( $fh ) ) !== false ) {
			$rows[] = array_map( 'strval', $line );
		}
		fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		return $rows;
	}

	/**
	 * Map zip image entries to ships by their parent folder's basename.
	 * Ordered by entry name (numbered prefixes = gallery order).
	 *
	 * @return array<string,array<int,array{index:int,name:string,size:int}>>
	 */
	private static function scan_zip( \ZipArchive $zip ): array {
		$folders = array();
		for ( $i = 0; $i < $zip->numFiles; $i++ ) {
			$stat = $zip->statIndex( $i );
			if ( false === $stat ) {
				continue;
			}
			$name = (string) $stat['name'];
			if ( str_starts_with( $name, '__MACOSX' ) || str_ends_with( $name, '/' ) ) {
				continue;
			}
			$base = basename( $name );
			if ( str_starts_with( $base, '.' ) || str_starts_with( $base, '._' ) ) {
				continue;
			}
			$ext = strtolower( pathinfo( $base, PATHINFO_EXTENSION ) );
			if ( ! in_array( $ext, self::IMG_EXT, true ) ) {
				continue;
			}
			$ship = basename( dirname( $name ) );
			if ( '' === $ship || '.' === $ship ) {
				continue; // Loose image at the zip root — no ship to attach it to.
			}
			$folders[ $ship ][] = array( 'index' => $i, 'name' => $base, 'size' => (int) $stat['size'] );
		}
		foreach ( $folders as &$entries ) {
			usort( $entries, static fn( array $a, array $b ): int => strnatcasecmp( $a['name'], $b['name'] ) );
		}
		return $folders;
	}

	/**
	 * Create/update one ship. Never blanks existing content; gallery is
	 * replaced to match the folder exactly when photos are present.
	 *
	 * @param array<string,string> $row
	 * @param array<int,array{index:int,name:string,size:int}> $entries
	 * @param array<string,array<string,array{alt:string,order:int}>> $photo_meta
	 * @return array<string,mixed>
	 */
	private static function import_ship( array $row, array $entries, ?\ZipArchive $zip, bool $dry, array $photo_meta ): array {
		$ship  = $row['ship'];
		$notes = array();

		$existing = self::find_any_status( $ship );
		$action   = $existing ? ( $dry ? 'would-update' : 'updated' ) : ( $dry ? 'would-create' : 'created' );

		$photos = array_slice( $entries, 0, self::MAX_PHOTOS );
		if ( count( $entries ) > self::MAX_PHOTOS ) {
			$notes[] = 'Folder has ' . count( $entries ) . ' images — only the first ' . self::MAX_PHOTOS . ' are used.';
		}
		if ( $photos && count( $photos ) < 6 ) {
			$notes[] = 'Only ' . count( $photos ) . ' photos (6–12 is the target).';
		}
		if ( $entries && '' === $row['intro'] && ! $existing ) {
			$notes[] = 'No intro in the workbook — the section will render facts and photos only.';
		}
		$status = strtolower( $row['status'] );
		if ( $entries && ! in_array( $status, array( 'approved', 'imported' ), true ) ) {
			$notes[] = 'Workbook Status is "' . ( $row['status'] ?: 'blank' ) . '" — importing anyway because the folder is in the pack.';
		}

		$facts = self::build_facts( $row );

		if ( $dry ) {
			return array(
				'action' => $action,
				'ship'   => $ship,
				'photos' => count( $photos ),
				'facts'  => count( $facts ),
				'notes'  => implode( ' ', $notes ),
			);
		}

		// --- write ----------------------------------------------------------
		$post_id = $existing ? (int) $existing->ID : 0;
		$args    = array(
			'post_type'   => CPT_Ship::POST_TYPE,
			'post_status' => 'publish',
			'post_title'  => $ship,
		);
		if ( $post_id ) {
			$args['ID'] = $post_id;
			if ( '' !== $row['intro'] ) {
				$args['post_content'] = $row['intro'];
			}
			$updated = wp_update_post( wp_slash( $args ), true );
			if ( is_wp_error( $updated ) ) {
				return array( 'action' => 'error', 'ship' => $ship, 'photos' => 0, 'facts' => 0, 'notes' => $updated->get_error_message() );
			}
		} else {
			$args['post_content'] = $row['intro'];
			$created              = wp_insert_post( wp_slash( $args ), true );
			if ( is_wp_error( $created ) ) {
				return array( 'action' => 'error', 'ship' => $ship, 'photos' => 0, 'facts' => 0, 'notes' => $created->get_error_message() );
			}
			$post_id = (int) $created;
		}

		if ( function_exists( 'update_field' ) ) {
			if ( '' !== $row['cruiseline'] ) {
				update_field( 'field_oomph_ship_line', $row['cruiseline'], $post_id );
			}
			if ( '' !== $row['photocredit'] ) {
				update_field( 'field_oomph_ship_photo_credit', $row['photocredit'], $post_id );
			}
			if ( $facts ) {
				update_field( 'field_oomph_ship_facts', $facts, $post_id );
			}
		} else {
			$notes[] = 'ACF is not active — only the title and intro were written.';
		}

		if ( $photos && $zip ) {
			$ids = self::sideload_photos( $ship, $photos, $zip, $post_id, $photo_meta[ self::norm( $ship ) ] ?? array(), $notes );
			if ( $ids && function_exists( 'update_field' ) ) {
				update_field( 'field_oomph_ship_gallery', $ids, $post_id );
				if ( ! has_post_thumbnail( $post_id ) ) {
					set_post_thumbnail( $post_id, $ids[0] );
				}
			}
		}

		return array(
			'action' => $action,
			'ship'   => $ship,
			'photos' => count( $photos ),
			'facts'  => count( $facts ),
			'notes'  => implode( ' ', $notes ),
		);
	}

	/** First 6 non-empty facts in display order, then the custom pairs. @return array<int,array{fact_label:string,fact_value:string}> */
	private static function build_facts( array $row ): array {
		$facts = array();
		foreach ( self::FACTS as $key => $label ) {
			$val = $row[ $key ] ?? '';
			if ( '' === $val ) {
				continue;
			}
			if ( 'suitesstaterooms' === $key && false !== stripos( $val, 'suite' ) ) {
				$label = 'Suites';
			}
			$facts[] = array( 'fact_label' => $label, 'fact_value' => $val );
		}
		foreach ( array( 1, 2 ) as $n ) {
			$l = $row[ "custom{$n}label" ] ?? '';
			$v = $row[ "custom{$n}value" ] ?? '';
			if ( '' !== $l && '' !== $v ) {
				$facts[] = array( 'fact_label' => $l, 'fact_value' => $v );
			}
		}
		return array_slice( $facts, 0, self::MAX_FACT_ROWS );
	}

	/**
	 * Sideload the folder's photos, deduped by md5 content hash.
	 *
	 * @param array<int,array{index:int,name:string,size:int}> $photos
	 * @param array<string,array{alt:string,order:int}> $meta
	 * @param array<int,string> $notes By-ref warning collector.
	 * @return array<int,int> Ordered attachment IDs.
	 */
	private static function sideload_photos( string $ship, array $photos, \ZipArchive $zip, int $post_id, array $meta, array &$notes ): array {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$ids = array();
		foreach ( $photos as $p ) {
			@set_time_limit( 120 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
			$data = $zip->getFromIndex( $p['index'] );
			if ( false === $data ) {
				$notes[] = $p['name'] . ': could not be read from the zip.';
				continue;
			}
			$hash = md5( $data );
			$m    = $meta[ strtolower( $p['name'] ) ] ?? null;
			$alt  = ( $m && '' !== $m['alt'] ) ? $m['alt'] : self::alt_from_filename( $p['name'], $ship );

			$att_id = self::find_by_hash( $hash );
			if ( ! $att_id ) {
				$tmp = wp_tempnam( $p['name'] );
				if ( false === file_put_contents( $tmp, $data ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions
					$notes[] = $p['name'] . ': could not be written to disk.';
					continue;
				}
				$att_id = media_handle_sideload(
					array( 'name' => sanitize_file_name( $p['name'] ), 'tmp_name' => $tmp ),
					$post_id,
					null
				);
				if ( is_wp_error( $att_id ) ) {
					@unlink( $tmp ); // phpcs:ignore
					$notes[] = $p['name'] . ': ' . $att_id->get_error_message();
					continue;
				}
				update_post_meta( (int) $att_id, self::HASH_META, $hash );
			}
			$att_id = (int) $att_id;
			// Workbook alt wins; a filename-derived alt only fills a blank.
			if ( ( $m && '' !== $m['alt'] ) || '' === (string) get_post_meta( $att_id, '_wp_attachment_image_alt', true ) ) {
				update_post_meta( $att_id, '_wp_attachment_image_alt', $alt );
			}
			$ids[] = $att_id;
		}
		return $ids;
	}

	private static function find_by_hash( string $hash ): int {
		$found = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => self::HASH_META, // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'     => $hash,           // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);
		return $found ? (int) $found[0] : 0;
	}

	/** "02-the-retreat-sundeck.jpg" -> "The retreat sundeck aboard Celebrity Beyond." */
	private static function alt_from_filename( string $filename, string $ship ): string {
		$base  = pathinfo( $filename, PATHINFO_FILENAME );
		$base  = (string) preg_replace( '/^[\d\s._-]+/', '', $base );
		$words = trim( (string) preg_replace( '/\s+/', ' ', str_replace( array( '-', '_' ), ' ', $base ) ) );
		if ( '' === $words ) {
			return $ship;
		}
		return ucfirst( strtolower( $words ) ) . ' aboard ' . $ship . '.';
	}

	/** Exact-title lookup across any status (drafts included, unlike CPT_Ship::find_by_name). */
	private static function find_any_status( string $ship ): ?\WP_Post {
		$q = new \WP_Query(
			array(
				'post_type'              => CPT_Ship::POST_TYPE,
				'post_status'            => 'any',
				'title'                  => trim( $ship ),
				'posts_per_page'         => 1,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);
		return $q->have_posts() ? $q->posts[0] : null;
	}

	private static function norm( string $s ): string {
		return (string) preg_replace( '/[^a-z0-9]/', '', strtolower( $s ) );
	}

	// --- results ------------------------------------------------------------

	/** @param array{error:?string,results:array<int,array<string,mixed>>,warnings:array<int,string>} $res */
	private static function render_results( array $res, bool $dry, string $token ): void {
		if ( ! empty( $res['error'] ) ) {
			printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( (string) $res['error'] ) );
			return;
		}

		echo '<div class="notice ' . ( $dry ? 'notice-info' : 'notice-success' ) . '"><p><strong>';
		echo $dry ? 'Preview only — nothing was changed.' : 'Import complete.';
		echo '</strong></p></div>';

		foreach ( $res['warnings'] as $w ) {
			printf( '<div class="notice notice-warning"><p>%s</p></div>', esc_html( $w ) );
		}

		if ( $res['results'] ) {
			echo '<table class="widefat striped" style="max-width:900px"><thead><tr><th>What</th><th>Ship</th><th>Photos</th><th>Facts</th><th>Notes</th></tr></thead><tbody>';
			foreach ( $res['results'] as $r ) {
				printf(
					'<tr><td>%s</td><td><strong>%s</strong></td><td>%d</td><td>%d</td><td>%s</td></tr>',
					esc_html( (string) $r['action'] ),
					esc_html( (string) $r['ship'] ),
					(int) $r['photos'],
					(int) $r['facts'],
					esc_html( (string) $r['notes'] )
				);
			}
			echo '</tbody></table>';
		}

		if ( $dry && $res['results'] ) {
			?>
			<hr>
			<form method="post" style="margin-top:8px">
				<?php wp_nonce_field( self::NONCE ); ?>
				<input type="hidden" name="oomph_step" value="import">
				<input type="hidden" name="oomph_token" value="<?php echo esc_attr( $token ); ?>">
				<p><strong>Looks right?</strong> Run the real import of this same pack:</p>
				<?php submit_button( 'Import for real', 'primary', 'submit', false ); ?>
			</form>
			<?php
		} elseif ( ! $dry ) {
			echo '<div class="notice notice-info" style="margin-top:16px"><p><strong>Next:</strong> open one live sailing page per imported ship and check the &ldquo;Life on board&rdquo; section, then tick these ships off in the workbook (Status &rarr; Imported).</p></div>';
		}
	}
}
