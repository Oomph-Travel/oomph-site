<?php
/**
 * Discovery Call page template.
 *
 * Bound by slug (page-discovery-call.php) to the page at /discovery-call/.
 * This is the conversion destination every primary CTA points to, so the
 * hero CTA and the sticky bar scroll to the booking section (#book) rather
 * than linking away.
 *
 * Booking: Calendly inline embed (never modal — CLAUDE.md). The Calendly
 * URL is filterable via 'oomph_calendly_url' (or the OOMPH_CALENDLY_URL
 * constant). Until a real URL is set, a styled placeholder renders and the
 * third-party Calendly script is NOT loaded.
 *
 * Intake: single-step Fluent Forms form (id resolved by title) embedded via
 * shortcode. Multi-step (R38) is deferred — it requires Fluent Forms Pro.
 *
 * On successful submit, a GA4 `generate_lead` event is pushed (R11). It is a
 * no-op until Site Kit / GA4 is live, then activates automatically.
 *
 * @package OomphChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Registered before get_header() so the <link rel=preload> still makes it
// into <head> — this hero is the LCP element on the page.
oomph_preload_hero( 'heroes/discovery-hero.jpg' );

get_header();

$hero_eyebrow  = oomph_acf_field( 'hero_eyebrow', 'Discovery Call' );
$hero_headline = oomph_acf_field( 'hero_headline', "Let's talk about the trip you're imagining." );
$hero_subhead  = oomph_acf_field(
	'hero_subhead',
	'A free 30-minute call. We talk through where you want to go, who is going, and what you would never do again. By the end you know whether I am the right advisor for you — no pressure either way.'
);

$show_trust_strip = (bool) oomph_acf_field( 'hero_trust_strip', true );

// Calendly URL — set via filter or constant; placeholder until provided.
$calendly_url = apply_filters( 'oomph_calendly_url', defined( 'OOMPH_CALENDLY_URL' ) ? OOMPH_CALENDLY_URL : '' );
$has_calendly = ( '' !== trim( (string) $calendly_url ) );

// Resolve the intake form id by title (no hard-coded id).
$form_shortcode = '';
if ( function_exists( 'wpFluent' ) ) {
	$form_row = wpFluent()->table( 'fluentform_forms' )->where( 'title', 'Discovery Call Intake' )->first();
	if ( $form_row ) {
		$form_shortcode = '[fluentform id="' . (int) $form_row->id . '"]';
	}
}
?>

<main id="primary" class="oomph-discovery" role="main">

	<a class="skip-link sr-only sr-only-focusable" href="#oomph-content">Skip to main content</a>
	<div id="oomph-content"></div>

	<?php /* 1. HERO ---------------------------------------------------- */ ?>
	<section class="oomph-hero oomph-hero--imaged" aria-label="Book a discovery call">
		<?php
		echo oomph_picture(
			'heroes/discovery-hero.jpg',
			array(
				'width'  => 1600,
				'height' => 1067,
				'class'  => 'oomph-hero__media',
				'lcp'    => true,
			)
		); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — escaped in helper.
		?>
		<div class="oomph-container oomph-hero__inner">
			<p class="oomph-eyebrow"><?php echo esc_html( strtoupper( $hero_eyebrow ) ); ?></p>
			<h1 class="oomph-hero__headline oomph-italic-display"><?php echo esc_html( $hero_headline ); ?></h1>
			<p class="oomph-hero__subhead"><?php echo esc_html( $hero_subhead ); ?></p>
			<p class="oomph-hero__cta">
				<a class="oomph-btn oomph-btn--primary" href="#book">
					Start a conversation <span aria-hidden="true">&rarr;</span>
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
		<span class="oomph-trust-strip__item">Port Angeles &middot; WA</span>
	</aside>
	<?php endif; ?>

	<?php /* 3. WHAT HAPPENS ON THE CALL -------------------------------- */ ?>
	<section class="oomph-section" aria-labelledby="what-happens-title">
		<div class="oomph-container">
			<div class="oomph-section__intro">
				<p class="oomph-eyebrow">What happens on the call</p>
				<h2 id="what-happens-title">Thirty minutes, and you will know.</h2>
			</div>
			<div class="oomph-grid oomph-grid--3">
				<div>
					<p class="oomph-eyebrow">One &middot; Talk</p>
					<h3 class="oomph-italic-display" style="font-size: var(--text-h3);">Tell me about the trip.</h3>
					<p>Who is going, when, where you have already been, what you would never repeat. The more specific, the better the plan that follows.</p>
				</div>
				<div>
					<p class="oomph-eyebrow">Two &middot; Fit</p>
					<h3 class="oomph-italic-display" style="font-size: var(--text-h3);">We see if I am the right advisor.</h3>
					<p>I plan premium cruises and custom European journeys. If your trip is in my lane, I will tell you. If it is not, I will say so and point you somewhere better.</p>
				</div>
				<div>
					<p class="oomph-eyebrow">Three &middot; Next</p>
					<h3 class="oomph-italic-display" style="font-size: var(--text-h3);">You leave with a next step.</h3>
					<p>Either we start planning, or you walk away with a clearer idea of what your trip takes. No pressure, no obligation, no hard sell.</p>
				</div>
			</div>
		</div>
	</section>

	<?php /* 4. BOOK — Calendly inline ---------------------------------- */ ?>
	<section id="book" class="oomph-section is-style-oomph-quiet-premium" aria-labelledby="book-title">
		<div class="oomph-container oomph-container--prose">
			<p class="oomph-eyebrow">Pick a time</p>
			<h2 id="book-title">Book your 30-minute call.</h2>
			<?php if ( $has_calendly ) : ?>
				<div
					class="calendly-inline-widget oomph-calendly"
					data-url="<?php echo esc_url( $calendly_url ); ?>"
					style="min-width:320px;height:680px;"
				></div>
				<?php
				// Third-party Calendly embed script (approved as part of this build).
				wp_enqueue_script(
					'calendly-widget',
					'https://assets.calendly.com/assets/external/widget.js',
					array(),
					null,
					true
				);
				?>
				<div class="oomph-calendly__confirm" data-calendly-confirm hidden role="status" aria-live="polite" style="text-align:center;padding:var(--space-8) 0;">
					<p class="oomph-eyebrow">You're booked</p>
					<h3 class="oomph-italic-display" style="font-size: var(--text-h2); margin-bottom: var(--space-4);">Your call is on the calendar.</h3>
					<p>Check your inbox for the confirmation and the calendar invite — I'll come prepared. See you then.</p>
					<p class="oomph-quiz__cta" style="justify-content:center;">
						<a class="oomph-btn oomph-btn--primary" href="/">Back to home <span aria-hidden="true">&rarr;</span></a>
						<a class="oomph-btn oomph-btn--ghost" href="/journal/">Read the journal</a>
					</p>
				</div>
			<?php else : ?>
				<div class="oomph-calendly oomph-calendly--placeholder" role="note" style="border:1px solid var(--hairline);border-radius:var(--radius-md);padding:var(--space-7);text-align:center;">
					<p style="margin:0 0 var(--space-3);"><strong>Calendly booking loads here.</strong></p>
					<p style="margin:0;color:var(--text-muted);">Set the booking link with <code>add_filter('oomph_calendly_url', fn() =&gt; 'https://calendly.com/&hellip;')</code> or define <code>OOMPH_CALENDLY_URL</code>.</p>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<?php /* 5. INTAKE FORM --------------------------------------------- */ ?>
	<section class="oomph-section" aria-labelledby="details-title">
		<div class="oomph-container oomph-container--prose">
			<div class="oomph-section__intro">
				<p class="oomph-eyebrow">Prefer to send details first?</p>
				<h2 id="details-title">Tell me about the trip.</h2>
			</div>
			<?php
			if ( '' !== $form_shortcode ) {
				echo do_shortcode( $form_shortcode ); // phpcs:ignore WordPress.Security.EscapeOutput
			} else {
				echo '<p>The intake form is being set up. Please use the booking calendar above for now.</p>';
			}
			?>
			<p class="oomph-form__privacy">We respect your inbox. Used solely to plan your trip.</p>
		</div>
	</section>

</main>

<?php /* GA4 generate_lead on Fluent Forms success (R11). No-op until GA4 is live. */ ?>
<script>
( function () {
	// Fluent Forms fires this as a jQuery event on document.body.
	if ( ! window.jQuery ) { return; }
	window.jQuery( document.body ).on( 'fluentform_submission_success', function () {
		window.dataLayer = window.dataLayer || [];
		window.dataLayer.push( { event: 'generate_lead', lead_source: 'discovery_call_form' } );
		if ( typeof window.gtag === 'function' ) {
			window.gtag( 'event', 'generate_lead', { lead_source: 'discovery_call_form' } );
		}
		if ( typeof window.clarity === 'function' ) {
			window.clarity( 'set', 'lead', 'discovery_call_form' );
		}
	} );
}() );
</script>
<script>
// When Calendly confirms a booking, swap the widget for an on-page confirmation
// with clear exits — otherwise the visitor is stranded on Calendly's own screen.
( function () {
	window.addEventListener( 'message', function ( e ) {
		if ( ! e.data || typeof e.data !== 'object' || e.data.event !== 'calendly.event_scheduled' ) {
			return;
		}
		var widget  = document.querySelector( '.calendly-inline-widget' );
		var confirm = document.querySelector( '[data-calendly-confirm]' );
		var book    = document.getElementById( 'book' );
		if ( widget ) { widget.style.display = 'none'; }
		if ( confirm ) { confirm.hidden = false; }
		if ( book ) { book.scrollIntoView( { behavior: 'smooth', block: 'start' } ); }
		window.dataLayer = window.dataLayer || [];
		window.dataLayer.push( { event: 'generate_lead', lead_source: 'calendly_booking' } );
		if ( typeof window.gtag === 'function' ) {
			window.gtag( 'event', 'generate_lead', { lead_source: 'calendly_booking' } );
		}
		if ( typeof window.clarity === 'function' ) {
			window.clarity( 'set', 'lead', 'calendly_booking' );
		}
	} );
}() );
</script>

<?php /* Sticky mobile CTA — R2. Scrolls to the booking section. */ ?>
<aside class="oomph-sticky-cta" aria-label="Quick contact">
	<a class="oomph-btn oomph-btn--primary" href="#book">
		Start a conversation <span aria-hidden="true">&rarr;</span>
	</a>
</aside>

<?php
get_footer();
