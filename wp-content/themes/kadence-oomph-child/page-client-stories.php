<?php
/**
 * Client Stories page.
 *
 * Bound by slug to /client-stories/. Content + markup come from
 * inc/client-stories.php (data-driven, version-controlled). Review +
 * AggregateRating JSON-LD is emitted by the oomph-travel-core plugin,
 * which reads the same data via the `oomph_client_testimonials` filter
 * so on-page and schema stay in sync.
 *
 * @package OomphChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
oomph_render_client_stories();
get_footer();
