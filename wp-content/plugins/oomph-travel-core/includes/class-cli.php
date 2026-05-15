<?php
/**
 * WP-CLI command: wp oomph status
 *
 * Reports environment, active theme, plugin version. Used as a smoke check
 * after deploys ("wp @stage oomph status" should return staging).
 *
 * @package OomphTravel\Core
 */

declare( strict_types=1 );

namespace OomphTravel\Core;

if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

final class CLI {

	/**
	 * Print environment + active theme + plugin version.
	 *
	 * ## EXAMPLES
	 *
	 *     wp oomph status
	 */
	public function status(): void {
		$active = wp_get_theme();
		$rows   = array(
			array( 'key' => 'Environment',    'value' => Environment::type() ),
			array( 'key' => 'Active theme',   'value' => $active->get( 'Name' ) . ' (' . $active->get_stylesheet() . ')' ),
			array( 'key' => 'Theme version',  'value' => (string) $active->get( 'Version' ) ),
			array( 'key' => 'Parent theme',   'value' => $active->parent() ? $active->parent()->get( 'Name' ) : '—' ),
			array( 'key' => 'Plugin version', 'value' => OOMPH_CORE_VERSION ),
			array( 'key' => 'Home URL',       'value' => (string) home_url( '/' ) ),
		);

		\WP_CLI\Utils\format_items( 'table', $rows, array( 'key', 'value' ) );
	}
}

\WP_CLI::add_command( 'oomph', CLI::class );
