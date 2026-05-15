<?php
/**
 * Kadence parent-theme overrides for the Oomph child theme.
 *
 * Two responsibilities:
 *   1. Register block styles (Gutenberg side panel pickers) so editors
 *      can apply the Brand Book's signature surfaces with a single click.
 *   2. Body classes for hero pages so the header can flip to transparent
 *      / inverse on pages that lead with full-bleed photography.
 *
 * CSS for these styles lives in assets/css/components.css under
 * .is-style-oomph-* selectors.
 *
 * @package OomphChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register block styles for Gutenberg.
 *
 * Each style adds a class .is-style-{name} to the rendered block.
 * Pair these with rules in components.css.
 *
 * @return void
 */
function oomph_child_register_block_styles(): void {
	// core/paragraph variants.
	register_block_style(
		'core/paragraph',
		array(
			'name'  => 'oomph-lede',
			'label' => __( 'Lede', 'oomph-travel-child' ),
		)
	);
	register_block_style(
		'core/paragraph',
		array(
			'name'  => 'oomph-meta',
			'label' => __( 'Meta', 'oomph-travel-child' ),
		)
	);

	// core/heading — editorial pull-quote.
	register_block_style(
		'core/heading',
		array(
			'name'  => 'oomph-pullquote',
			'label' => __( 'Pull-quote', 'oomph-travel-child' ),
		)
	);

	// core/group — surface variants from the Brand Book signature combinations.
	register_block_style(
		'core/group',
		array(
			'name'  => 'oomph-cabin-notes',
			'label' => __( 'Cabin Notes (inverse)', 'oomph-travel-child' ),
		)
	);
	register_block_style(
		'core/group',
		array(
			'name'  => 'oomph-quiet-premium',
			'label' => __( 'Quiet Premium (Paper)', 'oomph-travel-child' ),
		)
	);
	register_block_style(
		'core/group',
		array(
			'name'  => 'oomph-european-itinerary',
			'label' => __( 'European Itinerary', 'oomph-travel-child' ),
		)
	);
}
add_action( 'init', 'oomph_child_register_block_styles' );

/**
 * Add a body class for pages whose hero takes the whole viewport.
 *
 * The header CSS flips to transparent + inverse-logo on these pages.
 * For now, just the home page and About; we wire ACF or a page-meta
 * toggle later if we need fine control.
 *
 * @param string[] $classes Existing body classes.
 * @return string[]
 */
function oomph_child_hero_body_class( array $classes ): array {
	if ( is_front_page() || is_page( 'about' ) ) {
		$classes[] = 'oomph-hero-page';
	}
	return $classes;
}
add_filter( 'body_class', 'oomph_child_hero_body_class' );
