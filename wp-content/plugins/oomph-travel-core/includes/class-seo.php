<?php
/**
 * Technical SEO: robots.txt + llms.txt.
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
		add_filter( 'robots_txt', array( __CLASS__, 'robots_txt' ), 10, 2 );
		// Serve /llms.txt early, before WordPress's canonical trailing-slash
		// redirect kicks in on the .txt request.
		add_action( 'init', array( __CLASS__, 'serve_llms' ), 0 );
	}

	public static function robots_txt( string $output, $public ): string {
		if ( ! $public ) {
			return $output; // staging / non-public: keep WP's disallow-all.
		}
		$lines   = array();
		$lines[] = 'User-agent: *';
		$lines[] = 'Allow: /';
		$lines[] = 'Disallow: /wp-admin/';
		$lines[] = 'Allow: /wp-admin/admin-ajax.php';
		$lines[] = '';
		$lines[] = 'Sitemap: ' . home_url( '/sitemap_index.xml' );
		$lines[] = 'Sitemap: ' . home_url( '/llms.txt' );
		return implode( "\n", $lines ) . "\n";
	}

	public static function serve_llms(): void {
		$uri = strtok( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), '?' );
		if ( rtrim( $uri, '/' ) !== '/llms.txt' ) {
			return;
		}
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'X-Robots-Tag: noindex' );
		echo self::llms_content(); // phpcs:ignore WordPress.Security.EscapeOutput
		exit;
	}

	private static function llms_content(): string {
		$site = home_url( '/' );
		$out  = array();

		$out[] = '# Oomph Travel';
		$out[] = '';
		$out[] = '> Premium and luxury cruise planning and custom European journeys, planned by one named advisor — Eric Hempel, a CLIA member and Silversea Ultra-Luxury Specialist based in Port Angeles, Washington. No planning fee; suppliers pay a commission that does not change your price.';
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
			$out[] = '- [About Eric Hempel](' . get_permalink( $about ) . ')';
			$out[] = '';
		}

		return implode( "\n", $out );
	}
}
