<?php
/**
 * Template Name: Service Page
 *
 * Service hub template — applies to the three service pages (Luxury
 * Cruise Planning, Custom Italy, Multi-Gen). Assigned via the WP admin
 * Page Attributes → Template dropdown. The Service Page ACF group is
 * keyed to this template slug, so the ACF fields only surface when this
 * template is selected.
 *
 * Per docx §10.5 — eleven sections from hero to final CTA. Two are
 * intentionally deferred:
 *   • §7 Fee transparency — pending fees implementation (same
 *     deferral as the home page's removed FEES TEASER).
 *   • §1 ghost CTA "Send me the cabin guide" — pending lead-magnet
 *     system.
 *
 * Primary CTA "Start a conversation →" appears in the hero, after the
 * deliverables grid, after the "why advisor" section, and in the final
 * CTA block (four placements + sticky mobile bar = five surfaces).
 *
 * Schema (Service + FAQPage) is injected sitewide by the
 * oomph-travel-core plugin's class-schema.php, conditional on this
 * template slug. No schema is output here.
 *
 * @package OomphChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

/*
 * Hero — read from ACF Page Hero with service-specific fallbacks.
 */
$hero_eyebrow   = oomph_acf_field( 'hero_eyebrow', 'Service · Luxury Cruises' );
$hero_headline  = oomph_acf_field( 'hero_headline', 'Luxury cruise planning, by an actual advisor.' );
$hero_subhead   = oomph_acf_field(
	'hero_subhead',
	'Premium and ultra-luxury cruise advice from a Silversea Ultra-Luxury Specialist. One named advisor across the booking — first call to last flight home.'
);
$hero_cta_label = oomph_acf_field( 'hero_cta_label', 'Start a conversation →' );
$hero_cta_url   = oomph_acf_field( 'hero_cta_url', '/discovery-call/' );
$hero_cta_text  = trim( preg_replace( '/\s*→\s*$/u', '', $hero_cta_label ) );

$show_trust_strip = (bool) oomph_acf_field( 'hero_trust_strip', true );

// Hero background image: ACF image array or no image (Bone canvas).
$hero_image_acf = function_exists( 'get_field' ) ? get_field( 'hero_image' ) : null;
$hero_has_image = is_array( $hero_image_acf ) && ! empty( $hero_image_acf['url'] );
// Fall back to a bundled cruise hero photo when no ACF image is set.
$hero_url = $hero_has_image ? $hero_image_acf['url'] : get_stylesheet_directory_uri() . '/assets/images/heroes/cruise-hero.jpg';

/*
 * Service Page ACF fields with voice-aligned fallbacks. Fallbacks
 * render on a freshly-created page before Eric populates the ACF
 * fields; once populated, ACF values take over.
 *
 * NOTE: schema (FAQPage) only emits when ACF has real values — never
 * uses these template defaults — so the fallback content stays out of
 * the indexable structured data.
 */

// §2 Negative qualifiers — repeater (each row has 'bullet').
$neg_qualifiers = function_exists( 'get_field' ) ? get_field( 'service_negative_qualifiers' ) : null;
if ( ! is_array( $neg_qualifiers ) || empty( $neg_qualifiers ) ) {
	$neg_qualifiers = array(
		array( 'bullet' => 'You want the lowest possible price at the expense of cabin choice. There are sites for that.' ),
		array( 'bullet' => 'You only need a confirmation number — you have already done the planning yourself.' ),
		array( 'bullet' => 'You want me to book the same itinerary you found on a deal site, no consultation, no input.' ),
	);
}

// §3 What I actually do — repeater (each row has 'headline' + 'body').
$what_you_do = function_exists( 'get_field' ) ? get_field( 'service_what_you_do' ) : null;
if ( ! is_array( $what_you_do ) || empty( $what_you_do ) ) {
	$what_you_do = array(
		array(
			'headline' => 'Cabin selection — by ship, deck, and wave-zone.',
			'body'     => 'Cruise lines publish thousands of cabins; only a handful are right for you. I know the deck plans, the noise patterns, the suite categories that overdeliver, and the ones that quietly disappoint.',
		),
		array(
			'headline' => 'Onboard credit and amenity coordination.',
			'body'     => 'Promotions, supplier perks, and consortium amenities are not published — they are applied. I tell you what is available, then book the right one for your booking class.',
		),
		array(
			'headline' => 'Pre- and post-cruise extensions.',
			'body'     => 'The cruise is the middle of the trip, not the whole trip. Hotels in the embarkation port, transfers, a few extra days in Lisbon or Venice — handled.',
		),
		array(
			'headline' => 'Group cabin holds for milestone trips.',
			'body'     => 'Anniversaries, family reunions, multi-cabin groups. I hold the right cabins together while you build your guest list.',
		),
		array(
			'headline' => 'Single-supplement strategy.',
			'body'     => 'Sailing solo does not have to mean paying double. I know which sailings waive the supplement and which suite categories are friendliest to solo bookings.',
		),
		array(
			'headline' => 'Insurance review and recommendation.',
			'body'     => 'Cruise insurance is a different animal than land-trip insurance. I will tell you what the cruise line policy actually covers, and where third-party coverage makes sense.',
		),
	);
}

// §10 FAQs — repeater (each row has 'question' + 'answer').
$faqs = function_exists( 'get_field' ) ? get_field( 'service_faqs' ) : null;
if ( ! is_array( $faqs ) || empty( $faqs ) ) {
	$faqs = array(
		array(
			'question' => 'Do you charge a planning fee?',
			'answer'   => 'No — I do not charge a planning fee. Cruise lines pay travel advisors a commission on the booked fare, and that commission does not change your price. You get cabin selection, dining strategy, and pre- and post-cruise extensions handled by a named advisor at no added cost to you.',
		),
		array(
			'question' => 'I already have an account with Royal Caribbean. Can you still help?',
			'answer'   => 'Sometimes. Cruise lines do not always allow advisor takeovers of existing bookings, and the rules differ by line and booking class. The earlier I can get involved, the more I can do. If you have paid your deposit on Royal Caribbean directly, call before you book the next one — that is where I can add the most value.',
		),
		array(
			'question' => 'How are commissions disclosed?',
			'answer'   => 'Cruise lines pay travel advisors a commission on the booked fare. It is part of how the industry works and does not affect your price. I will tell you the commission rate on your specific booking if you ask. I do not charge a separate planning fee.',
		),
		array(
			'question' => 'Can you hold cabins before I commit?',
			'answer'   => 'Most cruise lines allow advisors to hold cabins for 24 to 72 hours without payment. I use this to lock in the right cabin while you check work calendars, talk to your travel companions, or sleep on the decision. Holds are not always available on promotional rates.',
		),
		array(
			'question' => 'What if I want to extend before or after the sailing?',
			'answer'   => 'The cruise is the middle of the trip, not the whole trip. I handle hotels in the embarkation and disembarkation ports, transfers, day tours, and rail or flight connections. Extensions usually need to be priced separately from the cruise itself — that is where most of my planning time goes.',
		),
		array(
			'question' => 'How do you handle solo supplements?',
			'answer'   => 'Solo supplements can effectively double the price of a cruise. I know which sailings waive the supplement, which suite categories are friendliest to solo bookings, and which lines run solo-targeted promotions. If you are cruising solo, ask before you book — it is often a question of timing as much as ship.',
		),
	);
}
?>

<main id="primary" class="oomph-service" role="main">

	<a class="skip-link sr-only sr-only-focusable" href="#oomph-content">Skip to main content</a>
	<div id="oomph-content"></div>

	<?php /* 1. HERO ---------------------------------------------------- */ ?>
	<section class="oomph-hero oomph-hero--imaged" aria-label="Luxury cruise planning">
		<picture class="oomph-hero__media">
			<img
				src="<?php echo esc_url( $hero_url ); ?>"
				alt="<?php echo esc_attr( $hero_has_image ? ( $hero_image_acf['alt'] ?? '' ) : '' ); ?>"
				width="1600"
				height="1067"
				fetchpriority="high"
				decoding="async"
			>
		</picture>
		<div class="oomph-container oomph-hero__inner">
			<p class="oomph-eyebrow"><?php echo esc_html( strtoupper( $hero_eyebrow ) ); ?></p>
			<h1 class="oomph-hero__headline oomph-italic-display"><?php echo esc_html( $hero_headline ); ?></h1>
			<p class="oomph-hero__subhead"><?php echo esc_html( $hero_subhead ); ?></p>
			<p class="oomph-hero__cta">
				<a class="oomph-btn oomph-btn--primary" href="<?php echo esc_url( $hero_cta_url ); ?>">
					<?php echo esc_html( $hero_cta_text ); ?> <span aria-hidden="true">→</span>
				</a>
				<span class="oomph-btn-microcopy">Email, text, or a quick call — whatever's easiest for you.</span>
			</p>
			<?php /* TODO: Hero ghost CTA "Send me the cabin guide" — deferred pending
			        lead-magnet system. Re-enable here once /cabin-guide/ or the inline
			        signup is ready. Per docx §10.5 it should sit beside the primary CTA. */ ?>
		</div>
	</section>

	<?php /* 2. TRUST STRIP --------------------------------------------- */ ?>
	<?php if ( $show_trust_strip ) : ?>
	<aside class="oomph-trust-strip" aria-label="Credentials">
		<span class="oomph-trust-strip__item oomph-trust-strip__item--highlight">Silversea Ultra-Luxury Specialist</span>
		<span class="oomph-trust-strip__item">CLIA Member</span>
		<span class="oomph-trust-strip__item">Nexion Affiliated</span>
		<span class="oomph-trust-strip__item">BritAgent Pro</span>
		<span class="oomph-trust-strip__item">Port Angeles · WA</span>
	</aside>
	<?php endif; ?>

	<?php /* 3. WHO THIS IS FOR / NOT FOR ------------------------------- */ ?>
	<section class="oomph-section is-style-oomph-quiet-premium" aria-labelledby="who-for-title">
		<div class="oomph-container">
			<div class="oomph-section__intro">
				<p class="oomph-eyebrow">Pre-qualifying</p>
				<h2 id="who-for-title">Who this is for, and who it isn't.</h2>
			</div>
			<div class="oomph-grid oomph-grid--2 oomph-who-for">
				<div class="oomph-who-for__col">
					<h3 class="oomph-card__headline">Who this is for</h3>
					<ul class="oomph-bullets">
						<li>You want one named advisor across the whole booking — not three different people each time you call.</li>
						<li>You're planning a milestone trip and want the cabin and the timing to land.</li>
						<li>You've sailed a few times and want help levelling up to premium or ultra-luxury without surprises.</li>
					</ul>
				</div>
				<div class="oomph-who-for__col">
					<h3 class="oomph-card__headline">Who this is not for</h3>
					<ul class="oomph-bullets oomph-bullets--negative">
						<?php foreach ( $neg_qualifiers as $row ) : ?>
							<?php $b = is_array( $row ) ? ( $row['bullet'] ?? '' ) : ''; ?>
							<?php if ( $b ) : ?>
							<li><?php echo esc_html( $b ); ?></li>
							<?php endif; ?>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
		</div>
	</section>

	<?php /* 4. WHAT I ACTUALLY DO -------------------------------------- */ ?>
	<section class="oomph-section" aria-labelledby="what-i-do-title">
		<div class="oomph-container">
			<div class="oomph-section__intro">
				<p class="oomph-eyebrow">Deliverables</p>
				<h2 id="what-i-do-title">What I actually do.</h2>
			</div>
			<div class="oomph-grid oomph-grid--3">
				<?php foreach ( $what_you_do as $row ) : ?>
					<?php
					$h = is_array( $row ) ? ( $row['headline'] ?? '' ) : '';
					$b = is_array( $row ) ? ( $row['body'] ?? '' ) : '';
					?>
					<?php if ( $h || $b ) : ?>
					<article class="oomph-card">
						<?php if ( $h ) : ?>
							<h3 class="oomph-card__headline"><?php echo esc_html( $h ); ?></h3>
						<?php endif; ?>
						<?php if ( $b ) : ?>
							<p><?php echo esc_html( $b ); ?></p>
						<?php endif; ?>
					</article>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
			<p class="oomph-section__cta" style="text-align: center; margin-top: var(--space-7);">
				<a class="oomph-btn oomph-btn--primary" href="/discovery-call/">
					Start a conversation <span aria-hidden="true">→</span>
				</a>
			</p>
		</div>
	</section>

	<?php /* 5. WHY AN ADVISOR MATTERS ---------------------------------- */ ?>
	<section class="oomph-section is-style-oomph-cabin-notes" aria-labelledby="why-advisor-title">
		<div class="oomph-container oomph-container--prose">
			<p class="oomph-eyebrow" style="color: var(--color-champagne);">Why an advisor</p>
			<h2 id="why-advisor-title" class="oomph-italic-display" style="color: var(--color-paper); font-size: var(--text-h1);">
				Cruise lines publish thousands of cabins; only a handful are right for you.
			</h2>
			<div style="color: var(--color-paper);">
				<p><strong>Price parity.</strong> Cruise line pricing is the same whether you book direct, through a marketplace, or through me. The fare is set by the line, not the channel. What changes is whether anyone is looking at your booking after you press confirm.</p>
				<p><strong>Hidden cabin pitfalls.</strong> Decks above the theater, cabins below the pool grill, mid-ship suites under the gym, balconies that face the wind on a one-way itinerary — these aren't on the deck plan. I know the deck plans because I've sailed them.</p>
				<p><strong>Supplier escalation.</strong> When something goes wrong — and at least once, something goes wrong — having a real advisor escalates faster than a 1-800 number. I have the supplier contacts. I make the call while you keep enjoying your trip.</p>
				<p><strong>One person across the booking.</strong> You don't get passed between three departments when you have a question about pre-cruise transfers, then again for dining reservations, then again at the port. One advisor, one inbox, one phone number.</p>
			</div>
			<p style="margin-top: var(--space-7);">
				<a class="oomph-btn oomph-btn--inverse" href="/discovery-call/">
					Start a conversation <span aria-hidden="true">→</span>
				</a>
			</p>
		</div>
	</section>

	<?php /* 6. CRUISE LINES I PLAN ------------------------------------- */ ?>
	<section class="oomph-section" aria-labelledby="cruise-lines-title">
		<div class="oomph-container">
			<div class="oomph-section__intro">
				<p class="oomph-eyebrow">Coverage</p>
				<h2 id="cruise-lines-title">Cruise lines I plan.</h2>
			</div>
			<div class="oomph-grid oomph-grid--2 oomph-cruise-lines">
				<article class="oomph-card oomph-card--bordered">
					<h3 class="oomph-card__headline">Silversea <span class="oomph-card__tag">Specialist</span></h3>
					<p>Ultra-luxury, all-inclusive, fleet of small ships in the 250–600 guest range. My specialty line; fourteen Silversea voyages and the Silversea Ultra-Luxury Specialist certification.</p>
				</article>
				<article class="oomph-card oomph-card--bordered">
					<h3 class="oomph-card__headline">Regent Seven Seas</h3>
					<p>All-suite, all-balcony, all-inclusive — including business-class air on most itineraries. The other major ultra-luxury operator.</p>
				</article>
				<article class="oomph-card oomph-card--bordered">
					<h3 class="oomph-card__headline">Seabourn</h3>
					<p>Small-ship ultra-luxury, 450–650 guests. Casual-elegant evening dress code, exceptional crew-to-guest ratio.</p>
				</article>
				<article class="oomph-card oomph-card--bordered">
					<h3 class="oomph-card__headline">Crystal</h3>
					<p>Relaunched in 2023 under new ownership; two ships, both ultra-luxury. The crew kept much of the original Crystal feel.</p>
				</article>
				<article class="oomph-card oomph-card--bordered">
					<h3 class="oomph-card__headline">Cunard Grills</h3>
					<p>Queen Mary 2, Queen Victoria, Queen Elizabeth, Queen Anne — book a Grills-class suite for the boutique ultra-luxury experience inside the larger Cunard ship.</p>
				</article>
				<article class="oomph-card oomph-card--bordered">
					<h3 class="oomph-card__headline">Viking Ocean + River</h3>
					<p>Adult-only (18+) ocean and river cruising. Mid-luxury, all-veranda staterooms, included shore excursions.</p>
				</article>
			</div>
			<!-- TODO: Add cruise-line logos when assets are available. Per docx §10.5 the section originally specs a logo grid; we render text-only for v1. -->
		</div>
	</section>

	<?php /* 7. HOW I WORK ---------------------------------------------- */ ?>
	<section class="oomph-section" aria-labelledby="how-i-work-title">
		<div class="oomph-container">
			<div class="oomph-section__intro">
				<p class="oomph-eyebrow">How I work</p>
				<h2 id="how-i-work-title">Three steps. One conversation to start.</h2>
			</div>
			<div class="oomph-grid oomph-grid--3">
				<div>
					<p class="oomph-eyebrow">Step One · Discover</p>
					<h3 class="oomph-italic-display" style="font-size: var(--text-h3);">A free 30-minute call.</h3>
					<p>We talk about the cruise you're imagining — which itineraries you've sailed, what you'd repeat, what you'd never do again. By the end I know which lines fit and which don't, and you know what comes next.</p>
				</div>
				<div>
					<p class="oomph-eyebrow">Step Two · Design</p>
					<h3 class="oomph-italic-display" style="font-size: var(--text-h3);">One proposal, not five.</h3>
					<p>Cabin selection, onboard credit and amenities, pre- and post-cruise hotels, transfers, dinner reservations, the small details. You see one proposal, not five — because I do the narrowing for you.</p>
				</div>
				<div>
					<p class="oomph-eyebrow">Step Three · Depart</p>
					<h3 class="oomph-italic-display" style="font-size: var(--text-h3);">Eyes on it the whole time.</h3>
					<p>From confirmation to your post-cruise hotel, I'm reachable. Flight delays, port changes, a sudden chance to upgrade an excursion — I'm the one you call when the day shifts.</p>
				</div>
			</div>
		</div>
	</section>

	<?php /* 8. FEE TRANSPARENCY — deferred. Will return per docx §10.5 when
	         the fees system is implemented. Restore from git history or
	         rebuild against the quiet-premium block pattern. */ ?>

	<?php /* 9. FEATURED JOURNAL ---------------------------------------- */ ?>
	<section class="oomph-section is-style-oomph-quiet-premium" aria-labelledby="cruise-journal-title">
		<div class="oomph-container">
			<div class="oomph-section__intro">
				<p class="oomph-eyebrow">Field notes</p>
				<h2 id="cruise-journal-title">Recent writing on cruise planning.</h2>
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
					for ( $i = 1; $i <= 3; $i++ ) :
						?>
						<article class="oomph-card">
							<p class="oomph-eyebrow oomph-card__eyebrow">Field Note · <?php echo str_pad( (string) $i, 2, '0', STR_PAD_LEFT ); ?></p>
							<h3 class="oomph-card__headline">Cluster post placeholder.</h3>
							<p>The first cruise journal posts ship in Phase 10.13. Until then this slot is held for the cluster content this hub will link to.</p>
							<p class="oomph-card__meta">— From the Journal</p>
						</article>
						<?php
					endfor;
				}
				?>
			</div>
		</div>
	</section>

	<?php /* 10. TESTIMONIALS ------------------------------------------- */ ?>
	<section class="oomph-section" aria-labelledby="testimonials-title">
		<div class="oomph-container">
			<div class="oomph-section__intro">
				<p class="oomph-eyebrow">In their words</p>
				<h2 id="testimonials-title">From recent cruise clients.</h2>
			</div>
			<div class="oomph-grid oomph-grid--2">
				<blockquote class="oomph-card oomph-card--champagne">
					<p>"From start to finish, Eric's planning was spot-on — every detail handled with care, and his knowledge of cruise travel really showed. What stood out most was his communication: he was always available, kept us informed every step of the way, and made the whole process easy and stress-free. We'll be working with Eric again."</p>
					<footer class="oomph-card__meta">Gary T. · Oceania Cruises · Poulsbo, WA<!-- TODO: Eric — add sailing year if available. --></footer>
				</blockquote>
				<blockquote class="oomph-card oomph-card--mist">
					<p>"The cruise itself was a blast, but the highlights were the moments before and after we sailed. Eric arranged a boutique hotel in the heart of Miami Beach, then a hotel near a lively boardwalk for the return — both effortless to settle into, and his thoughtful planning made the whole trip feel seamless."</p>
					<footer class="oomph-card__meta">travellover52 · Pre + post Miami extension · Port Angeles, WA<!-- TODO: Eric — replace nickname with real first name + last initial if available; add sailing year. --></footer>
				</blockquote>
			</div>
		</div>
	</section>

	<?php /* 11. FAQ ---------------------------------------------------- */ ?>
	<section class="oomph-section is-style-oomph-european-itinerary" aria-labelledby="faq-title">
		<div class="oomph-container oomph-container--prose">
			<p class="oomph-eyebrow">Frequently asked</p>
			<h2 id="faq-title">The questions I get most.</h2>
			<div class="oomph-faq">
				<?php foreach ( $faqs as $row ) : ?>
					<?php
					$q = is_array( $row ) ? ( $row['question'] ?? '' ) : '';
					$a = is_array( $row ) ? ( $row['answer'] ?? '' ) : '';
					?>
					<?php if ( $q && $a ) : ?>
					<details class="oomph-faq__item">
						<summary class="oomph-faq__question"><?php echo esc_html( $q ); ?></summary>
						<div class="oomph-faq__answer"><?php echo wp_kses_post( wpautop( $a ) ); ?></div>
					</details>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<?php /* 12. FINAL CTA ---------------------------------------------- */ ?>
	<section class="oomph-section is-style-oomph-cabin-notes" aria-labelledby="final-cta-title">
		<div class="oomph-container" style="text-align: center;">
			<p class="oomph-eyebrow" style="color: var(--color-champagne);">First call</p>
			<h2 id="final-cta-title" class="oomph-italic-display" style="font-size: var(--text-h1); max-width: 22ch; margin-inline: auto;">
				Worth a thirty-minute call?
			</h2>
			<p style="margin-top: var(--space-6);">
				<a class="oomph-btn oomph-btn--inverse" href="/discovery-call/">
					Start a conversation <span aria-hidden="true">→</span>
				</a>
				<span class="oomph-btn-microcopy" style="color: var(--color-champagne);">Email, text, or a quick call — whatever's easiest for you.</span>
			</p>
			<?php /* TODO: Final dual CTA — secondary ghost "Send me the cabin guide"
			        deferred pending lead-magnet system. Re-enable beside the primary
			        button once the guide signup exists. */ ?>
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
