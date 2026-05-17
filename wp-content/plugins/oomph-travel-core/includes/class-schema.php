<?php
/**
 * JSON-LD schema injection.
 *
 * Single output() entry on wp_head outputs one combined @graph that
 * covers Organization, Person, BreadcrumbList sitewide, plus contextual
 * Service / Event / FAQPage / Article depending on page type.
 *
 * Bodies of each graph come from docs/schema.md. Real values (URLs,
 * dates, photo paths) substitute placeholders at runtime via
 * get_permalink(), get_the_date(), etc.
 *
 * Universal rules from docs/schema.md:
 *   • @id URLs match the actual page URL with #fragment for entities
 *   • Never mark up content not visible on the page
 *   • Update dateModified on every meaningful edit
 *
 * @package OomphTravel\Core
 */

declare( strict_types=1 );

namespace OomphTravel\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Schema {

	public static function output(): void {
		$graph = array();

		$graph[] = self::organization();
		$graph[] = self::person();

		if ( is_singular() || is_front_page() ) {
			$graph[] = self::breadcrumb();
		}

		if ( is_singular( CPT_Cruise::POST_TYPE ) ) {
			$event = self::event_for_current_post();
			if ( $event ) {
				$graph[] = $event;
			}
		}

		if ( self::is_service_page() ) {
			$graph[] = self::service_for_current_page();
			$faqpage = self::faqpage_for_current_page();
			if ( $faqpage ) {
				$graph[] = $faqpage;
			}
		}

		$payload = array(
			'@context' => 'https://schema.org',
			'@graph'   => array_values( array_filter( $graph ) ),
		);

		echo '<script type="application/ld+json">' . "\n";
		echo wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
		echo "\n" . '</script>' . "\n";
	}

	private static function organization(): array {
		$site = home_url( '/' );
		return array(
			'@type'        => 'TravelAgency',
			'@id'          => $site . '#organization',
			'name'         => 'Oomph Travel',
			'legalName'    => 'Oomph Travel LLC',
			'url'          => $site,
			'logo'         => array(
				'@type'      => 'ImageObject',
				'@id'        => $site . '#logo',
				'url'        => $site . 'wp-content/uploads/2026/05/Original-Logo-Symbol.png',
				'contentUrl' => $site . 'wp-content/uploads/2026/05/Original-Logo-Symbol.png',
				'width'      => 1604,
				'height'     => 1671,
				'caption'    => 'Oomph Travel',
			),
			'image'        => array( '@id' => $site . '#logo' ),
			'description'  => 'Premium and luxury cruises, and custom European journeys, planned by one named advisor.',
			'slogan'       => 'Life is short — travel with Oomph.',
			'priceRange'   => '$$$$',
			'areaServed'   => array( '@type' => 'Country', 'name' => 'United States' ),
			'address'      => array(
				'@type'           => 'PostalAddress',
				'addressLocality' => 'Port Angeles',
				'addressRegion'   => 'WA',
				'addressCountry'  => 'US',
			),
			'contactPoint' => array(
				'@type'             => 'ContactPoint',
				'contactType'       => 'Customer Service',
				'email'             => 'hello@oomphtravel.com',
				'telephone'         => '+1-360-775-4644',
				'areaServed'        => 'US',
				'availableLanguage' => 'English',
			),
			'knowsAbout' => array(
				'Luxury cruises',
				'Silversea Cruises',
				'Custom European travel',
				'Multi-generational travel',
				'Italy travel planning',
				'United Kingdom travel planning',
			),
		);
	}

	private static function person(): array {
		$site = home_url( '/' );
		return array(
			'@type'         => 'Person',
			'@id'           => $site . 'about/#advisor',
			'name'          => 'Eric Hempel',
			'jobTitle'      => 'Travel Advisor',
			'url'           => $site . 'about/',
			'worksFor'      => array( '@id' => $site . '#organization' ),
			'memberOf'      => array(
				array( '@type' => 'Organization', 'name' => 'Cruise Lines International Association', 'url' => 'https://cruising.org' ),
				array( '@type' => 'Organization', 'name' => 'Nexion Travel Group', 'url' => 'https://nexion.com' ),
			),
			'hasCredential' => array(
				array(
					'@type'              => 'EducationalOccupationalCredential',
					'credentialCategory' => 'certification',
					'name'               => 'Silversea Ultra-Luxury Specialist',
					'recognizedBy'       => array( '@type' => 'Organization', 'name' => 'Silversea Cruises' ),
				),
				array(
					'@type'              => 'EducationalOccupationalCredential',
					'credentialCategory' => 'certification',
					'name'               => 'BritAgent Pro',
					'recognizedBy'       => array( '@type' => 'Organization', 'name' => 'VisitBritain' ),
				),
			),
			'knowsLanguage' => 'en',
			'knowsAbout'    => array( 'Silversea Cruises', 'Italy travel', 'United Kingdom travel', 'Multi-generational travel' ),
		);
	}

	private static function breadcrumb(): array {
		$items = array(
			array(
				'@type'    => 'ListItem',
				'position' => 1,
				'name'     => 'Home',
				'item'     => home_url( '/' ),
			),
		);

		if ( is_singular() && ! is_front_page() ) {
			$post = get_post();
			if ( $post ) {
				$items[] = array(
					'@type'    => 'ListItem',
					'position' => 2,
					'name'     => get_the_title( $post ),
					'item'     => (string) get_permalink( $post ),
				);
			}
		}

		return array(
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $items,
		);
	}

	private static function is_service_page(): bool {
		if ( ! is_page() ) {
			return false;
		}
		return get_page_template_slug() === 'service-page.php';
	}

	private static function service_for_current_page(): array {
		$page    = get_post();
		$url     = (string) get_permalink( $page );
		$keyword = function_exists( 'get_field' ) ? (string) get_field( 'service_keyword' ) : '';

		// Fall back to page title if ACF service_keyword is empty.
		if ( $keyword === '' && $page ) {
			$keyword = (string) get_the_title( $page );
		}

		return array(
			'@type'       => 'Service',
			'@id'         => $url . '#service',
			'name'        => $keyword !== '' ? $keyword : 'Travel planning',
			'serviceType' => $keyword !== '' ? $keyword : 'Travel planning',
			'provider'    => array( '@id' => home_url( '/' ) . '#organization' ),
			'areaServed'  => array( '@type' => 'Country', 'name' => 'United States' ),
			'audience'    => array(
				'@type'        => 'Audience',
				'audienceType' => 'High-net-worth individuals',
			),
			'url'         => $url,
		);
	}

	private static function faqpage_for_current_page(): ?array {
		// Only emit FAQPage when ACF has real Q&A pairs. Template fallbacks
		// are presentational only — never claimed in structured data.
		$faqs = function_exists( 'get_field' ) ? get_field( 'service_faqs' ) : null;
		if ( ! is_array( $faqs ) || empty( $faqs ) ) {
			return null;
		}

		$main_entity = array();
		foreach ( $faqs as $row ) {
			$q = is_array( $row ) ? (string) ( $row['question'] ?? '' ) : '';
			$a = is_array( $row ) ? (string) ( $row['answer'] ?? '' ) : '';
			if ( $q === '' || $a === '' ) {
				continue;
			}
			$main_entity[] = array(
				'@type'          => 'Question',
				'name'           => $q,
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => wp_strip_all_tags( $a ),
				),
			);
		}

		if ( empty( $main_entity ) ) {
			return null;
		}

		return array(
			'@type'      => 'FAQPage',
			'@id'        => (string) get_permalink( get_post() ) . '#faq',
			'mainEntity' => $main_entity,
		);
	}

	private static function event_for_current_post(): ?array {
		$post = get_post();
		if ( ! $post ) {
			return null;
		}

		// Event details come from custom fields once we wire ACF (Phase 10.12).
		// Until then, output a minimal Event with the title + URL so the
		// schema validates structurally; we backfill real dates later.
		return array(
			'@type'       => 'Event',
			'@id'         => get_permalink( $post ) . '#event',
			'name'        => get_the_title( $post ),
			'description' => wp_strip_all_tags( (string) get_the_excerpt( $post ) ),
			'url'         => (string) get_permalink( $post ),
			'organizer'   => array( '@id' => home_url( '/' ) . '#organization' ),
		);
	}
}
