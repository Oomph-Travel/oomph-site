<?php
/**
 * Stylesheet and script enqueue for the Oomph Travel child theme.
 *
 * Dependency chain (front-end):
 *   kadence-global  →  oomph-tokens  →  oomph-travel-child  (= style.css)
 *
 * Tokens are also enqueued inside the block editor so Gutenberg renders
 * with the same palette, type scale, and spacing as the front-end.
 *
 * @package OomphChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front-end stylesheets.
 *
 * Hooks at priority 20 so Kadence's parent handles (kadence-global etc.)
 * are already registered. filemtime() cache-busts on every file save —
 * better DX than bumping a constant.
 *
 * @return void
 */
function oomph_child_enqueue_styles(): void {
	$theme_dir = get_stylesheet_directory();
	$theme_uri = get_stylesheet_directory_uri();

	// Tokens — CSS custom properties + @font-face.
	$tokens_path = $theme_dir . '/assets/css/tokens.css';
	if ( file_exists( $tokens_path ) ) {
		wp_enqueue_style(
			'oomph-tokens',
			$theme_uri . '/assets/css/tokens.css',
			array( 'kadence-global' ),
			(string) filemtime( $tokens_path )
		);
	}

	// Base — reset, focus, prose, selection, skip link.
	$base_path = $theme_dir . '/assets/css/base.css';
	if ( file_exists( $base_path ) ) {
		wp_enqueue_style(
			'oomph-base',
			$theme_uri . '/assets/css/base.css',
			array( 'oomph-tokens' ),
			(string) filemtime( $base_path )
		);
	}

	// Components — buttons, cards, eyebrows, trust strip, sticky CTA, fields.
	$components_path = $theme_dir . '/assets/css/components.css';
	if ( file_exists( $components_path ) ) {
		wp_enqueue_style(
			'oomph-components',
			$theme_uri . '/assets/css/components.css',
			array( 'oomph-base' ),
			(string) filemtime( $components_path )
		);
	}

	// Child theme style.css — load last so any page-specific overrides win.
	wp_enqueue_style(
		'oomph-travel-child',
		get_stylesheet_uri(),
		array( 'oomph-components' ),
		(string) filemtime( $theme_dir . '/style.css' )
	);
}
add_action( 'wp_enqueue_scripts', 'oomph_child_enqueue_styles', 20 );

/**
 * Resource hints — font preload + third-party preconnect.
 *
 * Both fix things the 2026-07-25 LCP measurement turned up.
 *
 * **Font preload.** The @font-face rules live inside the combined stylesheet,
 * so the browser can't discover a font until that CSS has downloaded AND
 * parsed — measured at 4.5–5.3s FCP on throttled mobile. Only Inter is
 * preloaded: it's the body face, so it renders every above-the-fold text
 * element on every template, including `.oomph-dv-hero__ship`, which is the
 * actual LCP element on /group-cruises/. Fraunces is deliberately NOT
 * preloaded — it only sets headlines, `font-display: swap` paints those in the
 * fallback immediately, and preloading another ~100KB would compete with the
 * hero image for bandwidth on the pages where the hero *is* the LCP.
 *
 * `crossorigin` is required even though the font is same-origin: fonts are
 * always fetched in CORS mode, and a preload without it is fetched twice.
 *
 * **Preconnect.** Lighthouse measured ~450ms of connection setup to the
 * analytics origins. Clarity is production-only (see Clarity_Guard), so its
 * hints are gated the same way rather than costing staging a wasted handshake.
 *
 * @return void
 */
function oomph_child_resource_hints(): void {
	$font = get_stylesheet_directory() . '/assets/fonts/inter-variable-latin.woff2';
	if ( file_exists( $font ) ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( get_stylesheet_directory_uri() . '/assets/fonts/inter-variable-latin.woff2' )
		);
	}

	$origins = array( 'https://www.googletagmanager.com' );

	$is_production = ! class_exists( '\OomphTravel\Core\Environment' )
		|| \OomphTravel\Core\Environment::is_production();
	if ( $is_production ) {
		$origins[] = 'https://scripts.clarity.ms';
		$origins[] = 'https://o.clarity.ms';
	}

	foreach ( $origins as $origin ) {
		printf( '<link rel="preconnect" href="%s" crossorigin>' . "\n", esc_url( $origin ) );
	}
}
add_action( 'wp_head', 'oomph_child_resource_hints', 1 );

/**
 * Group Cruise single stylesheet — only on single oomph_cruise views.
 *
 * @return void
 */
function oomph_child_enqueue_cruise(): void {
	if ( ! is_singular( 'oomph_cruise' ) && ! is_post_type_archive( 'oomph_cruise' ) ) {
		return;
	}
	$path = get_stylesheet_directory() . '/assets/css/dv-sailing.css';
	if ( file_exists( $path ) ) {
		wp_enqueue_style(
			'oomph-dv-sailing',
			get_stylesheet_directory_uri() . '/assets/css/dv-sailing.css',
			array( 'oomph-components' ),
			(string) filemtime( $path )
		);
	}
}
add_action( 'wp_enqueue_scripts', 'oomph_child_enqueue_cruise', 20 );

/**
 * Force light color-scheme on cruise singles.
 *
 * The DV and hosted layouts use deep navy surfaces with light type; a mobile
 * browser's automatic dark mode would otherwise recolour them and break
 * contrast. Emitted in <head> because a template can't reach it after the fact.
 *
 * @return void
 */
function oomph_child_cruise_color_scheme(): void {
	if ( is_singular( 'oomph_cruise' ) || is_post_type_archive( 'oomph_cruise' ) ) {
		echo '<meta name="color-scheme" content="light only">' . "\n";
	}
}
add_action( 'wp_head', 'oomph_child_cruise_color_scheme', 1 );

/**
 * Cabin quiz script — only on the /trip-quiz/ page.
 *
 * @return void
 */
function oomph_child_enqueue_quiz(): void {
	if ( ! is_page( 'trip-quiz' ) ) {
		return;
	}
	$path = get_stylesheet_directory() . '/assets/js/cabin-quiz.js';
	if ( file_exists( $path ) ) {
		wp_enqueue_script(
			'oomph-cabin-quiz',
			get_stylesheet_directory_uri() . '/assets/js/cabin-quiz.js',
			array( 'jquery' ),
			(string) filemtime( $path ),
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'oomph_child_enqueue_quiz', 20 );

/**
 * Block editor stylesheets.
 *
 * Mirrors tokens.css into the editor so Gutenberg's preview matches the
 * rendered front-end. The classic `add_editor_style()` helper resolves
 * paths relative to the theme dir.
 *
 * @return void
 */
function oomph_child_enqueue_editor_styles(): void {
	add_editor_style( 'assets/css/tokens.css' );
	add_editor_style( 'assets/css/base.css' );
	add_editor_style( 'assets/css/components.css' );
}
add_action( 'after_setup_theme', 'oomph_child_enqueue_editor_styles' );

/**
 * Strip WordPress's emoji scripts and styles.
 *
 * Saves ~12KB and 2 HTTP requests on every page — measurable CWV win.
 *
 * @return void
 */
function oomph_child_disable_emojis(): void {
	remove_action( 'wp_head',             'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles',     'print_emoji_styles' );
	remove_action( 'admin_print_styles',  'print_emoji_styles' );
	remove_filter( 'the_content_feed',    'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss',    'wp_staticize_emoji' );
	remove_filter( 'wp_mail',             'wp_staticize_emoji_for_email' );
}
add_action( 'init', 'oomph_child_disable_emojis' );
