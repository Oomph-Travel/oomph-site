<?php
/**
 * WP-CLI command: wp oomph enrich-sailing <post_id> --file=<payload.json>
 *
 * Writes prepared enrichment content onto an imported sailing: the day-by-day
 * itinerary rows, inclusions/exclusions, and (optionally) a draft "Why this
 * sailing" paragraph. Built for the monthly cadence: the TLN import creates
 * the drafts, an enrichment payload deepens them, Eric reviews and publishes.
 *
 * Safety rails:
 *   • Never publishes. Post status is untouched.
 *   • Never overwrites an edited "Why this sailing" — post_content is only
 *     written when it is empty or still the import placeholder (--force-why
 *     overrides, deliberately verbose in name).
 *   • --dry-run reports what would change and writes nothing.
 *
 * Payload shape (all keys optional):
 * {
 *   "itinerary":  [ { "day": 1, "port": "Athens (Piraeus), Greece", "activity": "…" }, … ],
 *   "inclusions": "Line-break separated text",
 *   "exclusions": "Line-break separated text",
 *   "why":        "2–3 sentences, first person, plain."
 * }
 *
 * @package OomphTravel\Core
 */

declare( strict_types=1 );

namespace OomphTravel\Core;

if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

final class Enrich {

	/**
	 * Enrich an imported sailing from a JSON payload file.
	 *
	 * ## OPTIONS
	 *
	 * <post_id>
	 * : ID of the oomph_cruise post to enrich.
	 *
	 * --file=<path>
	 * : Path to the JSON payload.
	 *
	 * [--force-why]
	 * : Overwrite post_content even when Eric has already written it.
	 *
	 * [--dry-run]
	 * : Report what would change without writing anything.
	 *
	 * ## EXAMPLES
	 *
	 *     wp oomph enrich-sailing 1234 --file=enrich-1234.json --dry-run
	 *     wp oomph enrich-sailing 1234 --file=enrich-1234.json
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		$post_id = (int) $args[0];
		$post    = get_post( $post_id );

		if ( ! $post || CPT_Cruise::POST_TYPE !== $post->post_type ) {
			\WP_CLI::error( "Post {$post_id} is not a Group Cruise." );
		}

		$path = (string) ( $assoc_args['file'] ?? '' );
		if ( '' === $path || ! is_readable( $path ) ) {
			\WP_CLI::error( 'Payload file not found or unreadable: ' . $path );
		}

		$payload = json_decode( (string) file_get_contents( $path ), true );
		if ( ! is_array( $payload ) ) {
			\WP_CLI::error( 'Payload is not valid JSON: ' . json_last_error_msg() );
		}

		$dry       = (bool) ( $assoc_args['dry-run'] ?? false );
		$force_why = (bool) ( $assoc_args['force-why'] ?? false );
		$changes   = array();

		// --- Itinerary rows -> ACF repeater `cruise_itinerary`. ---------------
		if ( isset( $payload['itinerary'] ) && is_array( $payload['itinerary'] ) ) {
			$rows = array();
			foreach ( $payload['itinerary'] as $i => $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$port     = trim( (string) ( $row['port'] ?? '' ) );
				$activity = trim( (string) ( $row['activity'] ?? '' ) );
				if ( '' === $port && '' === $activity ) {
					continue;
				}
				$rows[] = array(
					'day'      => (int) ( $row['day'] ?? ( $i + 1 ) ),
					'port'     => $port,
					'activity' => $activity,
				);
			}
			if ( $rows ) {
				$changes[] = 'itinerary (' . count( $rows ) . ' days)';
				if ( ! $dry ) {
					if ( ! function_exists( 'update_field' ) ) {
						\WP_CLI::error( 'ACF is not active — cannot write the itinerary repeater.' );
					}
					update_field( 'cruise_itinerary', $rows, $post_id );
				}
			}
		}

		// --- Inclusions / exclusions -> ACF textareas. ------------------------
		foreach ( array( 'inclusions' => 'cruise_inclusions', 'exclusions' => 'cruise_exclusions' ) as $key => $field ) {
			if ( isset( $payload[ $key ] ) && '' !== trim( (string) $payload[ $key ] ) ) {
				$changes[] = $key;
				if ( ! $dry ) {
					if ( ! function_exists( 'update_field' ) ) {
						\WP_CLI::error( 'ACF is not active — cannot write ' . $field . '.' );
					}
					update_field( $field, trim( (string) $payload[ $key ] ), $post_id );
				}
			}
		}

		// --- "Why this sailing" draft -> post_content (guarded). --------------
		if ( isset( $payload['why'] ) && '' !== trim( (string) $payload['why'] ) ) {
			$current = trim( $post->post_content );
			$is_stub = ( '' === $current )
				|| ( false !== strpos( $current, 'WHY THIS SAILING' ) && false !== strpos( $current, 'Eric writes' ) );

			if ( $is_stub || $force_why ) {
				$changes[] = $is_stub ? 'why (placeholder replaced)' : 'why (FORCED overwrite)';
				if ( ! $dry ) {
					wp_update_post(
						array(
							'ID'           => $post_id,
							'post_content' => trim( (string) $payload['why'] ),
						)
					);
				}
			} else {
				\WP_CLI::warning( 'Skipped "why" — post_content already written by a human. Use --force-why to overwrite.' );
			}
		}

		if ( ! $changes ) {
			\WP_CLI::success( 'Nothing to do — payload contained no usable fields.' );
			return;
		}

		$verb = $dry ? 'Would write' : 'Wrote';
		\WP_CLI::success( sprintf( '%s to "%s" (#%d, status: %s): %s.', $verb, $post->post_title, $post_id, $post->post_status, implode( ', ', $changes ) ) );
	}
}

\WP_CLI::add_command( 'oomph enrich-sailing', Enrich::class );
