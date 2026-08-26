<?php
/**
 * Sticky mobile CTA — R2. Visible at every scroll depth, mobile only
 * (CSS hides it above the breakpoint; see .oomph-sticky-cta in
 * assets/css/components.css).
 *
 * Usage:
 *   get_template_part( 'parts/sticky-cta' );
 *   get_template_part( 'parts/sticky-cta', null, array( 'href' => '#book' ) );
 *
 * The label is fixed: "Start a conversation →" is the primary CTA copy
 * (voice-guide.md — that exact phrasing, with the arrow). Only the
 * destination varies (R62 — changing the copy needs Eric's approval).
 *
 * @package OomphChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$oomph_sticky_href = isset( $args['href'] ) ? (string) $args['href'] : '/discovery-call/';
?>
<aside class="oomph-sticky-cta" aria-label="Quick contact">
	<a class="oomph-btn oomph-btn--primary" href="<?php echo esc_url( $oomph_sticky_href ); ?>">
		Start a conversation <span aria-hidden="true">&rarr;</span>
	</a>
</aside>
