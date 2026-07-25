<?php
/**
 * Home page template.
 *
 * Per docx §10.3 — eleven sections from hero to final CTA. The FEES
 * TEASER (section 9) is currently deferred — see placeholder comment
 * where it lived. Primary CTA "Start a conversation →" appears in
 * hero, after the founder bio, in the lead magnet, and in the final
 * CTA block (four placements + the sticky mobile bar = five surfaces
 * total).
 *
 * Schema (Organization + Person + BreadcrumbList) is injected by the
 * oomph-travel-core plugin via wp_head, so we don't output it here.
 *
 * Image placeholders are wrapped in <figure class="is-placeholder">.
 * Replace these with Eric's original photography (per R20) — no stock.
 *
 * @package OomphChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Hero image is resolved BEFORE get_header() so oomph_preload_hero() can add
 * its <link rel=preload> while wp_head is still ahead of us. Move this below
 * get_header() and the preload silently stops emitting.
 *
 * ACF returns array(ID, url, alt, width, height, sizes, …) or false when no
 * image is selected; fall back to the bundled theme asset.
 */
$hero_image_acf     = function_exists( 'get_field' ) ? get_field( 'hero_image' ) : null;
$hero_image_bundled = '';
$hero_image_srcset  = '';

if ( is_array( $hero_image_acf ) && ! empty( $hero_image_acf['url'] ) ) {
	$hero_image_url    = $hero_image_acf['url'];
	$hero_image_alt    = $hero_image_acf['alt'] ?? '';
	$hero_image_width  = $hero_image_acf['width'] ?? 1000;
	$hero_image_height = $hero_image_acf['height'] ?? 667;

	// An uploaded hero goes through the Media Library, so WordPress already
	// has the resized files — but this template hand-builds its <img> from
	// ['url'], which is always the full-size original. Ask WP for the srcset
	// explicitly, otherwise a 3000px upload ships whole to a phone.
	if ( ! empty( $hero_image_acf['ID'] ) ) {
		$hero_image_srcset = (string) wp_get_attachment_image_srcset( (int) $hero_image_acf['ID'], 'full' );
	}
} else {
	$hero_image_bundled = 'hero-background.webp';
	$hero_image_url     = get_stylesheet_directory_uri() . '/assets/images/' . $hero_image_bundled;
	$hero_image_alt     = '';
	$hero_image_width   = 1000;
	$hero_image_height  = 667;
	oomph_preload_hero( $hero_image_bundled );
}

get_header();

/*
 * Hero copy — read from ACF Page Hero with fallbacks to the original
 * hardcoded copy. Fallbacks ensure the page never renders blank
 * if ACF is absent or the post hasn't been populated in admin yet.
 */
$hero_eyebrow   = oomph_acf_field( 'hero_eyebrow', 'Premium Cruises · Custom Europe · Multi-Gen' );
$hero_headline  = oomph_acf_field( 'hero_headline', "Travel that's worth the trip." );
$hero_subhead   = oomph_acf_field(
	'hero_subhead',
	'Premium and luxury cruises, and custom European journeys — planned by one named advisor who stays on your trip from the first call to the last flight home.'
);
$hero_cta_label = oomph_acf_field( 'hero_cta_label', 'Start a conversation →' );
$hero_cta_url   = oomph_acf_field( 'hero_cta_url', '/discovery-call/' );

// Strip a trailing arrow from the label so we can re-append it inside
// an aria-hidden span, keeping the arrow out of the accessible name.
$hero_cta_text = trim( preg_replace( '/\s*→\s*$/u', '', $hero_cta_label ) );

// Trust strip defaults to on; explicit false from ACF hides it.
$show_trust_strip = (bool) oomph_acf_field( 'hero_trust_strip', true );
?>

<main id="primary" class="oomph-home" role="main">

	<a class="skip-link sr-only sr-only-focusable" href="#oomph-content">Skip to main content</a>
	<div id="oomph-content"></div>

	<?php /* 1. HERO ---------------------------------------------------- */ ?>
	<section class="oomph-hero oomph-hero--imaged" aria-label="Welcome">
		<?php if ( $hero_image_bundled ) : ?>
			<?php
			// LCP element. oomph_picture() serves the 480/768/1000 WebP variants
			// instead of the single 180KB original; oomph_preload_hero() above
			// starts the download from <head> with a matching srcset.
			echo oomph_picture(
				$hero_image_bundled,
				array(
					'alt'    => $hero_image_alt,
					'width'  => $hero_image_width,
					'height' => $hero_image_height,
					'class'  => 'oomph-hero__media',
					'lcp'    => true,
				)
			); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — escaped in helper.
			?>
		<?php else : ?>
			<picture class="oomph-hero__media">
				<img
					src="<?php echo esc_url( $hero_image_url ); ?>"
					alt="<?php echo esc_attr( $hero_image_alt ); ?>"
					width="<?php echo esc_attr( $hero_image_width ); ?>"
					height="<?php echo esc_attr( $hero_image_height ); ?>"
					srcset="<?php echo esc_attr( $hero_image_srcset ); ?>"
					sizes="100vw"
					fetchpriority="high"
					decoding="async"
				>
			</picture>
		<?php endif; ?>
		<div class="oomph-container oomph-hero__inner">
			<p class="oomph-eyebrow"><?php echo esc_html( $hero_eyebrow ); ?></p>
			<h1 class="oomph-hero__headline"><?php echo esc_html( $hero_headline ); ?></h1>
			<p class="oomph-hero__subhead"><?php echo esc_html( $hero_subhead ); ?></p>
			<p class="oomph-hero__cta">
				<a class="oomph-btn oomph-btn--primary" href="<?php echo esc_url( $hero_cta_url ); ?>">
					<?php echo esc_html( $hero_cta_text ); ?> <span aria-hidden="true">→</span>
				</a>
				<span class="oomph-btn-microcopy">Email, text, or a quick call — whatever's easiest for you.</span>
			</p>
		</div>
	</section>

	<?php /* 2. TRUST STRIP --------------------------------------------- */ ?>
	<?php if ( $show_trust_strip ) : ?>
	<aside class="oomph-trust-strip" aria-label="Credentials">
		<span class="oomph-trust-strip__item">CLIA Member</span>
		<span class="oomph-trust-strip__item">Silversea Ultra-Luxury Specialist</span>
		<span class="oomph-trust-strip__item">Nexion Affiliated</span>
		<span class="oomph-trust-strip__item">BritAgent Pro</span>
		<span class="oomph-trust-strip__item">Port Angeles · WA</span>
	</aside>
	<?php endif; ?>

	<?php /* 3. WHO I HELP --------------------------------------------- */ ?>
	<section class="oomph-section" aria-labelledby="who-i-help-title">
		<div class="oomph-container">
			<div class="oomph-section__intro">
				<p class="oomph-eyebrow">Who I help</p>
				<h2 id="who-i-help-title">Three kinds of trip, three kinds of traveler.</h2>
			</div>
			<div class="oomph-grid oomph-grid--3">
				<article class="oomph-card">
					<h3 class="oomph-card__headline">Couples planning a milestone.</h3>
					<p>Anniversaries, retirements, the trip you've talked about for ten years. The kind that has to land — choose the cabin, get the dinner reservations right, pace the days so neither of you comes home tired.</p>
				</article>
				<article class="oomph-card">
					<h3 class="oomph-card__headline">Families planning across three generations.</h3>
					<p>The trip planned around the slowest walker and the pickiest eater. Mobility, dietary, room configurations, a plan B for weather — it's not one decision; it's a hundred.</p>
				</article>
				<article class="oomph-card">
					<h3 class="oomph-card__headline">Cruisers planning what's next.</h3>
					<p>You've sailed the mainstream lines and you're curious what ultra-luxury is actually like. I've sailed Silversea fourteen times. I'll tell you what's worth it and what isn't.</p>
				</article>
			</div>
		</div>
	</section>

	<?php /* 4. WHAT I PLAN ------------------------------------------- */ ?>
	<section class="oomph-section is-style-oomph-european-itinerary" aria-labelledby="what-i-plan-title">
		<div class="oomph-container">
			<div class="oomph-section__intro">
				<p class="oomph-eyebrow">What I plan</p>
				<h2 id="what-i-plan-title">Three services. One named advisor.</h2>
			</div>
			<div class="oomph-grid oomph-grid--3">
				<a class="oomph-card oomph-card--clickable oomph-card--media" href="/luxury-cruise-planning/">
					<figure class="oomph-card__media"><?php echo oomph_picture( 'cards/card-cruise.jpg', array( 'width' => 900, 'height' => 600 ) ); ?></figure>
					<h3 class="oomph-card__headline">Luxury cruise planning.</h3>
					<p>Silversea, Regent, Seabourn, Crystal, Cunard Grills, Viking Ocean. Cabin selection by deck and wave-zone, onboard credit, pre- and post-cruise extensions.</p>
					<p class="oomph-card__meta">See cruise planning →</p>
					<span class="oomph-card__link" aria-hidden="true"></span>
				</a>
				<a class="oomph-card oomph-card--clickable oomph-card--media" href="/custom-italy-travel/">
					<figure class="oomph-card__media"><?php echo oomph_picture( 'cards/card-italy.jpg', array( 'width' => 900, 'height' => 600 ) ); ?></figure>
					<h3 class="oomph-card__headline">Custom Italy travel.</h3>
					<p>Hand-built itineraries by region — Puglia, Sicily, the Lakes, the Dolomites. Private drivers, vetted guides, the villa rentals that actually deliver.</p>
					<p class="oomph-card__meta">See custom Italy →</p>
					<span class="oomph-card__link" aria-hidden="true"></span>
				</a>
				<a class="oomph-card oomph-card--clickable oomph-card--media" href="/multi-generational-travel-planning/">
					<figure class="oomph-card__media"><?php echo oomph_picture( 'cards/card-multigen.jpg', array( 'width' => 900, 'height' => 600 ) ); ?></figure>
					<h3 class="oomph-card__headline">Multi-generational travel.</h3>
					<p>The trip that works for grandparents, parents, teens, and the toddler. Pace, mobility, dietary, special-occasion choreography — planned around the slowest walker.</p>
					<p class="oomph-card__meta">See multi-gen planning →</p>
					<span class="oomph-card__link" aria-hidden="true"></span>
				</a>
			</div>
		</div>
	</section>

	<?php /* 4b. SAILINGS I'M HOSTING (only when a sailing is published) -- */ ?>
	<?php if ( function_exists( 'oomph_has_published_sailings' ) && oomph_has_published_sailings() ) : ?>
	<section class="oomph-section" aria-labelledby="home-sailings-title">
		<div class="oomph-container">
			<div class="oomph-section__intro">
				<p class="oomph-eyebrow">Group Cruises · Distinctive Voyages</p>
				<h2 id="home-sailings-title">Sailings I'm hosting.</h2>
			</div>
			<div class="oomph-grid oomph-grid--3">
				<?php foreach ( oomph_get_upcoming_sailings( 3 ) as $sailing_id ) { oomph_render_sailing_card( $sailing_id ); } ?>
			</div>
			<p class="oomph-section__cta" style="text-align: center; margin-top: var(--space-7);">
				<a class="oomph-btn oomph-btn--primary" href="<?php echo esc_url( (string) get_post_type_archive_link( 'oomph_cruise' ) ); ?>">
					See all sailings <span aria-hidden="true">→</span>
				</a>
			</p>
		</div>
	</section>
	<?php endif; ?>

	<?php /* 5. FOUNDER MINI-BIO --------------------------------------- */ ?>
	<section class="oomph-section is-style-oomph-cabin-notes" aria-labelledby="founder-title">
		<div class="oomph-container">
			<div class="oomph-grid oomph-grid--2 oomph-founder">
				<figure class="oomph-portrait oomph-founder__portrait">
					<?php
					echo oomph_picture(
						'eric-hempel-portrait.webp',
						array(
							'alt'    => 'Eric Hempel, travel advisor at Oomph Travel',
							'width'  => 735,
							'height' => 564,
							'sizes'  => '(min-width: 768px) 50vw, 100vw',
						)
					); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — escaped in helper.
					?>
				</figure>
				<div class="oomph-founder__copy">
					<p class="oomph-eyebrow" style="color: var(--color-champagne);">One Advisor</p>
					<h2 id="founder-title" class="oomph-italic-display" style="font-size: var(--text-h1);">Hi, I'm Eric.</h2>
					<p>I plan premium and luxury cruises and custom European journeys for clients who want one named advisor across the whole trip — from the first call to the last flight home. Based in Port Angeles, Washington.</p>
					<p>
						<a class="oomph-btn oomph-btn--inverse" href="/about/">
							Read my story <span aria-hidden="true">→</span>
						</a>
					</p>
				</div>
			</div>
		</div>
	</section>

	<?php /* 6. HOW IT WORKS ------------------------------------------- */ ?>
	<section class="oomph-section" aria-labelledby="how-it-works-title">
		<div class="oomph-container">
			<div class="oomph-section__intro">
				<p class="oomph-eyebrow">How it works</p>
				<h2 id="how-it-works-title">Three steps. One conversation to start.</h2>
			</div>
			<div class="oomph-grid oomph-grid--3">
				<div>
					<p class="oomph-eyebrow">Step One · Discover</p>
					<h3 class="oomph-italic-display" style="font-size: var(--text-h3);">A free 30-minute call.</h3>
					<p>We talk about the trip you're imagining — who's going, when, where you've already been, what you'd never do again. By the end I know whether I'm the right advisor for you, and you know what comes next.</p>
				</div>
				<div>
					<p class="oomph-eyebrow">Step Two · Design</p>
					<h3 class="oomph-italic-display" style="font-size: var(--text-h3);">One proposal, not five.</h3>
					<p>Cabin selection, itinerary, transfers, dinner reservations, the small details that make a trip feel choreographed. You see one proposal, not five — because I do the narrowing for you.</p>
				</div>
				<div>
					<p class="oomph-eyebrow">Step Three · Depart</p>
					<h3 class="oomph-italic-display" style="font-size: var(--text-h3);">Eyes on it the whole time.</h3>
					<p>If something changes — a delayed flight, a closed restaurant, a sudden chance to do something better — I'm reachable. The point of an advisor isn't the planning; it's the person on call when the day shifts.</p>
				</div>
			</div>
		</div>
	</section>

	<?php /* 7. LEAD MAGNET -------------------------------------------- */ ?>
	<section class="oomph-section is-style-oomph-quiet-premium" aria-labelledby="lead-magnet-title">
		<div class="oomph-container">
			<div class="oomph-grid oomph-grid--2 oomph-leadmagnet">
				<figure class="oomph-cover oomph-leadmagnet__cover">
					<?php
						echo oomph_picture(
							'cabin-guide-cover.webp',
							array(
								'alt'    => 'Cover of the Silversea cabin selection guide',
								'width'  => 900,
								'height' => 1443,
								'sizes'  => '(min-width: 768px) 50vw, 100vw',
							)
						); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — escaped in helper.
						?>
				</figure>
				<div class="oomph-leadmagnet__copy">
					<p class="oomph-eyebrow">Cruise · Cabin Note</p>
					<h2 id="lead-magnet-title">The cabin you choose determines the trip you have.</h2>
					<p>Same ship, same itinerary — two cabins three decks apart can be a different vacation. Answer seven questions and I'll point you to the cabin category that fits how you actually sail, plus my full Cabin Selection Guide.</p>
					<p>
						<a class="oomph-btn oomph-btn--primary" href="/trip-quiz/">
							Take the cabin quiz <span aria-hidden="true">→</span>
						</a>
						<span class="oomph-btn-microcopy">Seven questions, about two minutes.</span>
					</p>
				</div>
			</div>
		</div>
	</section>

	<?php /* 8. TESTIMONIALS ------------------------------------------- */ ?>
	<section class="oomph-section" aria-labelledby="testimonials-title">
		<div class="oomph-container">
			<div class="oomph-section__intro">
				<p class="oomph-eyebrow">Client Stories</p>
				<h2 id="testimonials-title">In their words.</h2>
			</div>
			<div class="oomph-grid oomph-grid--3">
				<blockquote class="oomph-card oomph-card--champagne">
					<p>"Thirtieth anniversary, Silver Nova, March 2025. Eric had the cabin, the dinner reservations, and the shore time mapped before we even knew what to ask for."</p>
					<footer class="oomph-card__meta">The Hendersons · 30th anniversary · Silver Nova · March 2025</footer>
				</blockquote>
				<blockquote class="oomph-card oomph-card--mist">
					<p>"Eleven days in Puglia, four generations, one wheelchair, three picky eaters. I don't know how he did it but it landed."</p>
					<footer class="oomph-card__meta">M. & R. · Multi-gen Italy · April 2025</footer>
				</blockquote>
				<blockquote class="oomph-card oomph-card--champagne">
					<p>"He talked us out of a cabin we were excited about and into a better one for the same price. That's the call you want your advisor to make."</p>
					<footer class="oomph-card__meta">D. Patel · Mediterranean cruise · October 2024</footer>
				</blockquote>
			</div>
		</div>
	</section>

	<?php /* 9. FEES TEASER — deferred. Will return per docx §10.3 when
	         the fees system is implemented; restore from git history
	         (commit 96869b0 or earlier) or rebuild against the same
	         section anatomy used by the other quiet-premium blocks. */ ?>

	<?php /* 10. FEATURED JOURNAL --------------------------------------- */ ?>
	<section class="oomph-section" aria-labelledby="journal-title">
		<div class="oomph-container">
			<div class="oomph-section__intro">
				<p class="oomph-eyebrow">From the Journal</p>
				<h2 id="journal-title">Recent field notes.</h2>
			</div>
			<div class="oomph-grid oomph-grid--3">
				<?php
				$recent_posts = get_posts(
					array(
						'numberposts' => 3,
						'post_status' => 'publish',
					)
				);

				if ( $recent_posts ) {
					foreach ( $recent_posts as $post ) :
						setup_postdata( $post );
						?>
						<a class="oomph-card oomph-card--clickable" href="<?php the_permalink(); ?>">
							<p class="oomph-eyebrow oomph-card__eyebrow">Field Note</p>
							<h3 class="oomph-card__headline"><?php the_title(); ?></h3>
							<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 28 ) ); ?></p>
							<p class="oomph-card__meta">From the Journal · <?php echo esc_html( get_the_date() ); ?></p>
							<span class="oomph-card__link" aria-hidden="true"></span>
						</a>
						<?php
					endforeach;
					wp_reset_postdata();
				} else {
					// Placeholder cards until the journal has posts.
					for ( $i = 1; $i <= 3; $i++ ) :
						?>
						<article class="oomph-card">
							<p class="oomph-eyebrow oomph-card__eyebrow">Field Note · <?php echo str_pad( (string) $i, 2, '0', STR_PAD_LEFT ); ?></p>
							<h3 class="oomph-card__headline">Journal post placeholder.</h3>
							<p>The first journal posts ship in Phase 10.13. Until then this slot is held for the pillar + cluster content the home page links to.</p>
							<p class="oomph-card__meta">— From the Journal</p>
						</article>
						<?php
					endfor;
				}
				?>
			</div>
		</div>
	</section>

	<?php /* 11. FINAL CTA --------------------------------------------- */ ?>
	<section class="oomph-section is-style-oomph-cabin-notes" aria-labelledby="final-cta-title">
		<div class="oomph-container" style="text-align: center;">
			<p class="oomph-eyebrow" style="color: var(--color-champagne);">One trip</p>
			<h2 id="final-cta-title" class="oomph-italic-display" style="font-size: var(--text-h1); max-width: 22ch; margin-inline: auto;">
				Plan the trip you'll talk about for thirty years.
			</h2>
			<p style="margin-top: var(--space-6);">
				<a class="oomph-btn oomph-btn--inverse" href="/discovery-call/">
					Start a conversation <span aria-hidden="true">→</span>
				</a>
				<span class="oomph-btn-microcopy" style="color: var(--color-champagne);">Email, text, or a quick call — whatever's easiest for you.</span>
			</p>
		</div>
	</section>

</main>

<?php /* Sticky mobile CTA — R2. Visible at every scroll depth, mobile only. */ ?>
<aside class="oomph-sticky-cta" aria-label="Quick contact">
	<a class="oomph-btn oomph-btn--primary" href="/discovery-call/">
		Start a conversation <span aria-hidden="true">→</span>
	</a>
</aside>

<?php
get_footer();
