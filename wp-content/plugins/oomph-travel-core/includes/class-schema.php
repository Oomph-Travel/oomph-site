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

	/**
	 * Wire schema output and defend the plugin's role as the single source of
	 * structured data.
	 *
	 * See docs/schema-audit-2026-05-26.md: Rank Math's Schema module is disabled
	 * today, but its settings are pre-configured to emit a duplicate
	 * Person / Organization / Article graph the moment that module is toggled on
	 * (dashboard or setup wizard). Emptying the rank_math/json_ld graph
	 * unconditionally keeps oomph-travel-core the sole source of JSON-LD
	 * regardless of that UI toggle. Registered at plugin load, so it is in place
	 * before wp_head fires whether or not the module is active.
	 */
	public static function init(): void {
		add_action( 'wp_head', array( __CLASS__, 'output' ), 5 );
		add_filter( 'rank_math/json_ld', '__return_empty_array', 99 );
	}

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

		if ( is_singular( 'post' ) ) {
			$graph[] = self::article();
		}

		if ( self::is_service_page() ) {
			$graph[] = self::service_for_current_page();
			$faqpage = self::faqpage_for_current_page();
			if ( $faqpage ) {
				$graph[] = $faqpage;
			}
		}

		// Client stories: attach Review[] + AggregateRating to the org via a
		// second TravelAgency node with the same @id (Google merges by @id).
		// Source of truth for testimonials is the theme's
		// oomph_client_testimonials filter, so on-page and schema can't drift.
		if ( is_page( 'client-stories' ) ) {
			$reviews_node = self::client_stories_reviews();
			if ( $reviews_node ) {
				$graph[] = $reviews_node;
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
		if ( get_page_template_slug() === 'service-page.php' ) {
			return true;
		}
		// Code-managed service hubs (dedicated page-{slug}.php templates).
		$post = get_post();
		$slug = $post ? $post->post_name : '';
		$slugs = apply_filters( 'oomph_service_page_slugs', array( 'custom-italy-travel', 'multi-generational-travel-planning' ) );
		return in_array( $slug, $slugs, true );
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
		// Only emit FAQPage when there are real Q&A pairs. ACF-driven service
		// pages use get_field; code-managed pages supply theirs via the
		// oomph_page_faqs filter. Template fallbacks are never claimed here.
		$faqs = function_exists( 'get_field' ) ? get_field( 'service_faqs' ) : null;
		if ( ! is_array( $faqs ) || empty( $faqs ) ) {
			$faqs = apply_filters( 'oomph_page_faqs', array(), get_post() );
		}
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

	/**
	 * Event schema for a single oomph_cruise, driven by the Group Cruise ACF
	 * fields. Every property is emitted only when its source field is real:
	 *
	 *   • Distinctive Voyages → Event + startDate/endDate + ship-as-Place
	 *     location + one subEvent per populated shore event. NO offers — these
	 *     sailings carry no price data, and availability is never fabricated.
	 *   • Hosted / amenity → Event + offers when a real price exists.
	 *
	 * Booking references are never read here.
	 */
	private static function event_for_current_post(): ?array {
		$post = get_post();
		if ( ! $post ) {
			return null;
		}

		$f = static function ( string $name ) use ( $post ) {
			return function_exists( 'get_field' ) ? get_field( $name, $post->ID ) : null;
		};

		$sailing_type = (string) ( $f( 'sailing_type' ) ?: 'hosted_group' );
		$ship         = trim( (string) $f( 'cruise_ship_name' ) );
		$line         = trim( (string) $f( 'cruise_line' ) );
		$start        = (string) $f( 'cruise_dates_start' );
		$end          = (string) $f( 'cruise_dates_end' );
		$url          = (string) get_permalink( $post );

		$event = array(
			'@type'               => 'Event',
			'@id'                 => $url . '#event',
			'name'                => get_the_title( $post ),
			'url'                 => $url,
			'eventStatus'         => 'https://schema.org/EventScheduled',
			'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
			'organizer'           => array( '@id' => home_url( '/' ) . '#organization' ),
		);

		if ( '' !== $start ) {
			$event['startDate'] = $start;
		}
		if ( '' !== $end ) {
			$event['endDate'] = $end;
		}
		if ( '' !== $ship ) {
			$event['location'] = array(
				'@type' => 'Place',
				'name'  => '' !== $line ? "{$ship} ({$line})" : $ship,
			);
		}

		// Description only from real editorial copy — never the import placeholder.
		$desc = wp_strip_all_tags( (string) get_the_excerpt( $post ) );
		if ( '' !== $desc && false === strpos( $desc, 'WHY THIS SAILING' ) ) {
			$event['description'] = $desc;
		}

		if ( 'distinctive_voyage' === $sailing_type ) {
			$subs = array();
			for ( $n = 1; $n <= 3; $n++ ) {
				$name = trim( (string) $f( "shore_event_{$n}_name" ) );
				if ( '' === $name ) {
					continue;
				}
				$sub  = array(
					'@type' => 'Event',
					'name'  => $name,
				);
				$sdate = (string) $f( "shore_event_{$n}_date" );
				if ( '' !== $sdate ) {
					$sub['startDate'] = $sdate;
				}
				$sloc = trim( (string) $f( "shore_event_{$n}_location" ) );
				if ( '' !== $sloc ) {
					$sub['location'] = array( '@type' => 'Place', 'name' => $sloc );
				}
				$sdet = trim( (string) $f( "shore_event_{$n}_details" ) );
				if ( '' !== $sdet ) {
					$sub['description'] = $sdet;
				}
				$slink = trim( (string) $f( "shore_event_{$n}_link" ) ); // Flyer — also shown on the page.
				if ( '' !== $slink ) {
					$sub['url'] = $slink;
				}
				$subs[] = $sub;
			}
			if ( ! empty( $subs ) ) {
				$event['subEvent'] = $subs;
			}
			// No offers for Distinctive Voyages — price data does not exist.
			return $event;
		}

		// Hosted / amenity — attach offers only when a real price is set.
		$price = $f( 'cruise_price_per_person' );
		if ( is_numeric( $price ) && (float) $price > 0 ) {
			$event['offers'] = array(
				'@type'         => 'Offer',
				'price'         => (string) ( (float) $price ),
				'priceCurrency' => 'USD',
				'availability'  => 'https://schema.org/InStock',
				'url'           => $url,
			);
		}

		return $event;
	}

	/**
	 * Reviews + AggregateRating for /client-stories/.
	 *
	 * Returns a partial TravelAgency node carrying review[] + aggregateRating,
	 * sharing the #organization @id with self::organization() so Google merges
	 * them into a single org entity. Data comes from the theme's
	 * `oomph_client_testimonials` filter (inc/client-stories.php) so on-page
	 * and schema stay in sync (cro-rules R12).
	 *
	 * Returns null if no testimonials are registered, so we never emit empty
	 * aggregate signals.
	 */
	private static function client_stories_reviews(): ?array {
		$rows = apply_filters( 'oomph_client_testimonials', array() );
		if ( ! is_array( $rows ) || empty( $rows ) ) {
			return null;
		}

		$reviews = array();
		$sum     = 0;
		$count   = 0;
		foreach ( $rows as $r ) {
			if ( ! is_array( $r ) ) {
				continue;
			}
			$body = (string) ( $r['body'] ?? '' );
			$auth = (string) ( $r['author'] ?? '' );
			if ( $body === '' || $auth === '' ) {
				continue;
			}
			$rating = max( 1, min( 5, (int) ( $r['rating'] ?? 5 ) ) );
			$sum   += $rating;
			$count++;
			$reviews[] = array(
				'@type'         => 'Review',
				'reviewRating'  => array(
					'@type'       => 'Rating',
					'ratingValue' => (string) $rating,
					'bestRating'  => '5',
				),
				'author'        => array(
					'@type' => 'Person',
					'name'  => $auth,
				),
				'datePublished' => (string) ( $r['date_pub'] ?? '' ),
				'name'          => (string) ( $r['title'] ?? '' ),
				'reviewBody'    => $body,
			);
		}

		if ( $count === 0 ) {
			return null;
		}

		return array(
			'@type'           => 'TravelAgency',
			'@id'             => home_url( '/' ) . '#organization',
			'aggregateRating' => array(
				'@type'       => 'AggregateRating',
				'ratingValue' => number_format( $sum / $count, 1, '.', '' ),
				'reviewCount' => (string) $count,
				'bestRating'  => '5',
			),
			'review'          => $reviews,
		);
	}

	private static function article(): array {
		$post = get_post();
		$site = home_url( '/' );
		$url  = (string) get_permalink( $post );

		$article = array(
			'@type'            => 'BlogPosting',
			'@id'              => $url . '#article',
			'mainEntityOfPage' => $url,
			'headline'         => get_the_title( $post ),
			'description'      => wp_strip_all_tags( (string) get_the_excerpt( $post ) ),
			'author'           => array( '@id' => $site . 'about/#advisor' ),
			'publisher'        => array( '@id' => $site . '#organization' ),
			'datePublished'    => get_the_date( 'c', $post ),
			'dateModified'     => get_the_modified_date( 'c', $post ),
			'inLanguage'       => 'en-US',
		);

		$image = get_the_post_thumbnail_url( $post, 'large' );
		if ( $image ) {
			$article['image'] = $image;
		}

		$cats = get_the_category( $post->ID );
		if ( $cats ) {
			$article['articleSection'] = $cats[0]->name;
		}

		return $article;
	}
}
