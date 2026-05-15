<?php
/**
 * Taxonomies — Region (hierarchical) and Trip Style (flat tag).
 *
 * Both attach to all three CPTs (Destination, Itinerary, Cruise).
 *
 * @package OomphTravel\Core
 */

declare( strict_types=1 );

namespace OomphTravel\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Taxonomies {

	public const REGION     = 'oomph_region';
	public const TRIP_STYLE = 'oomph_trip_style';

	public static function register(): void {
		$object_types = array(
			CPT_Destination::POST_TYPE,
			CPT_Itinerary::POST_TYPE,
			CPT_Cruise::POST_TYPE,
		);

		register_taxonomy(
			self::REGION,
			$object_types,
			array(
				'labels' => array(
					'name'          => __( 'Regions', 'oomph-travel-core' ),
					'singular_name' => __( 'Region', 'oomph-travel-core' ),
				),
				'hierarchical' => true,
				'public'       => true,
				'show_in_rest' => true,
				'rewrite'      => array(
					'slug'       => 'regions',
					'with_front' => false,
				),
			)
		);

		register_taxonomy(
			self::TRIP_STYLE,
			$object_types,
			array(
				'labels' => array(
					'name'          => __( 'Trip Styles', 'oomph-travel-core' ),
					'singular_name' => __( 'Trip Style', 'oomph-travel-core' ),
				),
				'hierarchical' => false,
				'public'       => true,
				'show_in_rest' => true,
				'rewrite'      => array(
					'slug'       => 'trip-styles',
					'with_front' => false,
				),
			)
		);
	}
}
