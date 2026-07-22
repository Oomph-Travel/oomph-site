<?php
/**
 * Enrichment engine — applies a prepared payload to a Group Cruise post.
 *
 * Shared by the WP-CLI command (class-enrich.php) and the admin
 * "Enrichment Sync" screen (class-enrich-sync.php). One code path,
 * one set of guarantees:
 *
 *   • Never publishes. Post status is untouched.
 *   • Never overwrites a human-written "Why this sailing" — post_content
 *     is only written when empty or still the import placeholder
 *     (callers may pass $force_why for a deliberate overwrite).
 *   • Dry-run reports what would change and writes nothing.
 *
 * Payload shape (all keys optional):
 * {
 *   "itinerary":  [ { "day": 1, "port": "…", "activity": "…" }, … ],
 *   "inclusions": "Line-break separated text",
 *   "exclusions": "Line-break separated text",
 *   "why":        "2–3 sentences, first person, plain."
 * }
 *
 * @package OomphTravel\Core
 */

declare( strict_types=1 );

namespace OomphTravel\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Enrich_Engine {

	/**
	 * Apply a payload to a cruise post.
	 *
	 * @param int   $post_id   Target oomph_cruise post.
	 * @param array $payload   Decoded payload array.
	 * @param bool  $dry       When true, report only — write nothing.
	 * @param bool  $force_why Overwrite human-written post_content.
	 *
	 * @return array{changes: string[], warnings: string[], error: ?string}
	 */
	public static function apply( int $post_id, array $payload, bool $dry = false, bool $force_why = false ): array {
		$out  = array(
			'changes'  => array(),
			'warnings' => array(),
			'error'    => null,
		);
		$post = get_post( $post_id );

		if ( ! $post || CPT_Cruise::POST_TYPE !== $post->post_type ) {
			$out['error'] = "Post {$post_id} is not a Group Cruise.";
			return $out;
		}

		// --- Itinerary rows -> ACF repeater `cruise_itinerary`. ---------------
		if ( isset( $payload['itinerary'] ) && is_array( $payload['itinerary'] ) ) {
			$rows = array();
			foreach ( $payload['itinerary'] as $i => $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$port     = sanitize_text_field( (string) ( $row['port'] ?? '' ) );
				$activity = sanitize_textarea_field( (string) ( $row['activity'] ?? '' ) );
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
				if ( ! function_exists( 'update_field' ) ) {
					$out['error'] = 'ACF is not active — cannot write the itinerary repeater.';
					return $out;
				}
				$out['changes'][] = 'itinerary (' . count( $rows ) . ' days)';
				if ( ! $dry ) {
					update_field( 'cruise_itinerary', $rows, $post_id );
				}
			}
		}

		// --- Inclusions / exclusions -> ACF textareas. ------------------------
		foreach ( array( 'inclusions' => 'cruise_inclusions', 'exclusions' => 'cruise_exclusions' ) as $key => $field ) {
			if ( isset( $payload[ $key ] ) && '' !== trim( (string) $payload[ $key ] ) ) {
				if ( ! function_exists( 'update_field' ) ) {
					$out['error'] = 'ACF is not active — cannot write ' . $field . '.';
					return $out;
				}
				$out['changes'][] = $key;
				if ( ! $dry ) {
					update_field( $field, sanitize_textarea_field( (string) $payload[ $key ] ), $post_id );
				}
			}
		}

		// --- "Why this sailing" draft -> post_content (guarded). --------------
		if ( isset( $payload['why'] ) && '' !== trim( (string) $payload['why'] ) ) {
			if ( self::why_is_stub( $post ) || $force_why ) {
				$out['changes'][] = self::why_is_stub( $post ) ? 'why (placeholder replaced)' : 'why (FORCED overwrite)';
				if ( ! $dry ) {
					wp_update_post(
						array(
							'ID'           => $post_id,
							'post_content' => wp_kses_post( trim( (string) $payload['why'] ) ),
						)
					);
				}
			} else {
				$out['warnings'][] = 'Skipped "why" — post_content already written by a human.';
			}
		}

		return $out;
	}

	/** Whether post_content is empty or still the import placeholder. */
	public static function why_is_stub( \WP_Post $post ): bool {
		$current = trim( $post->post_content );
		return ( '' === $current )
			|| ( false !== strpos( $current, 'WHY THIS SAILING' ) && false !== strpos( $current, 'Eric writes' ) );
	}

	/** Find a cruise post (any non-trash status) by slug. */
	public static function find_by_slug( string $slug ): ?\WP_Post {
		$slug = sanitize_title( $slug );
		if ( '' === $slug ) {
			return null;
		}
		$query = new \WP_Query(
			array(
				'post_type'              => CPT_Cruise::POST_TYPE,
				'post_status'            => array( 'publish', 'draft', 'pending', 'future', 'private' ),
				'name'                   => $slug,
				'posts_per_page'         => 1,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);
		return $query->have_posts() ? $query->posts[0] : null;
	}
}
