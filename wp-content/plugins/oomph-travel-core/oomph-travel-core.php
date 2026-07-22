<?php
/**
 * Plugin Name:       Oomph Travel Core
 * Plugin URI:        https://oomphtravel.com
 * Description:       Data layer for the Oomph Travel rebuild — custom post types, taxonomies, schema injection, environment guards. Presentation belongs in the child theme; this lives in a plugin so it survives a theme switch.
 * Version:           1.0.0
 * Requires PHP:      8.1
 * Requires at least: 6.7
 * Tested up to:      6.8
 * Author:            Oomph Travel LLC
 * Author URI:        https://oomphtravel.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       oomph-travel-core
 * Domain Path:       /languages
 *
 * @package OomphTravel\Core
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'OOMPH_CORE_VERSION', '1.0.0' );
define( 'OOMPH_CORE_FILE',    __FILE__ );
define( 'OOMPH_CORE_DIR',     plugin_dir_path( __FILE__ ) );
define( 'OOMPH_CORE_URI',     plugin_dir_url( __FILE__ ) );

// Classes — autoload-free, explicit requires keep the boot order obvious.
require_once OOMPH_CORE_DIR . 'includes/class-environment.php';
require_once OOMPH_CORE_DIR . 'includes/class-cpt-destination.php';
require_once OOMPH_CORE_DIR . 'includes/class-cpt-itinerary.php';
require_once OOMPH_CORE_DIR . 'includes/class-cpt-cruise.php';
require_once OOMPH_CORE_DIR . 'includes/class-cpt-ship.php';
require_once OOMPH_CORE_DIR . 'includes/class-taxonomies.php';
require_once OOMPH_CORE_DIR . 'includes/class-schema.php';
require_once OOMPH_CORE_DIR . 'includes/class-clarity-guard.php';
require_once OOMPH_CORE_DIR . 'includes/class-acf-config.php';
require_once OOMPH_CORE_DIR . 'includes/class-seo.php';
require_once OOMPH_CORE_DIR . 'includes/class-xlsx.php';
require_once OOMPH_CORE_DIR . 'includes/class-importer.php'; // engine — shared by CLI + admin.

if ( is_admin() ) {
	require_once OOMPH_CORE_DIR . 'includes/class-admin-import.php';
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once OOMPH_CORE_DIR . 'includes/class-cli.php';
	require_once OOMPH_CORE_DIR . 'includes/class-enrich.php';
}

// Boot.
add_action( 'init', array( \OomphTravel\Core\CPT_Destination::class, 'register' ) );
add_action( 'init', array( \OomphTravel\Core\CPT_Itinerary::class,   'register' ) );
add_action( 'init', array( \OomphTravel\Core\CPT_Cruise::class,      'register' ) );
add_action( 'init', array( \OomphTravel\Core\CPT_Ship::class,        'register' ) );
add_action( 'init', array( \OomphTravel\Core\Taxonomies::class,      'register' ) );

\OomphTravel\Core\CPT_Cruise::init();
\OomphTravel\Core\CPT_Ship::init();
\OomphTravel\Core\Schema::init();
\OomphTravel\Core\Clarity_Guard::init();
\OomphTravel\Core\ACF_Config::init();
\OomphTravel\Core\SEO::init();

if ( is_admin() ) {
	\OomphTravel\Core\Admin_Import::init();
}

/**
 * Activation — flush rewrite rules so CPT slugs resolve immediately.
 */
register_activation_hook( __FILE__, function (): void {
	\OomphTravel\Core\CPT_Destination::register();
	\OomphTravel\Core\CPT_Itinerary::register();
	\OomphTravel\Core\CPT_Cruise::register();
	\OomphTravel\Core\CPT_Ship::register();
	\OomphTravel\Core\Taxonomies::register();
	flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
