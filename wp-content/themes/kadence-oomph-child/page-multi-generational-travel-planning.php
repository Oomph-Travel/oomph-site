<?php
/**
 * Multi-Generational Travel Planning service page.
 *
 * Bound by slug to /multi-generational-travel-planning/. Content + markup come
 * from the shared data-driven renderer in inc/service-pages.php. Service +
 * FAQPage schema is emitted by the oomph-travel-core plugin (recognizes this
 * slug).
 *
 * @package OomphChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Before get_header() — the renderer runs after wp_head has already fired,
// so the hero preload has to be registered from out here.
oomph_preload_service_hero( 'multi-generational-travel-planning' );

get_header();
oomph_render_service_page( 'multi-generational-travel-planning' );
get_footer();
