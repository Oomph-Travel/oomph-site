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
