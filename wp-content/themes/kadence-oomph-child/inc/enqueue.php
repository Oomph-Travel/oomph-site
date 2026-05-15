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
