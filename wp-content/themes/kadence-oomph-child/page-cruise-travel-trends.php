<?php
/**
 * Cruise Travel Trends — lead-magnet landing page.
 *
 * Bound by slug to /cruise-travel-trends/. Single email field (CRO R43),
 * posting to Plainsend's "trends-guide" form.
 *
 * This replaced a Fluent Forms embed that emailed a permanent public link to
 * the PDF in the media library — forwardable, uncountable, and impossible to
 * withdraw. Plainsend sends each person their own link that expires, counts
 * downloads, and confirms the address before anything is sent. The cost is one
 * extra click: the guide follows the confirmation rather than arriving with it.
 *
 * @package OomphChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$inside = array(
	'The Slow Cruise — why every line is sailing longer.',
	'Eleven ships worth knowing.',
	'Where the compass is pointing — the itineraries on the rise.',
	'The rivers, redrawn.',
	"Who's sailing now — solos, couples, three generations.",
	'The all-inclusive question.',
	'A first cruise, done right.',
);

?>

<main id="primary" class="oomph-leadpage" role="main">
	<a class="skip-link sr-only sr-only-focusable" href="#oomph-content">Skip to main content</a>
	<div id="oomph-content"></div>

	<section class="oomph-section oomph-hero" aria-labelledby="trends-title">
		<div class="oomph-container">
			<div class="oomph-grid oomph-grid--2 oomph-leadmagnet">

				<div class="oomph-leadmagnet__copy">
					<p class="oomph-eyebrow">Field Guide · Vol. I</p>
					<h1 id="trends-title" class="oomph-hero__headline oomph-italic-display">What's worth watching at sea in 2026 and 2027.</h1>
					<p class="oomph-hero__subhead">A 24-page field guide to where cruising is actually going — written by one advisor who sails it, not a marketing team. The shifts worth knowing before you book your next voyage.</p>

					<div class="oomph-leadpage__form">
						<?php
						oomph_signup_form(
							'trends-guide',
							array(
								'label'  => 'Email address',
								'button' => 'Send me the guide',
								'source' => 'trends-guide',
							)
						);
						?>
						<p class="oomph-form__privacy">Confirm with the button in the email and the guide follows straight after. One guide, the occasional field note, and nothing else.</p>
					</div>
				</div>

				<aside class="oomph-leadpage__inside" aria-labelledby="inside-title">
					<p class="oomph-eyebrow">Inside this issue</p>
					<h2 id="inside-title" class="oomph-h3">Seven things worth your time.</h2>
					<ul class="oomph-bullets">
						<?php foreach ( $inside as $item ) : ?>
							<li><?php echo esc_html( $item ); ?></li>
						<?php endforeach; ?>
					</ul>
					<p class="oomph-leadpage__meta">— <?php echo esc_html( oomph_advisor_name() ); ?> · Port Angeles, Washington</p>
				</aside>

			</div>
		</div>
	</section>
</main>

<?php
get_footer();
