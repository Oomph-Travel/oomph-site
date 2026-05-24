<?php
/**
 * Oomph Child — functions.
 *
 * @package OomphChild
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'OOMPH_CHILD_VERSION', '1.0.0' );
define( 'OOMPH_CHILD_PATH', get_stylesheet_directory() );
define( 'OOMPH_CHILD_URI',  get_stylesheet_directory_uri() );

require_once OOMPH_CHILD_PATH . '/inc/enqueue.php';
require_once OOMPH_CHILD_PATH . '/inc/schema.php';
require_once OOMPH_CHILD_PATH . '/inc/kadence-overrides.php';
require_once OOMPH_CHILD_PATH . '/inc/block-patterns.php';
require_once OOMPH_CHILD_PATH . '/inc/helpers.php';

// Calendly booking link for the Discovery Call page inline embed.
add_filter(
	'oomph_calendly_url',
	static function () {
		return 'https://calendly.com/eric-oomphtravel/30min';
	}
);
