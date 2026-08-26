<?php
/**
 * Technical SEO: llms.txt, sailing meta, and Rank Math guards.
 *
 * - robots.txt: allow crawling on the public (production) site, point to the
 *   Rank Math sitemap. Non-public environments (staging) keep WordPress's
 *   default disallow.
 * - llms.txt: a plain-text guide for AI crawlers (the emerging /llms.txt
 *   convention), generated from the site's key pages + recent journal posts.
 *
 * @package OomphTravel\Core
 */

declare( strict_types=1 );

namespace OomphTravel\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SEO {

	public static function init(): void {
		// Rank Math already serves a crawl-friendly robots.txt with the XML
		// sitemap, so we don't filter robots_txt. We only add /llms.txt,
		// served early — before WordPress's canonical trailing-slash redirect
		// would fire on the .txt request.
		add_action( 'init', array( __CLASS__, 'serve_llms' ), 0 );

		// Sailings are imported in the hundreds and nobody hand-writes a meta
		// description for each; generate one from the sailing's own fields.
		// A description typed in the editor always wins.
		add_filter( 'rank_math/frontend/description', array( __CLASS__, 'sailing_description' ), 10, 1 );

		// Rank Math's Titles & Meta settings can noindex the whole Group
		// Cruise post type and drop it from the XML sitemap — settings drift,
		// the same failure mode class-schema.php guards against on the JSON-LD
		// side. Published sailings and their archive are this site's organic
		// growth engine; pin them indexable and sitemapped in code so a UI
		// toggle can never silently unlist 300+ pages again.
		add_filter( 'rank_math/frontend/robots', array( __CLASS__, 'sailing_robots' ) );
		add_filter( 'option_rank-math-options-titles', array( __CLASS__, 'pin_sailing_titles_options' ) );
		add_filter( 'option_rank-math-options-sitemap', array( __CLASS__, 'pin_sailing_sitemap_option' ) );

		// The archive ships with Rank Math's bare defaults ("Group Cruises" /
		// "Group Cruises Archive - Oomph Travel"); its title and description
		// are code-owned here — edit this class, not the Rank Math UI.
		add_filter( 'rank_math/frontend/title', array( __CLASS__, 'archive_title' ) );
	}

	/**
	 * Force index,follow on published sailings and the Group Cruises archive.
	 *
	 * Runs last on Rank Math's resolved robots array, so it wins over both the
	 * post-type default and any stale per-post value left by an import.
	 *
	 * @param array $robots Rank Math's resolved robots directives.
	 * @return array
	 */
	public static function sailing_robots( $robots ) {
		$is_sailing = ( is_singular( CPT_Cruise::POST_TYPE ) && 'publish' === get_post_status() )
			|| is_post_type_archive( CPT_Cruise::POST_TYPE );

		if ( ! $is_sailing ) {
			return $robots;
		}

		$robots = is_array( $robots ) ? $robots : array();
		unset( $robots['noindex'] );
		$robots['index']  = 'index';
		$robots['follow'] = 'follow';

		return $robots;
	}

	/**
	 * Pin the Group Cruise post-type robots setting to index.
	 *
	 * The sitemap module skips posts whose post-type default resolves to
	 * noindex, so the frontend robots filter alone is not enough — the stored
	 * option has to read as indexable too. Only this one key is overridden;
	 * everything else in the option passes through untouched.
	 *
	 * @param mixed $options The rank-math-options-titles option value.
	 * @return mixed
	 */
	public static function pin_sailing_titles_options( $options ) {
		if ( is_array( $options ) ) {
			$options[ 'pt_' . CPT_Cruise::POST_TYPE . '_robots' ] = array( 'index' );
		}
		return $options;
	}

	/**
	 * Keep the Group Cruise post type included in the XML sitemap.
	 *
	 * @param mixed $options The rank-math-options-sitemap option value.
	 * @return mixed
	 */
	public static function pin_sailing_sitemap_option( $options ) {
		if ( is_array( $options ) ) {
			$options[ 'pt_' . CPT_Cruise::POST_TYPE . '_sitemap' ] = 'on';
		}
		return $options;
	}

	/**
	 * Title for the Group Cruises archive (R5: keyword first, ~50 chars).
	 *
	 * @param string $title Rank Math's value.
	 * @return string
	 */
	public static function archive_title( $title ) {
		if ( is_post_type_archive( CPT_Cruise::POST_TYPE ) ) {
			return 'Group Cruises with Extra Amenities | Oomph Travel';
		}
		return $title;
	}

	/**
	 * Auto meta description for a Group Cruise, built from its own data.
	 *
	 * Distinctive Voyages lead with the private shore event; amenity
	 * departures lead with the amenity. Falls through untouched for every
	 * other post type and for sailings with a hand-written description.
	 *
	 * @param string $description Rank Math's value.
	 * @return string
	 */
	public static function sailing_description( $description ) {
		// The archive's title and description are code-owned (Rank Math's
		// default is "Group Cruises Archive - Oomph Travel").
		if ( is_post_type_archive( CPT_Cruise::POST_TYPE ) ) {
			return 'Group cruise departures with a private shore event or shipboard credit included at the same fare. Compare 2026–2027 sailings, planned with Eric Hempel.';
		}

		if ( ! is_singular( CPT_Cruise::POST_TYPE ) || ! function_exists( 'get_field' ) ) {
			return $description;
		}

		$post_id = get_queried_object_id();
		if ( ! $post_id ) {
			return $description;
		}

		$manual = get_post_meta( $post_id, 'rank_math_description', true );
		if ( is_string( $manual ) && '' !== trim( $manual ) ) {
			return $description;
		}

		$f         = static function ( string $k ) use ( $post_id ): string {
			return trim( (string) get_field( $k, $post_id ) );
		};
		$ship      = $f( 'cruise_ship_name' );
		$line      = $f( 'cruise_line' );
		$start     = $f( 'cruise_dates_start' );
		$type      = $f( 'sailing_type' );
		$perk      = $f( 'amenity_summary' );
		$event     = $f( 'shore_event_1_name' );
		$embark    = $f( 'embark_city' );
		$disembark = $f( 'disembark_city' );

		// "{Itinerary} — {Ship}, {Month Year}" is the imported title shape.
		$itin = explode( ' — ', (string) get_the_title( $post_id ) )[0];
		$when = $start ? date_i18n( 'F Y', (int) strtotime( $start ) ) : '';

		$parts   = array();
		$parts[] = sprintf( '%s aboard %s%s.', $itin, $ship ?: 'ship', $when ? ", {$when}" : '' );

		if ( $embark && $disembark ) {
			$parts[] = $embark === $disembark
				? "Round-trip from {$embark}."
				: "{$embark} to {$disembark}.";
		}

		if ( 'distinctive_voyage' === $type && $event ) {
			$parts[] = "Includes a private shore event: {$event}.";
		} elseif ( $perk ) {
			$parts[] = rtrim( $perk, '.' ) . '.';
		}

		$parts[] = $line
			? "Planned with Eric Hempel — same {$line} fare, more included."
			: 'Planned with Eric Hempel.';

		$out = trim( (string) preg_replace( '/\s+/', ' ', implode( ' ', $parts ) ) );

		// Meta descriptions truncate around 160 chars; cut on a word boundary.
		if ( mb_strlen( $out ) > 158 ) {
			$out = rtrim( mb_substr( $out, 0, 155 ), " .,;:—-" ) . '…';
		}

		return $out;
	}

	public static function serve_llms(): void {
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return; // No request context (e.g. WP-CLI).
		}
		$uri = strtok( (string) $_SERVER['REQUEST_URI'], '?' );
		if ( ! is_string( $uri ) || '/llms.txt' !== rtrim( $uri, '/' ) ) {
			return;
		}
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'X-Robots-Tag: noindex' );
		echo self::llms_content(); // phpcs:ignore WordPress.Security.EscapeOutput
		exit;
	}

	public static function llms_content(): string {
		$site = home_url( '/' );
		$out  = array();

		$out[] = '# Oomph Travel';
		$out[] = '';
		$advisor = Advisor::name();
		$out[] = '> Premium and luxury cruise planning and custom European journeys, planned by one named advisor — ' . $advisor . ', a CLIA member and Silversea Ultra-Luxury Specialist based in Port Angeles, Washington. No planning fee; suppliers pay a commission that does not change your price.';
		$out[] = '';

		$out[] = '## Services';
		foreach ( array(
			'luxury-cruise-planning'             => 'Luxury cruise planning — premium and ultra-luxury cruises (Silversea, Regent, Seabourn, Crystal, Cunard Grills, Viking), cabin selection, onboard credit, pre/post extensions.',
			'custom-italy-travel'                => 'Custom Italy travel — region-by-region itineraries (Tuscany, the Lakes, the Amalfi Coast, Puglia, Sicily, the Dolomites), private drivers, vetted guides.',
			'multi-generational-travel-planning' => 'Multi-generational travel — trips planned across two or three generations, around pace, mobility, dietary needs, and room/cabin configurations.',
		) as $slug => $desc ) {
			$p = get_page_by_path( $slug );
			if ( $p && 'publish' === get_post_status( $p ) ) {
				$out[] = '- [' . get_the_title( $p ) . '](' . get_permalink( $p ) . '): ' . $desc;
			}
		}
		$out[] = '';

		$out[] = '## Start here';
		foreach ( array(
			'discovery-call'       => 'Book a free 30-minute discovery call.',
			'trip-quiz'            => 'Cabin quiz — a 7-question quiz that matches you to the right cruise cabin category.',
			'cruise-travel-trends' => 'Cruise Travel Trends — a free field guide to where cruising is headed in 2026–2027.',
		) as $slug => $desc ) {
			$p = get_page_by_path( $slug );
			if ( $p && 'publish' === get_post_status( $p ) ) {
				$out[] = '- [' . get_the_title( $p ) . '](' . get_permalink( $p ) . '): ' . $desc;
			}
		}
		$out[] = '';

		$posts = get_posts( array( 'numberposts' => 20, 'post_status' => 'publish' ) );
		if ( $posts ) {
			$out[] = '## Journal';
			foreach ( $posts as $post ) {
				$out[] = '- [' . get_the_title( $post ) . '](' . get_permalink( $post ) . '): ' . wp_strip_all_tags( (string) get_the_excerpt( $post ) );
			}
			$out[] = '';
		}

		$about = get_page_by_path( 'about' );
		if ( $about ) {
			$out[] = '## About';
			$out[] = '- [About ' . $advisor . '](' . get_permalink( $about ) . ')';
			$out[] = '';
		}

		return implode( "\n", $out );
	}
}
