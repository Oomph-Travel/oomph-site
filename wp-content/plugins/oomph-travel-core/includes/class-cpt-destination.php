<?php
/**
 * Destination CPT — long-form destination guides
 * (e.g., Puglia, Amalfi, Dolomites).
 *
 * Slug: oomph_destination · public rewrite: /destinations/[slug]/
 *
 * @package OomphTravel\Core
 */

declare( strict_types=1 );

namespace OomphTravel\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CPT_Destination {

	public const POST_TYPE = 'oomph_destination';

	public static function register(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels' => array(
					'name'          => __( 'Destinations', 'oomph-travel-core' ),
					'singular_name' => __( 'Destination', 'oomph-travel-core' ),
					'add_new_item'  => __( 'Add New Destination', 'oomph-travel-core' ),
					'edit_item'     => __( 'Edit Destination', 'oomph-travel-core' ),
				),
				'public'              => true,
				'show_in_rest'        => true,
				'has_archive'         => true,
				'menu_position'       => 21,
				'menu_icon'           => 'dashicons-location',
				'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions' ),
				'rewrite'             => array(
					'slug'       => 'destinations',
					'with_front' => false,
				),
				'capability_type'     => 'post',
			)
		);
	}
}
