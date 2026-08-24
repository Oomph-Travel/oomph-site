<?php
/**
 * Plainsend — newsletter signup, posted to our own email app.
 *
 * The signup form's markup lives in the child theme, because it is
 * presentation. What lives here is the part that must survive a theme
 * switch and must differ per environment: which app the form posts to,
 * and which form it posts to there.
 *
 * Why not a plugin embed or an iframe. Plainsend serves a hosted page that
 * could be framed, but a frame is a second document with its own stylesheet
 * on a site that has just spent an LCP workstream removing 43 KB of form CSS,
 * and it cannot inherit the brand tokens. Native markup posting across
 * origins costs nothing extra to render and looks like the rest of the page.
 *
 * The form works with JavaScript switched off: it posts and the visitor lands
 * on a thank-you page at Plainsend. The script below is the enhancement that
 * keeps them on oomphtravel.com instead.
 *
 * @package OomphTravel\Core
 */

declare( strict_types=1 );

namespace OomphTravel\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plainsend {

	/** Where the Plainsend app lives. Filterable for a future move. */
	private const APP_URL = 'https://app.plainsend.net';

	public static function init(): void {
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue' ), 20 );
	}

	/**
	 * The Plainsend app's address.
	 */
	public static function app_url(): string {
		return untrailingslashit( (string) apply_filters( 'oomph_plainsend_app_url', self::APP_URL ) );
	}

	/**
	 * Which Plainsend form receives signups from this environment.
	 *
	 * Staging and local post to a separate form so that test signups never
	 * mix into the real mailing list. They are still real submissions against
	 * the real app — that is the point of testing — but they arrive somewhere
	 * that can be emptied without touching a subscriber.
	 */
	public static function form_slug( string $key = 'newsletter' ): string {
		// Staging posts to "<key>-staging", which is a real form in Plainsend
		// rather than a convention it infers. Adding a key here means creating
		// that pair in Plainsend too — see its scripts/create-*.js.
		$slug = Environment::is_production() ? $key : $key . '-staging';

		return (string) apply_filters( 'oomph_plainsend_form_slug', $slug, $key );
	}

	/**
	 * The address a signup form posts to.
	 *
	 * @param string $key Which form — 'newsletter' or 'trends-guide'.
	 */
	public static function endpoint( string $key = 'newsletter' ): string {
		return self::app_url() . '/f/' . self::form_slug( $key );
	}

	/**
	 * The submit script, loaded only where a form actually exists.
	 *
	 * The footer form is sitewide, so this is too — but it is a few hundred
	 * bytes, deferred, and touches nothing until somebody types.
	 */
	public static function enqueue(): void {
		$path = get_stylesheet_directory() . '/assets/js/newsletter.js';
		if ( ! file_exists( $path ) ) {
			return;
		}

		wp_enqueue_script(
			'oomph-newsletter',
			get_stylesheet_directory_uri() . '/assets/js/newsletter.js',
			array(),
			(string) filemtime( $path ),
			true
		);

		wp_localize_script(
			'oomph-newsletter',
			'oomphNewsletter',
			array(
				'endpoint' => self::endpoint(),
				// Named so the GA4 report says where the signup came from
				// rather than merely that one happened (R11).
				'source'   => 'footer',
			)
		);
	}
}
