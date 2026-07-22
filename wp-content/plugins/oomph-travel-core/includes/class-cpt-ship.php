<?php
/**
 * Ship Library CPT — reusable per-ship content.
 *
 * Slug: oomph_ship · not publicly queryable (no front-end URL of its own).
 * A ship record holds the gallery, the intro paragraph, and the quick facts
 * that are identical for every sailing of that ship. The cruise templates
 * look the ship up by name (the TLN import already writes cruise_ship_name)
 * so a sailing page gets its ship section with zero per-sailing effort.
 *
 * Enter each ship once — every current and future sailing of it benefits.
 *
 * @package OomphTravel\Core
 */

declare( strict_types=1 );

namespace OomphTravel\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CPT_Ship {

	public const POST_TYPE = 'oomph_ship';

	/** Per-request lookup cache: normalized ship name => post ID (0 = miss). */
	private static array $cache = array();

	/**
	 * Classic editor, same as Group Cruises: the ACF group's acf_after_title
	 * position only works there — in the block editor the fields sink into a
	 * collapsed meta box under an empty canvas (confirmed on prod 2026-07-22).
	 */
	public static function init(): void {
		add_filter( 'use_block_editor_for_post_type', array( __CLASS__, 'use_classic_editor' ), 10, 2 );
	}

	public static function use_classic_editor( bool $use_block_editor, string $post_type ): bool {
		return self::POST_TYPE === $post_type ? false : $use_block_editor;
	}

	public static function register(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels' => array(
					'name'          => __( 'Ships', 'oomph-travel-core' ),
					'singular_name' => __( 'Ship', 'oomph-travel-core' ),
					'add_new_item'  => __( 'Add New Ship', 'oomph-travel-core' ),
					'edit_item'     => __( 'Edit Ship', 'oomph-travel-core' ),
				),
				// Data source only — no front-end URLs, no archive, no sitemap entry.
				'public'              => false,
				'show_ui'             => true,
				'show_in_rest'        => true,
				'exclude_from_search' => true,
				'publicly_queryable'  => false,
				'has_archive'         => false,
				'rewrite'             => false,
				'menu_position'       => 24,
				'menu_icon'           => 'dashicons-sos',
				// post_content is the ship intro (Eric's voice, first person).
				'supports'            => array( 'title', 'editor', 'thumbnail', 'revisions' ),
				'capability_type'     => 'post',
			)
		);
	}

	/**
	 * Find the ship record whose title matches a sailing's ship name.
	 *
	 * Match is case-insensitive on the exact post title ("Silver Nova").
	 * Returns null when no ship record exists — templates render nothing,
	 * so an un-seeded ship never breaks a sailing page.
	 */
	public static function find_by_name( string $ship_name ): ?\WP_Post {
		$key = strtolower( trim( $ship_name ) );
		if ( '' === $key ) {
			return null;
		}

		if ( ! array_key_exists( $key, self::$cache ) ) {
			$query = new \WP_Query(
				array(
					'post_type'              => self::POST_TYPE,
					'post_status'            => 'publish',
					'title'                  => trim( $ship_name ),
					'posts_per_page'         => 1,
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				)
			);
			self::$cache[ $key ] = $query->have_posts() ? (int) $query->posts[0]->ID : 0;
		}

		$post_id = self::$cache[ $key ];
		return $post_id ? get_post( $post_id ) : null;
	}
}
