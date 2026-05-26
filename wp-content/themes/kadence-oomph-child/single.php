<?php
/**
 * Single journal post.
 *
 * Branded post layout: byline linked to /about/ (R13), TL;DR lead from the
 * excerpt (R14), visible published + updated dates (R15), constrained prose,
 * and three related posts. BlogPosting schema is emitted by the
 * oomph-travel-core plugin.
 *
 * @package OomphChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	$published = get_the_date();
	$modified  = get_the_modified_date();
	$cats      = get_the_category();
	$eyebrow   = $cats ? $cats[0]->name : 'From the Journal';
	?>

	<main id="primary" class="oomph-post" role="main">
		<a class="skip-link sr-only sr-only-focusable" href="#oomph-content">Skip to main content</a>
		<div id="oomph-content"></div>

		<article <?php post_class( 'oomph-post__article' ); ?>>

			<header class="oomph-section oomph-post__header">
				<div class="oomph-container oomph-container--prose">
					<p class="oomph-eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
					<h1 class="oomph-post__title oomph-italic-display"><?php the_title(); ?></h1>
					<p class="oomph-post__byline">
						By <a href="/about/" rel="author">Eric Hempel</a>
						<span aria-hidden="true">·</span>
						<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( $published ); ?></time>
						<?php if ( $modified !== $published ) : ?>
							<span class="oomph-post__updated"><span aria-hidden="true">·</span> Updated <time datetime="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>"><?php echo esc_html( $modified ); ?></time></span>
						<?php endif; ?>
					</p>
				</div>
			</header>

			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="oomph-post__feature">
					<?php the_post_thumbnail( 'large', array( 'loading' => 'eager', 'fetchpriority' => 'high' ) ); ?>
				</figure>
			<?php endif; ?>

			<div class="oomph-section oomph-post__body">
				<div class="oomph-container oomph-container--prose">
					<?php if ( has_excerpt() ) : ?>
						<p class="oomph-post__tldr"><strong>The short version:</strong> <?php echo esc_html( get_the_excerpt() ); ?></p>
					<?php endif; ?>

					<div class="oomph-prose">
						<?php the_content(); ?>
					</div>

					<p class="oomph-post__cta">
						<a class="oomph-btn oomph-btn--primary" href="/discovery-call/">Book a Discovery Call <span aria-hidden="true">&rarr;</span></a>
						<span class="oomph-btn-microcopy">Free 30-minute call. No pressure, no obligation.</span>
					</p>
				</div>
			</div>

			<?php
			$related = get_posts( array(
				'numberposts'  => 3,
				'post_status'  => 'publish',
				'post__not_in' => array( get_the_ID() ),
				'orderby'      => 'date',
			) );
			if ( $related ) :
				?>
				<section class="oomph-section oomph-post__related" aria-labelledby="related-title">
					<div class="oomph-container">
						<div class="oomph-section__intro">
							<p class="oomph-eyebrow">Keep reading</p>
							<h2 id="related-title">More from the journal.</h2>
						</div>
						<div class="oomph-grid oomph-grid--3">
							<?php foreach ( $related as $post ) : setup_postdata( $post ); ?>
								<a class="oomph-card oomph-card--clickable" href="<?php the_permalink(); ?>">
									<p class="oomph-eyebrow oomph-card__eyebrow">Field Note</p>
									<h3 class="oomph-card__headline"><?php the_title(); ?></h3>
									<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 24 ) ); ?></p>
									<p class="oomph-card__meta">From the Journal · <?php echo esc_html( get_the_date() ); ?></p>
									<span class="oomph-card__link" aria-hidden="true"></span>
								</a>
							<?php endforeach; wp_reset_postdata(); ?>
						</div>
					</div>
				</section>
			<?php endif; ?>

		</article>
	</main>

	<aside class="oomph-sticky-cta" aria-label="Quick contact">
		<a class="oomph-btn oomph-btn--primary" href="/discovery-call/">Book a Discovery Call <span aria-hidden="true">&rarr;</span></a>
	</aside>

	<?php
endwhile;

get_footer();
