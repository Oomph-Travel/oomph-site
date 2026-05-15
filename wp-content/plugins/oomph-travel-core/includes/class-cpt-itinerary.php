<?php
/**
 * Itinerary CPT — published sample/skeleton itineraries.
 *
 * Slug: oomph_itinerary · public rewrite: /itineraries/[slug]/
 *
 * @package OomphTravel\Core
 */

declare( strict_types=1 );

namespace OomphTravel\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CPT_Itinerary {

	public const POST_TYPE = 'oomph_itinerary';

	public static function register(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels' => array(
					'name'          => __( 'Itineraries', 'oomph-travel-core' ),
					'singular_name' => __( 'Itinerary', 'oomph-travel-core' ),
				),
				'public'        => true,
				'show_in_rest'  => true,
				'has_archive'   => true,
				'menu_position' => 22,
				'menu_icon'     => 'dashicons-list-view',
				'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions' ),
				'rewrite'       => array(
					'slug'       => 'itineraries',
					'with_front' => false,
				),
				'capability_type' => 'post',
			)
		);
	}
}
