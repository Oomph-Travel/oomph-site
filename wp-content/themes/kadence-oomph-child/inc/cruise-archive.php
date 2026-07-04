<?php
/**
 * Group Cruise archive — query shaping, shared card renderer, filter options.
 *
 * The /group-cruises/ archive (archive-oomph_cruise.php) lists published,
 * upcoming sailings soonest-first, filterable by cruise line and region.
 * The same card renderer feeds the homepage and Luxury Cruise Planning
 * entry-point sections so a sailing looks the same wherever it appears.
 *
 * Only PUBLISHED sailings surface anywhere — drafts (the import default)
 * stay private until Eric writes their "Why this sailing" copy.
 *
 * @package OomphChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Human label for a sailing_type value.
 */
function oomph_sailing_type_label( string $type ): string {
	switch ( $type ) {
		case 'distinctive_voyage':
			return 'Distinctive Voyages';
		case 'amenity_departure':
			return 'Amenity Departure';
		default:
			return 'Hosted Group Cruise';
	}
}

/**
 * Format a sail/return pair as a compact date range ("July 16–23, 2026").
 */
function oomph_sailing_date_range( string $start, string $end ): string {
	if ( $start && $end ) {
		$s = strtotime( $start );
		$e = strtotime( $end );
		if ( gmdate( 'Y-m', $s ) === gmdate( 'Y-m', $e ) ) {
			return date_i18n( 'F j', $s ) . '–' . date_i18n( 'j, Y', $e );
		}
		return date_i18n( 'M j', $s ) . ' – ' . date_i18n( 'M j, Y', $e );
	}
	return $start ? date_i18n( 'F j, Y', strtotime( $start ) ) : '';
}

/**
 * Base WP_Query args for published, upcoming sailings, soonest first.
 *
 * @param array<string,mixed> $extra Merged over the defaults.
 * @return array<string,mixed>
 */
function oomph_sailings_query_args( array $extra = array() ): array {
	$args = array(
		'post_type'      => 'oomph_cruise',
		'post_status'    => 'publish',
		'posts_per_page' => 12,
		'meta_query'     => array(
			'upcoming' => array(
				'key'     => 'cruise_dates_start',
				'value'   => current_time( 'Y-m-d' ),
				'compare' => '>=',
				'type'    => 'DATE',
			),
		),
		'orderby'        => array( 'upcoming' => 'ASC' ),
		'no_found_rows'  => true,
	);
	return array_merge( $args, $extra );
}

/**
 * Are there any published, upcoming sailings? Gates the nav/footer/homepage
 * entry points so we never link to an empty archive.
 */
function oomph_has_published_sailings(): bool {
	static $has = null;
	if ( null !== $has ) {
		return $has;
	}
	$q   = new WP_Query( oomph_sailings_query_args( array( 'posts_per_page' => 1, 'fields' => 'ids' ) ) );
	$has = $q->have_posts();
	return $has;
}

/**
 * The next N upcoming published sailings (for the homepage / cruise-page teasers).
 *
 * @return int[] Post IDs.
 */
function oomph_get_upcoming_sailings( int $limit = 3 ): array {
	$q = new WP_Query( oomph_sailings_query_args( array( 'posts_per_page' => $limit, 'fields' => 'ids' ) ) );
	return array_map( 'intval', $q->posts );
}

/**
 * Distinct cruise lines among published upcoming sailings (filter dropdown).
 *
 * @return string[]
 */
function oomph_sailing_lines(): array {
	global $wpdb;
	$today = current_time( 'Y-m-d' );
	// Lines that have at least one published, upcoming sailing.
	$rows = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT DISTINCT line.meta_value
			 FROM {$wpdb->postmeta} line
			 JOIN {$wpdb->posts} p ON p.ID = line.post_id
			 JOIN {$wpdb->postmeta} d ON d.post_id = p.ID AND d.meta_key = 'cruise_dates_start'
			 WHERE line.meta_key = 'cruise_line' AND line.meta_value <> ''
			   AND p.post_type = 'oomph_cruise' AND p.post_status = 'publish'
			   AND d.meta_value >= %s
			 ORDER BY line.meta_value",
			$today
		)
	);
	return array_map( 'strval', $rows );
}

/**
 * Region terms actually used by published upcoming sailings (filter dropdown).
 *
 * @return array<int,object> Rows with ->name and ->slug.
 */
function oomph_sailing_regions(): array {
	global $wpdb;
	$today = current_time( 'Y-m-d' );
	return $wpdb->get_results(
		$wpdb->prepare(
			"SELECT DISTINCT t.name, t.slug
			 FROM {$wpdb->terms} t
			 JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = t.term_id AND tt.taxonomy = 'oomph_region'
			 JOIN {$wpdb->term_relationships} tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
			 JOIN {$wpdb->posts} p ON p.ID = tr.object_id
			 JOIN {$wpdb->postmeta} d ON d.post_id = p.ID AND d.meta_key = 'cruise_dates_start'
			 WHERE p.post_type = 'oomph_cruise' AND p.post_status = 'publish' AND d.meta_value >= %s
			 ORDER BY t.name",
			$today
		)
	);
}

/**
 * Shape the archive's main query: upcoming only, soonest first, GET filters,
 * paginated. Ordering by the named 'upcoming' meta clause avoids meta_key /
 * meta_query JOIN ambiguity.
 */
function oomph_cruise_archive_query( WP_Query $q ): void {
	if ( is_admin() || ! $q->is_main_query() || ! $q->is_post_type_archive( 'oomph_cruise' ) ) {
		return;
	}

	$meta_query = array(
		'upcoming' => array(
			'key'     => 'cruise_dates_start',
			'value'   => current_time( 'Y-m-d' ),
			'compare' => '>=',
			'type'    => 'DATE',
		),
	);

	$line = isset( $_GET['line'] ) ? sanitize_text_field( wp_unslash( $_GET['line'] ) ) : '';
	if ( '' !== $line ) {
		$meta_query[] = array(
			'key'   => 'cruise_line',
			'value' => $line,
		);
	}

	$q->set( 'posts_per_page', 12 );
	$q->set( 'meta_query', $meta_query );
	$q->set( 'orderby', array( 'upcoming' => 'ASC' ) );

	$region = isset( $_GET['region'] ) ? sanitize_title( wp_unslash( $_GET['region'] ) ) : '';
	if ( '' !== $region ) {
		$q->set(
			'tax_query',
			array(
				array(
					'taxonomy' => 'oomph_region',
					'field'    => 'slug',
					'terms'    => $region,
				),
			)
		);
	}
}
add_action( 'pre_get_posts', 'oomph_cruise_archive_query' );

/**
 * Hide the "Group Cruises" nav item (menu-item class `oomph-nav-sailings`) when
 * there are no published sailings, so the menu never links to an empty archive.
 * The item lives in the menu permanently (seeded); this gates it at render time,
 * consistent with the footer/homepage/cruise-page entry points.
 *
 * @param array<int,object> $items
 * @return array<int,object>
 */
function oomph_filter_sailings_nav_item( array $items ): array {
	if ( oomph_has_published_sailings() ) {
		return $items;
	}
	foreach ( $items as $k => $it ) {
		if ( isset( $it->classes ) && is_array( $it->classes ) && in_array( 'oomph-nav-sailings', $it->classes, true ) ) {
			unset( $items[ $k ] );
		}
	}
	return $items;
}
add_filter( 'wp_nav_menu_objects', 'oomph_filter_sailings_nav_item', 10, 1 );

/**
 * Render one sailing card. Shared by the archive, homepage, and cruise page so
 * the card is identical everywhere. Whole card is clickable (no "Read more").
 */
function oomph_render_sailing_card( $post ): void {
	$id = is_object( $post ) ? (int) $post->ID : (int) $post;
	$f  = static function ( string $name ) use ( $id ) {
		return function_exists( 'get_field' ) ? get_field( $name, $id ) : null;
	};

	$type   = (string) ( $f( 'sailing_type' ) ?: 'hosted_group' );
	$ship   = trim( (string) $f( 'cruise_ship_name' ) );
	$line   = trim( (string) $f( 'cruise_line' ) );
	$start  = (string) $f( 'cruise_dates_start' );
	$end    = (string) $f( 'cruise_dates_end' );
	$range  = oomph_sailing_date_range( $start, $end );
	$event  = trim( (string) $f( 'shore_event_1_name' ) );

	$terms  = get_the_terms( $id, 'oomph_region' );
	$region = ( is_array( $terms ) && $terms ) ? $terms[0]->name : '';

	// Itinerary label lives in the title ("{Itinerary} — {Ship}, {F Y}").
	$title = get_the_title( $id );
	$bits  = explode( ' — ', $title );
	$itin  = count( $bits ) > 1 ? $bits[0] : $title;

	$eyebrow = oomph_sailing_type_label( $type ) . ( $region ? ' · ' . $region : '' );
	$meta    = implode( ' · ', array_filter( array( $ship, $line, $range ) ) );
	?>
	<a class="oomph-card oomph-card--clickable oomph-sailing-card" href="<?php echo esc_url( (string) get_permalink( $id ) ); ?>">
		<p class="oomph-eyebrow oomph-card__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
		<h3 class="oomph-card__headline"><?php echo esc_html( $itin ); ?></h3>
		<?php if ( 'distinctive_voyage' === $type && '' !== $event ) : ?>
			<p class="oomph-sailing-card__event">Exclusive shore event: <?php echo esc_html( $event ); ?></p>
		<?php endif; ?>
		<?php if ( '' !== $meta ) : ?>
			<p class="oomph-card__meta"><?php echo esc_html( $meta ); ?></p>
		<?php endif; ?>
		<span class="oomph-card__link" aria-hidden="true"></span>
	</a>
	<?php
}
