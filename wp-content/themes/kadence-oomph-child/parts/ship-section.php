<?php
/**
 * Ship section — shared by both single-oomph_cruise layouts.
 *
 * Renders the Ship Library record matching the sailing's ship name:
 * intro (ship post_content), quick-facts table, and the photo gallery band.
 * Renders nothing when no ship record exists — a sailing page never breaks
 * on an un-seeded ship.
 *
 * Usage:
 *   get_template_part( 'parts/ship-section', null, array( 'ship_name' => $ship ) );
 *
 * Images: gallery is below the fold by definition — every image lazy-loads
 * with explicit width/height (R4). The Ship Library ACF group caps the
 * gallery at 12 and instructs licensed-media-only (R20 applies to heroes;
 * ship galleries use supplier media Eric is licensed to run).
 *
 * @package OomphChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ship_name = isset( $args['ship_name'] ) ? trim( (string) $args['ship_name'] ) : '';
if ( '' === $ship_name || ! class_exists( '\OomphTravel\Core\CPT_Ship' ) ) {
	return;
}

$ship_post = \OomphTravel\Core\CPT_Ship::find_by_name( $ship_name );
if ( ! $ship_post ) {
	return;
}

$ship_get = static function ( string $name ) use ( $ship_post ) {
	return function_exists( 'get_field' ) ? get_field( $name, $ship_post->ID ) : null;
};

$ship_line   = trim( (string) $ship_get( 'ship_line' ) );
$ship_credit = trim( (string) $ship_get( 'ship_photo_credit' ) );
$ship_facts  = $ship_get( 'ship_facts' );
$gallery     = $ship_get( 'ship_gallery' );
$intro       = trim( (string) apply_filters( 'the_content', $ship_post->post_content ) );

$has_facts   = is_array( $ship_facts ) && ! empty( $ship_facts );
$has_gallery = is_array( $gallery ) && ! empty( $gallery );

if ( '' === $intro && ! $has_facts && ! $has_gallery ) {
	return;
}
?>
<section class="oomph-ship" aria-label="About <?php echo esc_attr( $ship_name ); ?>">

	<?php if ( '' !== $intro || $has_facts ) : ?>
		<div class="oomph-ship__head">
			<div class="oomph-ship__intro">
				<p class="oomph-eyebrow"><?php echo esc_html( trim( ( $ship_line ? $ship_line . ' · ' : '' ) . 'The ship' ) ); ?></p>
				<h2>Life on board <?php echo esc_html( $ship_name ); ?>.</h2>
				<?php if ( '' !== $intro ) : ?>
					<div class="oomph-prose"><?php echo wp_kses_post( $intro ); ?></div>
				<?php endif; ?>
			</div>
			<?php if ( $has_facts ) : ?>
				<table class="oomph-ship__facts">
					<caption class="sr-only"><?php echo esc_html( $ship_name ); ?> quick facts</caption>
					<tbody>
						<?php foreach ( $ship_facts as $fact ) : ?>
							<?php
							$f_label = is_array( $fact ) ? trim( (string) ( $fact['fact_label'] ?? '' ) ) : '';
							$f_value = is_array( $fact ) ? trim( (string) ( $fact['fact_value'] ?? '' ) ) : '';
							if ( '' === $f_label || '' === $f_value ) {
								continue;
							}
							?>
							<tr><th scope="row"><?php echo esc_html( $f_label ); ?></th><td><?php echo esc_html( $f_value ); ?></td></tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( $has_gallery ) : ?>
		<div class="oomph-ship__gallery" role="list">
			<?php foreach ( $gallery as $img ) : ?>
				<?php
				if ( ! is_array( $img ) || empty( $img['sizes']['large'] ) ) {
					continue;
				}
				$src = (string) $img['sizes']['large'];
				$w   = (int) ( $img['sizes']['large-width'] ?? 1024 );
				$h   = (int) ( $img['sizes']['large-height'] ?? 683 );
				$alt = trim( (string) ( $img['alt'] ?? '' ) );
				?>
				<figure class="oomph-ship__photo" role="listitem">
					<img src="<?php echo esc_url( $src ); ?>" alt="<?php echo esc_attr( $alt ?: $ship_name ); ?>" width="<?php echo esc_attr( (string) $w ); ?>" height="<?php echo esc_attr( (string) $h ); ?>" loading="lazy" decoding="async">
					<?php if ( ! empty( $img['caption'] ) ) : ?>
						<figcaption><?php echo esc_html( (string) $img['caption'] ); ?></figcaption>
					<?php endif; ?>
				</figure>
			<?php endforeach; ?>
		</div>
		<?php if ( '' !== $ship_credit ) : ?>
			<p class="oomph-ship__credit"><?php echo esc_html( $ship_credit ); ?></p>
		<?php endif; ?>
	<?php endif; ?>

</section>
