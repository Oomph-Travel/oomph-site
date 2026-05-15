<?php
/**
 * Clarity Guard — dequeue Microsoft Clarity unless we're on production.
 *
 * The official Clarity plugin's own environment check is unreliable across
 * local environments. We belt-and-suspenders it by dequeuing anything
 * Clarity registers when Environment::is_production() returns false.
 *
 * @package OomphTravel\Core
 */

declare( strict_types=1 );

namespace OomphTravel\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Clarity_Guard {

	public static function init(): void {
		if ( Environment::is_production() ) {
			return;
		}

		// Dequeue Clarity's front-end script and any inline tags it prints.
		add_action( 'wp_enqueue_scripts', array( self::class, 'dequeue' ), 99 );
		add_action( 'wp_head',            array( self::class, 'strip_inline' ), 1 );
	}

	public static function dequeue(): void {
		// Handles Microsoft's official "Microsoft Clarity" plugin uses.
		foreach ( array( 'clarity-tracker', 'microsoft-clarity', 'ms-clarity' ) as $handle ) {
			wp_dequeue_script( $handle );
			wp_deregister_script( $handle );
		}
	}

	public static function strip_inline(): void {
		// If the Clarity plugin echoes inline JS via wp_head directly, we
		// can't dequeue it. Output buffering at priority 1 + a filter on
		// the buffered HTML would be heavy-handed; instead, we rely on
		// the plugin's own environment check + this dequeue as defense in
		// depth, and document the requirement to deactivate Clarity in
		// non-prod environments (BUILD-PLAN.md §5.4).
	}
}
