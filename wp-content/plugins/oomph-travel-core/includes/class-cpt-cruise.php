<?php
/**
 * Group Cruise CPT — hosted-cruise landing pages.
 *
 * Slug: oomph_cruise · public rewrite: /group-cruises/[slug]/
 * Event schema injection lives in class-schema.php.
 *
 * @package OomphTravel\Core
 */

declare( strict_types=1 );

namespace OomphTravel\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CPT_Cruise {

	public const POST_TYPE = 'oomph_cruise';

	/**
	 * Register non-CPT hooks for this post type.
	 *
	 * A cruise post is a structured data-entry form (40 ACF fields) with one
	 * short editorial paragraph in the content. The block editor always renders
	 * ACF field groups below the block canvas, which buries the form; the classic
	 * editor honours the group's `acf_after_title` position, putting the fields
	 * directly under the title where they belong. REST stays enabled
	 * (show_in_rest => true) — this only swaps the editing surface.
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
					'name'          => __( 'Group Cruises', 'oomph-travel-core' ),
					'singular_name' => __( 'Group Cruise', 'oomph-travel-core' ),
				),
				'public'        => true,
				'show_in_rest'  => true,
				'has_archive'   => true,
				'menu_position' => 23,
				'menu_icon'     => 'dashicons-palmtree',
				'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions' ),
				'rewrite'       => array(
					'slug'       => 'group-cruises',
					'with_front' => false,
				),
				'capability_type' => 'post',
			)
		);
	}
}
