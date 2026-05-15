<?php
/**
 * ACF Pro configuration — redirect JSON sync into the plugin.
 *
 * By default ACF saves field groups to the database and (optionally)
 * to acf-json/ in the active theme. We want them in the plugin
 * because:
 *   1. The fields describe data that lives on plugin-registered CPTs
 *      (Destination, Itinerary, Cruise) — they belong with the CPT
 *      definitions, not with the theme.
 *   2. Switching themes shouldn't orphan field group definitions.
 *
 * Both save and load paths are redirected to /plugin-root/acf-json/.
 *
 * @package OomphTravel\Core
 */

declare( strict_types=1 );

namespace OomphTravel\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ACF_Config {

	public static function init(): void {
		add_filter( 'acf/settings/save_json', array( self::class, 'save_path' ) );
		add_filter( 'acf/settings/load_json', array( self::class, 'load_paths' ) );
	}

	public static function save_path(): string {
		return OOMPH_CORE_DIR . 'acf-json';
	}

	/**
	 * @param string[] $paths
	 * @return string[]
	 */
	public static function load_paths( array $paths ): array {
		$paths[] = OOMPH_CORE_DIR . 'acf-json';
		return $paths;
	}
}
