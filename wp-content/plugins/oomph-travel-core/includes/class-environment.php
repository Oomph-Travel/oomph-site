<?php
/**
 * Environment detection.
 *
 * Single source of truth for "is this local / staging / production?"
 * Other classes (Clarity_Guard, future analytics) consume this rather
 * than re-checking `WP_ENVIRONMENT_TYPE` or hostname themselves.
 *
 * @package OomphTravel\Core
 */

declare( strict_types=1 );

namespace OomphTravel\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Environment {

	public static function type(): string {
		// WordPress 5.5+ exposes wp_get_environment_type(). Trust it first.
		if ( function_exists( 'wp_get_environment_type' ) ) {
			$wp_env = wp_get_environment_type();
			if ( in_array( $wp_env, array( 'local', 'development', 'staging', 'production' ), true ) ) {
				return $wp_env;
			}
		}

		// Fallback: derive from home_url() host. SiteGround staging URLs
		// contain "staging" in the hostname; .local / .test / .ddev.site
		// patterns are local; everything else is production.
		$host = wp_parse_url( (string) home_url(), PHP_URL_HOST ) ?: '';

		if ( str_contains( $host, '.local' ) || str_contains( $host, '.test' ) || str_contains( $host, '.ddev.site' ) ) {
			return 'local';
		}
		if ( str_contains( $host, 'staging' ) ) {
			return 'staging';
		}
		return 'production';
	}

	public static function is_production(): bool {
		return 'production' === self::type();
	}

	public static function is_staging(): bool {
		return 'staging' === self::type();
	}

	public static function is_local(): bool {
		return in_array( self::type(), array( 'local', 'development' ), true );
	}
}
