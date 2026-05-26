<?php
/**
 * Journal archive — lists all posts as branded cards.
 *
 * Bound by slug to /journal/. Individual posts live at /journal/{slug}/ via the
 * permalink structure (set by scripts/seed-content.php).
 *
 * @package OomphChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$paged = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
$posts_q = new WP_Query( array(
	'post_type'      => 'post',
	'post_status'    => 'publish',
	'posts_per_page' => 9,
	'paged'          => $paged,
) );
?>

<main id="primary" class="oomph-journal" role="main">
	<a class="skip-link sr-only sr-only-focusable" href="#oomph-content">Skip to main content</a>
	<div id="oomph-content"></div>

	<section class="oomph-hero oomph-hero--imaged" aria-labelledby="journal-title">
		<picture class="oomph-hero__media">
			<img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/images/heroes/journal-hero.jpg' ); ?>" alt="" width="1600" height="1067" fetchpriority="high" decoding="async">
		</picture>
		<div class="oomph-container oomph-hero__inner">
			<p class="oomph-eyebrow">From the Journal</p>
			<h1 id="journal-title" class="oomph-hero__headline oomph-italic-display">Field notes from the road and the rail.</h1>
			<p class="oomph-hero__subhead">First-hand notes on cruising and custom European travel — what's worth knowing before you book, from one advisor who sails and travels it himself.</p>
		</div>
	</section>

	<section class="oomph-section" aria-label="Journal posts">
		<div class="oomph-container">
			<?php if ( $posts_q->have_posts() ) : ?>
				<div class="oomph-grid oomph-grid--3">
					<?php
					while ( $posts_q->have_posts() ) :
						$posts_q->the_post();
						$cats = get_the_category();
						?>
						<a class="oomph-card oomph-card--clickable oomph-card--media" href="<?php the_permalink(); ?>">
							<?php if ( has_post_thumbnail() ) : ?>
								<figure class="oomph-card__media"><?php the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy', 'alt' => '' ) ); ?></figure>
							<?php endif; ?>
							<p class="oomph-eyebrow oomph-card__eyebrow"><?php echo esc_html( $cats ? $cats[0]->name : 'Field Note' ); ?></p>
							<h2 class="oomph-card__headline"><?php the_title(); ?></h2>
							<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 28 ) ); ?></p>
							<p class="oomph-card__meta">From the Journal · <?php echo esc_html( get_the_date() ); ?></p>
							<span class="oomph-card__link" aria-hidden="true"></span>
						</a>
					<?php endwhile; ?>
				</div>

				<?php
				$pagination = paginate_links( array(
					'total'   => $posts_q->max_num_pages,
					'current' => $paged,
					'type'    => 'list',
					'prev_text' => '&larr; Newer',
					'next_text' => 'Older &rarr;',
				) );
				if ( $pagination ) {
					echo '<nav class="oomph-pagination" aria-label="Journal pages">' . wp_kses_post( $pagination ) . '</nav>';
				}
				?>
			<?php else : ?>
				<div class="oomph-container--prose">
					<p>The first field notes are on the way. Check back soon — or <a href="/discovery-call/">book a discovery call</a> and we'll talk in person.</p>
				</div>
			<?php endif; ?>
		</div>
	</section>
</main>

<?php
wp_reset_postdata();
get_footer();
