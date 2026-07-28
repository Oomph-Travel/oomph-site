<?php
/**
 * Link-in-bio page — /links/
 *
 * The single destination the Instagram profile link points at. Instagram
 * allows one clickable link and captions are not clickable, so this page
 * stands in for "whatever I posted about today" and never has to change.
 *
 * Everything on it is generated, not hand-maintained:
 *
 *   • The featured card is the most recent published Journal post. Publish a
 *     post and this page updates itself — nothing to swap after a post goes
 *     live. Override with the `oomph_links_featured_post_id` filter to pin a
 *     different post (e.g. an evergreen one during a quiet month).
 *   • The Group Cruises row counts live sailings, so the label reads "three
 *     sailings open now" and self-corrects when the monthly import retires a
 *     departed one.
 *
 * One primary CTA (the discovery call) per CLAUDE.md; everything else is a
 * quiet row. Deep ground because the page is read on a phone in a feed
 * context, and a dark card stack is legible in sunlight where Bone is not.
 *
 * Indexing: noindex,follow by default. The page duplicates the navigation and
 * has no content of its own to rank — it exists for one referrer. Flip it with
 * `add_filter( 'oomph_links_noindex', '__return_false' )`.
 *
 * @package OomphChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Robots. Runs before get_header() so it lands in wp_head. Rank Math writes
 * its own robots tag, so it gets the same instruction through its own filter.
 */
if ( (bool) apply_filters( 'oomph_links_noindex', true ) ) {
	add_filter(
		'wp_robots',
		static function ( array $robots ): array {
			$robots['noindex'] = true;
			$robots['follow']  = true;
			unset( $robots['max-image-preview'], $robots['max-snippet'], $robots['max-video-preview'] );
			return $robots;
		},
		99
	);
	add_filter(
		'rank_math/frontend/robots',
		static function (): array {
			return array( 'noindex', 'follow' );
		},
		99
	);
}

// Automatic dark-mode recolouring would wreck a deep-ground page (same reason
// the cruise singles pin this). Emitted here because wp_head has not run yet.
add_action(
	'wp_head',
	static function (): void {
		echo '<meta name="color-scheme" content="light only">' . "\n";
	},
	1
);

get_header();

/* -------------------------------------------------------------------------
 * Featured Journal post — latest published, or a pinned override.
 * ---------------------------------------------------------------------- */
$featured_id = (int) apply_filters( 'oomph_links_featured_post_id', 0 );

if ( ! $featured_id ) {
	$latest = new WP_Query(
		array(
			'post_type'              => 'post',
			'post_status'            => 'publish',
			'posts_per_page'         => 1,
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
		)
	);
	$featured_id = $latest->have_posts() ? (int) $latest->posts[0]->ID : 0;
}

$featured = $featured_id ? get_post( $featured_id ) : null;
if ( $featured && 'publish' !== $featured->post_status ) {
	$featured = null;
}

/* -------------------------------------------------------------------------
 * Live sailing count for the Group Cruises row.
 * ---------------------------------------------------------------------- */
/*
 * Count through the archive's own helper, not a bare oomph_cruise query. The
 * archive is shaped by oomph_cruise_archive_query(), which only fires on the
 * main query for the post-type archive — a WP_Query here is exempt, so a bare
 * count picks up every sailing ever published, departed ones included, and the
 * row contradicts /group-cruises/.
 *
 * no_found_rows is forced back on: the helper disables it (nothing else needs a
 * total), but found_posts is the whole point here.
 */
$sailings = new WP_Query(
	oomph_sailings_query_args(
		array(
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	)
);
$sailing_count = (int) $sailings->found_posts;

$number_words  = array( 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine' );
$sailings_meta = 'The sailings I’m hosting';
if ( $sailing_count > 0 ) {
	$word          = $number_words[ $sailing_count ] ?? (string) $sailing_count;
	$sailings_meta = sprintf(
		/* translators: %1$s: number of sailings as a word, %2$s: "sailing" or "sailings". */
		'%1$s %2$s open right now',
		$word,
		1 === $sailing_count ? 'sailing' : 'sailings'
	);
}

/* -------------------------------------------------------------------------
 * The quiet rows. Order is deliberate: lowest commitment first.
 * ---------------------------------------------------------------------- */
$rows = array(
	array(
		'url'   => '/trip-quiz/',
		'label' => 'Which cabin actually suits you?',
		'meta'  => 'Seven questions, about two minutes',
	),
	array(
		'url'   => '/group-cruises/',
		'label' => 'Group cruises',
		'meta'  => $sailings_meta,
	),
	array(
		'url'   => '/cruise-travel-trends/',
		'label' => 'Cruise Trends guide',
		'meta'  => 'What’s changing at sea, and what it means for your next booking',
	),
	array(
		'url'   => '/journal/',
		'label' => 'The Journal',
		'meta'  => 'Every post, oldest to newest',
	),
);

/* Secondary text links — the rest of the site, without the weight of a row. */
$more = array(
	'About Eric'             => '/about/',
	'Luxury cruise planning' => '/luxury-cruise-planning/',
	'Custom Italy'           => '/custom-italy-travel/',
	'Multi-generational'     => '/multi-generational-travel-planning/',
	'Client stories'         => '/client-stories/',
);
?>

<main id="primary" class="oomph-links" role="main">

	<a class="skip-link sr-only sr-only-focusable" href="#oomph-content">Skip to main content</a>
	<div id="oomph-content"></div>

	<div class="oomph-links__inner">

		<?php /* 1. WHO ---------------------------------------------------- */ ?>
		<header class="oomph-links__intro">
			<p class="oomph-eyebrow">Oomph Travel &middot; Port Angeles, WA</p>
			<h1 class="oomph-links__title oomph-italic-display">Everything, in one place.</h1>
			<p class="oomph-links__standfirst">I’m Eric Hempel. I plan premium cruises and custom European journeys for people who’d rather not spend their evenings comparing deck plans. Start anywhere below.</p>
		</header>

		<?php /* 2. FEATURED JOURNAL POST ------------------------------------ */ ?>
		<?php if ( $featured ) : ?>
			<?php
			$permalink = get_permalink( $featured );
			$thumb_id  = (int) get_post_thumbnail_id( $featured );
			$excerpt   = wp_trim_words( get_the_excerpt( $featured ), 26, '&hellip;' );
			?>
			<article class="oomph-links__feature<?php echo $thumb_id ? ' has-media' : ''; ?>">
				<?php if ( $thumb_id ) : ?>
					<div class="oomph-links__feature-media">
						<?php
						/*
						 * The LCP element on this page — eager, high priority, explicit
						 * dimensions (R3, R4). Everything below the fold stays lazy.
						 */
						echo wp_get_attachment_image(
							$thumb_id,
							'medium_large',
							false,
							array(
								'class'         => 'oomph-links__feature-img',
								'alt'           => esc_attr( (string) get_post_meta( $thumb_id, '_wp_attachment_image_alt', true ) ),
								'loading'       => 'eager',
								'fetchpriority' => 'high',
								'decoding'      => 'async',
							)
						);
						?>
					</div>
				<?php endif; ?>
				<div class="oomph-links__feature-body">
					<p class="oomph-eyebrow">Newest in the Journal</p>
					<h2 class="oomph-links__feature-title">
						<a class="oomph-links__feature-link" href="<?php echo esc_url( (string) $permalink ); ?>"><?php echo esc_html( get_the_title( $featured ) ); ?></a>
					</h2>
					<?php if ( $excerpt ) : ?>
						<p class="oomph-links__feature-excerpt"><?php echo esc_html( $excerpt ); ?></p>
					<?php endif; ?>
					<p class="oomph-links__feature-more">
						<span class="oomph-links__feature-cue">Read it <span aria-hidden="true">&rarr;</span></span>
						<span class="oomph-links__feature-date"><?php echo esc_html( get_the_date( 'F j, Y', $featured ) ); ?></span>
					</p>
				</div>
			</article>
		<?php endif; ?>

		<?php /* 3. THE ONE PRIMARY CTA --------------------------------------- */ ?>
		<div class="oomph-links__cta">
			<a class="oomph-btn oomph-btn--primary" href="/discovery-call/">
				Start a conversation <span aria-hidden="true">&rarr;</span>
			</a>
			<span class="oomph-btn-microcopy">Email, text, or a quick call &mdash; whatever&rsquo;s easiest for you.</span>
		</div>

		<?php /* 4. QUIET ROWS ------------------------------------------------ */ ?>
		<nav class="oomph-links__rows" aria-label="More from Oomph Travel">
			<?php foreach ( $rows as $row ) : ?>
				<a class="oomph-links__row" href="<?php echo esc_url( $row['url'] ); ?>">
					<span class="oomph-links__row-text">
						<span class="oomph-links__row-label"><?php echo esc_html( $row['label'] ); ?></span>
						<span class="oomph-links__row-meta"><?php echo esc_html( $row['meta'] ); ?></span>
					</span>
					<span class="oomph-links__row-arrow" aria-hidden="true">&rarr;</span>
				</a>
			<?php endforeach; ?>
		</nav>

		<?php /* 5. THE REST OF THE SITE -------------------------------------- */ ?>
		<?php
		/*
		 * No middot separators between these: the row wraps to two or three
		 * lines on a phone, and a separator element is its own flex item, so
		 * it strands itself at the end of a wrapped line. Gap does the same
		 * job without the artifact.
		 */
		?>
		<nav class="oomph-links__more" aria-label="Elsewhere on the site">
			<?php foreach ( $more as $label => $url ) : ?>
				<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</nav>

		<p class="oomph-links__foot">CLIA Member &middot; Silversea Ultra-Luxury Specialist &middot; Nexion Affiliated</p>

	</div>
</main>

<?php /* Sticky mobile CTA — R2. Same destination as the button above, so the
       call is reachable from any scroll depth on a phone. */ ?>
<aside class="oomph-sticky-cta" aria-label="Quick contact">
	<a class="oomph-btn oomph-btn--primary" href="/discovery-call/">
		Start a conversation <span aria-hidden="true">&rarr;</span>
	</a>
</aside>

<?php
get_footer();
