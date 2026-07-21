<?php
/**
 * About page template.
 *
 * Per docx §10.4 — nine narrative sections from hero to final CTA. Primary
 * CTA "Start a conversation →" appears in the hero and the final CTA
 * block (two placements + the sticky mobile bar = three surfaces total).
 *
 * Person schema is injected sitewide via the oomph-travel-core plugin's
 * class-schema.php with @id about/#advisor; no schema is output here.
 *
 * Headshot: assets/images/eric-hempel-portrait.webp — shared with the
 * home page's founder mini-bio section.
 *
 * @package OomphChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

/*
 * Hero — read from ACF Page Hero with About-specific fallbacks. The
 * About hero is a split layout (portrait + copy), so the hero_image
 * ACF field is intentionally unused; the headshot is hardcoded.
 */
$hero_eyebrow   = oomph_acf_field( 'hero_eyebrow', 'About' );
$hero_headline  = oomph_acf_field( 'hero_headline', "I plan the trips I'd take myself." );
$hero_subhead   = oomph_acf_field(
	'hero_subhead',
	'From Port Angeles, Washington, I plan premium cruises and custom European journeys for clients who want one named advisor across the whole trip — first call to last flight home.'
);
$hero_cta_label = oomph_acf_field( 'hero_cta_label', 'Start a conversation →' );
$hero_cta_url   = oomph_acf_field( 'hero_cta_url', '/discovery-call/' );
$hero_cta_text  = trim( preg_replace( '/\s*→\s*$/u', '', $hero_cta_label ) );

$show_trust_strip = (bool) oomph_acf_field( 'hero_trust_strip', true );

// Portrait is hardcoded — same headshot as the home page's founder bio.
$portrait_url = get_stylesheet_directory_uri() . '/assets/images/eric-hempel-portrait.webp';
?>

<main id="primary" class="oomph-about" role="main">

	<a class="skip-link sr-only sr-only-focusable" href="#oomph-content">Skip to main content</a>
	<div id="oomph-content"></div>

	<?php /* 1. HERO — split portrait + copy ---------------------------- */ ?>
	<section class="oomph-section oomph-hero oomph-hero--split" aria-label="About Eric Hempel">
		<div class="oomph-container">
			<div class="oomph-grid oomph-grid--2 oomph-hero-split">
				<figure class="oomph-portrait oomph-hero-split__portrait">
					<img
						src="<?php echo esc_url( $portrait_url ); ?>"
						alt="Eric Hempel, travel advisor at Oomph Travel"
						width="480"
						height="480"
						fetchpriority="high"
						decoding="async"
					>
				</figure>
				<div class="oomph-hero-split__copy">
					<p class="oomph-eyebrow"><?php echo esc_html( strtoupper( $hero_eyebrow ) ); ?></p>
					<h1 class="oomph-hero__headline oomph-italic-display"><?php echo esc_html( $hero_headline ); ?></h1>
					<p class="oomph-hero__subhead"><?php echo esc_html( $hero_subhead ); ?></p>
					<p class="oomph-hero__cta">
						<a class="oomph-btn oomph-btn--primary" href="<?php echo esc_url( $hero_cta_url ); ?>">
							<?php echo esc_html( $hero_cta_text ); ?> <span aria-hidden="true">→</span>
						</a>
						<span class="oomph-btn-microcopy">Email, text, or a quick call — whatever's easiest for you.</span>
					</p>
				</div>
			</div>
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

	<?php /* 3. WHY I DO THIS ------------------------------------------- */ ?>
	<section class="oomph-section" aria-labelledby="why-title">
		<div class="oomph-container oomph-container--prose">
			<p class="oomph-eyebrow">One advisor</p>
			<h2 id="why-title">Why I do this.</h2>
			<p>I started planning other people's trips because I'd been keeping spreadsheets for my own for years. Cabin numbers, deck plans, the restaurant in Lecce my brother-in-law wouldn't stop asking about. Eventually a friend asked me to plan a Mediterranean cruise for their thirtieth anniversary. They came home and told four people. That was the whole start of it.</p>
			<!-- TODO: Eric — replace with your actual 60-word origin moment. Voice-aligned placeholder. -->
		</div>
	</section>

	<?php /* 4. WHO I SERVE, AND WHY ----------------------------------- */ ?>
	<section class="oomph-section is-style-oomph-european-itinerary" aria-labelledby="serve-title">
		<div class="oomph-container">
			<div class="oomph-section__intro">
				<p class="oomph-eyebrow">Who I serve</p>
				<h2 id="serve-title">Three kinds of trip, three reasons I'm the right advisor.</h2>
			</div>
			<div class="oomph-grid oomph-grid--3">
				<article>
					<h3 class="oomph-card__headline">Couples planning a milestone.</h3>
					<p>Anniversaries, retirements, the trip you've talked about for ten years. The kind that has to land — choose the right cabin, get the dinner reservations, pace the days so neither of you comes home tired. I plan milestone trips because I've planned my own; the ones you only get a couple of in a lifetime deserve someone who has been there.</p>
				</article>
				<article>
					<h3 class="oomph-card__headline">Families planning across three generations.</h3>
					<p>Three generations, one wheelchair, three picky eaters. Mobility, dietary, room configurations, a plan B for weather — a hundred small decisions, not one big one. I serve multi-gen families because they remind me of my own, and because the trip works or it doesn't. There's no middle ground.</p>
				</article>
				<article>
					<h3 class="oomph-card__headline">Cruisers planning what's next.</h3>
					<p>You've sailed the mainstream lines and you're curious what ultra-luxury is actually like. I've sailed fourteen Silversea voyages. I'll tell you what's worth it, what isn't, and which cabin categories overdeliver — and which ones quietly disappoint.</p>
				</article>
			</div>
		</div>
	</section>

	<?php /* 5. CREDENTIALS -------------------------------------------- */ ?>
	<section class="oomph-section" aria-labelledby="credentials-title">
		<div class="oomph-container">
			<div class="oomph-section__intro">
				<p class="oomph-eyebrow">Credentials</p>
				<h2 id="credentials-title">The training behind the planning.</h2>
			</div>
			<div class="oomph-grid oomph-grid--2 oomph-credentials">
				<article class="oomph-card oomph-card--bordered">
					<h3 class="oomph-card__headline">CLIA Member</h3>
					<p>Cruise Lines International Association is the trade body that certifies cruise advisors. Membership is required to sell cabins commercially in the US.</p>
				</article>
				<article class="oomph-card oomph-card--bordered">
					<h3 class="oomph-card__headline">Silversea Ultra-Luxury Specialist</h3>
					<p>Silversea's highest advisor certification. Earned through completed product training and a verified booking record across the fleet.</p>
				</article>
				<article class="oomph-card oomph-card--bordered">
					<h3 class="oomph-card__headline">Nexion Affiliated</h3>
					<p>Nexion Travel Group is my host agency — the partner that supports access to supplier programs, booking tools, and consortium pricing.</p>
				</article>
				<article class="oomph-card oomph-card--bordered">
					<h3 class="oomph-card__headline">BritAgent Pro</h3>
					<p>VisitBritain's specialist certification for advisors planning trips to England, Scotland, Wales, and Northern Ireland.</p>
				</article>
			</div>
			<p class="oomph-credentials__inprogress"><em>Currently completing: Italy Destination Specialist (DS-Italy) certification.</em></p>
		</div>
	</section>

	<?php /* 6. FIRST-HAND EXPERIENCE ---------------------------------- */ ?>
	<section class="oomph-section is-style-oomph-cabin-notes" aria-labelledby="experience-title">
		<div class="oomph-container">
			<div class="oomph-section__intro">
				<p class="oomph-eyebrow" style="color: var(--color-champagne);">Field notes</p>
				<h2 id="experience-title" style="color: var(--color-paper);">First-hand experience.</h2>
			</div>
			<ol class="oomph-experience-list" style="color: var(--color-paper);">
				<!-- TODO: Eric — verify counts/ships/dates against your records. Items read naturally either way, but accuracy matters before launch. -->
				<li>Sailed 14 Silversea voyages between 2018 and 2025.</li>
				<li>Silver Nova · Western Mediterranean · March 2025.</li>
				<li>Silver Whisper · Northern Europe · September 2024.</li>
				<li>Silver Spirit · Caribbean · April 2024.</li>
				<li>Multiple voyages on Regent Seven Seas and Seabourn.</li>
				<li>Four trips to Italy between 2020 and 2025 — Puglia, Sicily, Tuscany, the Lakes.</li>
				<li>Two trips to the UK — London, Edinburgh, and the Lake District.</li>
			</ol>
			<!-- TODO: docx §10.4 calls for "First-hand experience list with photos." Add a small photo grid next to or below this list once imagery is selected. -->
		</div>
	</section>

	<?php /* 7. HOW I WORK --------------------------------------------- */ ?>
	<section class="oomph-section" aria-labelledby="how-title">
		<div class="oomph-container">
			<div class="oomph-section__intro">
				<p class="oomph-eyebrow">How I work</p>
				<h2 id="how-title">Three steps. One conversation to start.</h2>
			</div>
			<div class="oomph-grid oomph-grid--3">
				<div>
					<p class="oomph-eyebrow">Step One · Discover</p>
					<h3 class="oomph-italic-display" style="font-size: var(--text-h3);">A free 30-minute call.</h3>
					<p>We talk about the trip you're imagining — who's going, when, where you've already been, what you'd never do again. By the end of the call I know whether I'm the right advisor for you, and you know what comes next. No pressure either way.</p>
				</div>
				<div>
					<p class="oomph-eyebrow">Step Two · Design</p>
					<h3 class="oomph-italic-display" style="font-size: var(--text-h3);">One proposal, not five.</h3>
					<p>Cabin selection, itinerary, transfers, dinner reservations — the small details that make a trip feel choreographed. You see one proposal because I do the narrowing for you. Revisions are part of the process; the goal is a plan we both agree on before any money changes hands.</p>
				</div>
				<div>
					<p class="oomph-eyebrow">Step Three · Depart</p>
					<h3 class="oomph-italic-display" style="font-size: var(--text-h3);">Eyes on it the whole time.</h3>
					<p>If something changes — a delayed flight, a closed restaurant, a sudden chance to do something better — I'm reachable. The point of an advisor isn't the planning; it's the person on call when the day shifts. I stay on your trip until you're back home.</p>
				</div>
			</div>
		</div>
	</section>

	<?php /* 8. PERSONAL TOUCHES --------------------------------------- */ ?>
	<section class="oomph-section is-style-oomph-quiet-premium" aria-labelledby="personal-title">
		<div class="oomph-container oomph-container--prose">
			<p class="oomph-eyebrow">Off the proposal</p>
			<h2 id="personal-title">Three small things.</h2>
			<p>The parts of the job that don't show up in the proposal but matter when the trip is happening:</p>
			<ul class="oomph-personal-touches">
				<!-- TODO: Eric — replace these three with your real specifics. The point is to humanize without being kitschy. -->
				<li>Reads Patrick Leigh Fermor on every flight to Greece.</li>
				<li>Keeps a notebook of restaurants in Bologna I'd send my sister to.</li>
				<li>Re-checks the weather, the port schedule, and your dinner reservations the night before each trip starts.</li>
			</ul>
		</div>
	</section>

	<?php /* 9. PULL-QUOTE TESTIMONIAL — hidden until real attribution is available.
	         Per cro-rules R31: first name + last initial, trip type, destination, date.
	         Per docx §10.4 example: "He kept the trip moving when my mother's knee
	         gave out in Florence. That's the part the website doesn't show you."
	         To re-enable, uncomment the section below and replace the placeholders.
	?>
	<section class="oomph-section" aria-labelledby="testimonial-title">
		<div class="oomph-container oomph-container--prose">
			<p class="oomph-eyebrow">In their words</p>
			<h2 id="testimonial-title" class="sr-only">Testimonial.</h2>
			<blockquote class="oomph-pullquote" style="font-family: var(--font-display); font-style: italic; font-size: var(--text-h2); line-height: 1.3;">
				<p>[ PLACEHOLDER — character-focused testimonial. Replace before launch. ]</p>
				<footer class="oomph-pullquote__cite" style="font-family: var(--font-text); font-style: normal; font-size: var(--text-body-sm); color: var(--color-slate);">[ Name · Trip type · Destination · Date ]</footer>
			</blockquote>
		</div>
	</section>
	<?php */ ?>

	<?php /* 10. FINAL CTA --------------------------------------------- */ ?>
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
