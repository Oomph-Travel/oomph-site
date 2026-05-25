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

get_header();
oomph_render_service_page( 'multi-generational-travel-planning' );
get_footer();
