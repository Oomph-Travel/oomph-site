<?php
/**
 * Client Stories page — testimonial data + renderer.
 *
 * Content lives here as a per-card data array (version-controlled, no DB/ACF
 * dependency). `oomph_render_client_stories()` renders the markup; the thin
 * page-client-stories.php template just calls it. Schema is single-sourced
 * by the oomph-travel-core plugin via the `oomph_client_testimonials` filter
 * registered below, so the Review + AggregateRating JSON-LD matches what's
 * visible on the page exactly (cro-rules R12: never mark up content not on
 * the page).
 *
 * Testimonial integrity (cro-rules R31): each entry uses the reviewer's
 * actual posted nickname + their location, the trip type, and the review
 * date — no fabrication. Quote bodies are verbatim.
 *
 * @package OomphChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The testimonials, ordered newest first (by review date).
 * Single source for both on-page rendering and JSON-LD schema.
 *
 * Fields per row:
 *   author    string  Displayed attribution (reviewer's posted nickname)
 *   location  string  City, ST
 *   trip      string  Short trip line — line/destination/year
 *   date_pub  string  Review date (YYYY-MM-DD) for schema datePublished
 *   rating    int     1–5
 *   title     string  Review title (auto-prefixes like "5 Stars –" stripped)
 *   body      string  Review body, verbatim
 */
function oomph_client_testimonials(): array {
	return array(
		array(
			'author'   => 'LCMurray',
			'location' => 'Port Angeles, WA',
			'trip'     => 'Cruise · Mexican Riviera · March 2025',
			'date_pub' => '2025-04-21',
			'rating'   => 5,
			'title'    => 'Amazing Cruise and Fantastic Service!',
			'body'     => 'I recently went on a cruise to the Mexican Riviera in March, and it was absolutely wonderful! From the moment we booked, our travel agent made the entire process smooth and stress-free. Communication was excellent — every detail was clearly laid out in our itinerary, and everything went exactly as planned. The cruise itself was incredible. Our room had a balcony with a stunning view, and it was both comfortable and beautifully maintained. The restaurants onboard were top-notch with a wide variety of delicious food, and the entertainment options kept us busy and smiling every night. We truly had an unforgettable experience, and I can\'t thank our travel agent enough for making it all so easy. Highly recommend!',
		),
		array(
			'author'   => 'travellover52',
			'location' => 'Port Angeles, WA',
			'trip'     => 'Cruise + Miami pre/post-stays',
			'date_pub' => '2025-04-20',
			'rating'   => 5,
			'title'    => 'Responsive, thoughtful, and detail oriented!',
			'body'     => 'We couldn\'t be more thrilled with the incredible travel experience Eric curated for us. While the cruise itself was a blast, the highlights of our trip were actually the moments he planned before we set sail and after we returned. Eric arranged for us to fly into Miami and stay at a charming boutique hotel, perfectly located in the heart of vibrant Miami Beach. From evening strolls to dining out and soaking in the local atmosphere, everything felt effortless and exciting. On the return side of our journey, Eric once again delivered. He secured a lovely hotel situated near a lively boardwalk filled with unique shops, cozy cafés, and inviting restaurants — an ideal setting to unwind and transition back to everyday life. What truly sets Eric apart is his eye for detail and his ability to tailor experiences that reflect who we are. His thoughtful planning and personal touches made this vacation not only seamless but unforgettable. We\'re already looking forward to booking our next adventure.',
		),
		array(
			'author'   => 'Ak Chris',
			'location' => 'Port Angeles, WA',
			'trip'     => 'Norwegian Cruise Line · 4 cruises + airfare (repeat client)',
			'date_pub' => '2025-04-19',
			'rating'   => 5,
			'title'    => 'NCL Cruze, mistake',
			'body'     => 'On our last cruise we ended up having a problem getting our travel documents from NCL. With them changing the rewards system, for some reason our docs did not show. Eric spent time with me on the phone and then 45 minutes with NCL getting it corrected. With that kind of customer service and not just dropping us off to NCL customer support gave us the feeling he cared and went the extra mile to help. We have used Eric for the last 4 cruses and airfare. Any problems that arose he got corrected quickly. Thank you and until next time.',
		),
		array(
			'author'   => 'Gary T.',
			'location' => 'Poulsbo, WA',
			'trip'     => 'Oceania Cruises',
			'date_pub' => '2025-04-18',
			'rating'   => 5,
			'title'    => 'Great Cruise Experience',
			'body'     => 'We had an absolutely fantastic cruise experience with Oceania Cruises, all thanks to Eric Hempel at Oomph Travel. From start to finish, Eric\'s planning was spot-on — every detail was handled with care, and his knowledge of cruise travel really showed. What stood out most was his outstanding communication; he was always available to answer questions and kept us informed every step of the way. His expertise and personal touch made the entire process easy and stress-free. We\'ll definitely be working with Eric again for our future adventures!',
		),
	);
}

/**
 * Expose the testimonials to the plugin's schema layer.
 * The plugin reads this filter when emitting Review + AggregateRating on /client-stories/.
 */
add_filter( 'oomph_client_testimonials', 'oomph_client_testimonials', 10, 0 );

/**
 * Render N filled stars + (5 - N) empty stars, accessibly.
 */
function oomph_render_rating( int $n ): string {
	$n = max( 0, min( 5, $n ) );
	$out  = '<span class="oomph-testimonial__rating" aria-label="' . esc_attr( $n ) . ' out of 5 stars">';
	$out .= str_repeat( '<span class="oomph-testimonial__star is-filled" aria-hidden="true">&#9733;</span>', $n );
	$out .= str_repeat( '<span class="oomph-testimonial__star" aria-hidden="true">&#9734;</span>', 5 - $n );
	$out .= '</span>';
	return $out;
}

/**
 * Render the full /client-stories/ page.
 */
function oomph_render_client_stories(): void {
	$rows         = oomph_client_testimonials();
	$last_updated = date_i18n( 'F Y' );
	?>
	<main id="primary" class="oomph-service oomph-client-stories" role="main">
		<a class="skip-link sr-only sr-only-focusable" href="#oomph-content">Skip to main content</a>
		<div id="oomph-content"></div>

		<?php /* 1. HERO — Quiet Premium, text-only */ ?>
		<section class="oomph-section oomph-hero is-style-oomph-quiet-premium" aria-label="Client stories">
			<div class="oomph-container oomph-hero__inner">
				<p class="oomph-eyebrow">FIELD NOTES &middot; FROM CLIENTS</p>
				<h1 class="oomph-hero__headline oomph-italic-display">Stories from clients I&rsquo;ve planned for.</h1>
				<p class="oomph-hero__subhead">Real reviews &mdash; names and trip details as the clients posted them. Mostly Pacific Northwest travelers, mostly cruise. The list grows as more come in.</p>
				<p class="oomph-hero__cta">
					<a class="oomph-btn oomph-btn--primary" href="/discovery-call/">Start a conversation <span aria-hidden="true">&rarr;</span></a>
					<span class="oomph-btn-microcopy">Email, text, or a quick call — whatever's easiest for you.</span>
				</p>
			</div>
		</section>

		<?php /* 2. TRUST STRIP */ ?>
		<aside class="oomph-trust-strip" aria-label="Credentials">
			<span class="oomph-trust-strip__item">CLIA Member</span>
			<span class="oomph-trust-strip__item">Silversea Ultra-Luxury Specialist</span>
			<span class="oomph-trust-strip__item">Nexion Affiliated</span>
			<span class="oomph-trust-strip__item">BritAgent Pro</span>
			<span class="oomph-trust-strip__item">Port Angeles &middot; WA</span>
		</aside>

		<?php /* 3. TESTIMONIALS GRID */ ?>
		<section class="oomph-section" aria-labelledby="testimonials-title">
			<div class="oomph-container">
				<div class="oomph-section__intro">
					<p class="oomph-eyebrow">What clients say</p>
					<h2 id="testimonials-title">What are clients actually saying about working with me?</h2>
				</div>
				<div class="oomph-grid oomph-grid--testimonials">
					<?php foreach ( $rows as $r ) : ?>
						<article class="oomph-testimonial" itemscope itemtype="https://schema.org/Review">
							<?php echo oomph_render_rating( (int) $r['rating'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<h3 class="oomph-testimonial__title oomph-italic-display"><?php echo esc_html( $r['title'] ); ?></h3>
							<blockquote class="oomph-testimonial__body">
								<p><?php echo esc_html( $r['body'] ); ?></p>
							</blockquote>
							<footer class="oomph-testimonial__meta">
								<p class="oomph-testimonial__author"><strong><?php echo esc_html( $r['author'] ); ?></strong> &middot; <?php echo esc_html( $r['location'] ); ?></p>
								<p class="oomph-testimonial__trip"><?php echo esc_html( $r['trip'] ); ?> &middot; <time datetime="<?php echo esc_attr( $r['date_pub'] ); ?>"><?php echo esc_html( date_i18n( 'F Y', strtotime( $r['date_pub'] ) ) ); ?></time></p>
							</footer>
						</article>
					<?php endforeach; ?>
				</div>
				<p class="oomph-section__cta" style="text-align: center; margin-top: var(--space-7);">
					<a class="oomph-btn oomph-btn--primary" href="/discovery-call/">Start a conversation <span aria-hidden="true">&rarr;</span></a>
				</p>
			</div>
		</section>

		<?php /* 4. HONEST FRAMER */ ?>
		<section class="oomph-section is-style-oomph-quiet-premium" aria-label="About this page">
			<div class="oomph-container oomph-container--prose">
				<p>More are on the way as past clients post. If you&rsquo;ve traveled with me and want to add a few sentences, send them my way &mdash; or post wherever you found me. Names and details stay exactly as you write them.</p>
				<p class="oomph-meta"><em>Last updated <?php echo esc_html( $last_updated ); ?>.</em></p>
			</div>
		</section>

		<?php /* 5. FINAL CTA — Editorial Inversion */ ?>
		<section class="oomph-section is-style-oomph-cabin-notes" aria-labelledby="final-cta-title">
			<div class="oomph-container" style="text-align: center;">
				<p class="oomph-eyebrow" style="color: var(--color-champagne);">First call</p>
				<h2 id="final-cta-title" class="oomph-italic-display" style="font-size: var(--text-h1); max-width: 22ch; margin-inline: auto;">Want a trip you can review like that?</h2>
				<p style="margin-top: var(--space-6);">
					<a class="oomph-btn oomph-btn--inverse" href="/discovery-call/">Start a conversation <span aria-hidden="true">&rarr;</span></a>
					<span class="oomph-btn-microcopy" style="color: var(--color-champagne);">Email, text, or a quick call — whatever's easiest for you.</span>
				</p>
			</div>
		</section>
	</main>

	<aside class="oomph-sticky-cta" aria-label="Quick contact">
		<a class="oomph-btn oomph-btn--primary" href="/discovery-call/">Start a conversation <span aria-hidden="true">&rarr;</span></a>
	</aside>
	<?php
}
