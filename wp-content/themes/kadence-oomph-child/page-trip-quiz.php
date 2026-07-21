<?php
/**
 * Cabin Quiz — "What's your cruise cabin match?"
 *
 * Bound by slug to /trip-quiz/. A custom 7-question quiz that scores to one of
 * five cabin profiles. Flow (driven by assets/js/cabin-quiz.js): intro → one
 * question at a time → email gate (Fluent Forms "Cabin Quiz" form) → result
 * reveal. Email capture stores the lead + the chosen profile; the Cabin
 * Selection Guide email is wired once that PDF exists.
 *
 * Scoring lives in the JS: Q5=C → Multi-Gen; Q6=A → Suite; Q1=A + value → Mid-Ship;
 * Q6=C → Port Hopper; else Balcony.
 *
 * @package OomphChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$questions = array(
	array(
		'q' => 'When the ship moves, you want to…',
		'a' => array(
			'A' => "Not feel it. I'd like to sleep, eat, and read without noticing.",
			'B' => 'Feel it gently. Part of being at sea.',
			'C' => 'Feel it. The rock is half the point.',
			'D' => "I don't know — I've never been.",
		),
	),
	array(
		'q' => 'On a sea day, where are you?',
		'a' => array(
			'A' => 'On my balcony or in the cabin most of the day.',
			'B' => 'In and out — naps, a chapter, a change of clothes.',
			'C' => 'Out of the cabin entirely. Public rooms, deck, spa, lectures.',
			'D' => 'Wherever the bar is.',
		),
	),
	array(
		'q' => 'How much does natural light in the cabin matter?',
		'a' => array(
			'A' => "Essential. I can't sleep — or wake up — in a windowless room.",
			'B' => "A window is enough. I don't need a balcony for the daylight.",
			'C' => 'Not important. Blackout is better.',
		),
	),
	array(
		'q' => "What's the trip shape?",
		'a' => array(
			'A' => 'Mostly sea days — transatlantic, Hawaii, a world segment.',
			'B' => 'A mix — Mediterranean, Caribbean, Alaska, Northern Europe.',
			'C' => 'Port-intensive — off the ship by 8 a.m. most mornings.',
		),
	),
	array(
		'q' => "Who's sailing with you?",
		'a' => array(
			'A' => 'Just me.',
			'B' => 'My partner. One cabin.',
			'C' => 'Two or three generations across multiple cabins.',
			'D' => 'A friend or family member. We may want connecting or adjoining.',
		),
	),
	array(
		'q' => 'On cabin spend, you\'re closer to…',
		'a' => array(
			'A' => '"Best cabin available — this trip is the occasion."',
			'B' => '"Best value — I want a balcony, I don\'t need a butler."',
			'C' => '"Lowest sensible — I\'d rather spend on excursions and dining."',
		),
	),
	array(
		'q' => 'What wakes you up at night?',
		'a' => array(
			'A' => 'Anything. A chair scraping a deck above me. Plumbing.',
			'B' => 'Loud, sudden noise — a steady hum I sleep through.',
			'C' => "Almost nothing. I'd sleep through a fire drill.",
		),
	),
);

$profiles = array(
	'suite' => array(
		'name' => 'The Suite Set',
		'body' => 'You want the cabin to be the trip. A concierge or full suite on a premium or luxury line — Silversea, Regent, Seabourn, Crystal, Cunard Grills. Mid-ship, mid-deck, with a real verandah and a bathtub that isn\'t an afterthought. The ship\'s restaurants, the butler-coordinated dining, the priority embarkation — that\'s the value, and it stops being optional above a certain trip length.',
		'ask'  => 'Which suite category includes specialty dining, premium spirits, and excursions in the fare? On luxury lines those are often inclusive; on premium lines they\'re not.',
	),
	'balcony' => array(
		'name' => 'The Balcony Believer',
		'body' => 'You want a verandah and a mid-ship location, on a premium line. Not a suite, not an interior. The balcony is where the morning coffee happens and where the sunset goes. Skip obstructed-view categories — the savings rarely justify staring at a lifeboat for seven days.',
		'ask'  => 'What deck is directly above and below your cabin? You want passenger cabins, not the pool deck and not the galley.',
	),
	'midship' => array(
		'name' => 'The Mid-Ship Strategist',
		'body' => "You're motion-sensitive and value-driven. The cabin to book is an ocean-view or balcony on a low-to-mid deck, as close to the ship's center as the deck plan allows. Forward and aft cabins move more. High decks move more. You don't need a balcony to enjoy the trip — but you do need to sleep.",
		'ask'  => 'On this specific ship class, where does the natural motion break? On smaller ultra-luxury ships the answer is different than on a 4,000-passenger megaship.',
	),
	'porthopper' => array(
		'name' => 'The Port Hopper',
		'body' => "You're off the ship by mid-morning and back by dinner. The cabin is a place to sleep, shower, and change. An interior or a low-category ocean-view, mid-ship, on a deck above the public rooms and below the pool deck, is the right answer — and the money saved goes into the excursions and the specialty dining that turn the trip from a hotel-on-water into the place you actually came to see.",
		'ask'  => 'Where on this ship are the quiet interior categories — and which ones share a wall with crew areas, elevators, or self-serve laundries?',
	),
	'multigen' => array(
		'name' => 'The Multi-Gen Coordinator',
		'body' => "You're booking three or more cabins, and the planning question is how those cabins relate to each other. Connecting cabins for the kids and grandparents. Same-deck adjacency so no one is on deck 7 while the rest of the family is on deck 11. A meeting cabin — usually the largest — that the family can gather in for a drink before dinner. Cabin choice here is a coordination problem, not a category problem.",
		'ask'  => 'Which categories actually offer connecting doors on this ship — not just "adjoining" cabins? On most ships the answer is fewer than you\'d think, and they sell out 9–12 months ahead.',
	),
);

// Resolve the Cabin Quiz Fluent Forms shortcode.
$quiz_form = '';
if ( function_exists( 'wpFluent' ) ) {
	$cqf = wpFluent()->table( 'fluentform_forms' )->where( 'title', 'Cabin Quiz' )->first();
	if ( $cqf ) {
		$quiz_form = '[fluentform id="' . (int) $cqf->id . '"]';
	}
}
?>

<main id="primary" class="oomph-quiz" role="main">
	<a class="skip-link sr-only sr-only-focusable" href="#oomph-content">Skip to main content</a>
	<div id="oomph-content"></div>

	<section class="oomph-section" aria-labelledby="quiz-title">
		<div class="oomph-container oomph-container--prose">

			<?php /* INTRO */ ?>
			<div class="oomph-quiz__intro" data-quiz-step="intro">
				<p class="oomph-eyebrow">Cruise · Cabin Note</p>
				<h1 id="quiz-title" class="oomph-italic-display">What's your cruise cabin match?</h1>
				<p class="oomph-hero__subhead">The cabin you choose determines the trip you have. Same ship, same itinerary — two cabins three decks apart can be a different vacation. Answer seven questions. I'll point you to the category that fits how you actually sail.</p>
				<p>
					<button type="button" class="oomph-btn oomph-btn--primary" data-quiz-start>Start the quiz <span aria-hidden="true">&rarr;</span></button>
				</p>
			</div>

			<?php /* QUESTIONS */ ?>
			<form class="oomph-quiz__questions" data-quiz-step="questions" hidden>
				<p class="oomph-quiz__progress" data-quiz-progress aria-live="polite"></p>
				<?php foreach ( $questions as $i => $question ) : $num = $i + 1; ?>
					<fieldset class="oomph-quiz__q" data-quiz-q="<?php echo esc_attr( $num ); ?>"<?php echo $i > 0 ? ' hidden' : ''; ?>>
						<legend class="oomph-quiz__question"><?php echo esc_html( $question['q'] ); ?></legend>
						<div class="oomph-quiz__options">
							<?php foreach ( $question['a'] as $letter => $text ) : ?>
								<button type="button" class="oomph-quiz__opt" data-letter="<?php echo esc_attr( $letter ); ?>"><?php echo esc_html( $text ); ?></button>
							<?php endforeach; ?>
						</div>
						<?php if ( $num > 1 ) : ?>
							<button type="button" class="oomph-quiz__back" data-quiz-back>&larr; Back</button>
						<?php endif; ?>
					</fieldset>
				<?php endforeach; ?>
			</form>

			<?php /* EMAIL GATE */ ?>
			<div class="oomph-quiz__gate" data-quiz-step="gate" hidden>
				<p class="oomph-eyebrow">Almost there</p>
				<h2>Where should I send your results?</h2>
				<p>I'll include the full Cabin Selection Guide — wave zones by ship class, deck-by-deck noise notes, and the three booking mistakes I see most often.</p>
				<?php
				if ( $quiz_form ) {
					echo do_shortcode( $quiz_form ); // phpcs:ignore WordPress.Security.EscapeOutput
				} else {
					echo '<p><em>The results form is being set up.</em></p>';
				}
				?>
				<p class="oomph-form__privacy">We respect your inbox. Used solely to plan your trip.</p>
			</div>

			<?php /* RESULTS */ ?>
			<div class="oomph-quiz__results" data-quiz-step="results" hidden>
				<?php foreach ( $profiles as $key => $p ) : ?>
					<div class="oomph-quiz__result" data-profile="<?php echo esc_attr( $key ); ?>" hidden>
						<p class="oomph-eyebrow">Your cabin profile</p>
						<h2 class="oomph-italic-display"><?php echo esc_html( $p['name'] ); ?></h2>
						<p><?php echo esc_html( $p['body'] ); ?></p>
						<p class="oomph-quiz__ask"><strong>What to ask before booking:</strong> <?php echo esc_html( $p['ask'] ); ?></p>
					</div>
				<?php endforeach; ?>

				<div class="oomph-quiz__thanks">
					<p>I've sent the full Cabin Selection Guide to your inbox. It's the same brief I send clients before we book.</p>
					<p>The quiz gets you to the category. The actual booking — which deck, which side of the ship, which cabin numbers to avoid on this specific class of vessel — is where I spend my time. If you're sailing in the next 12 months and want a second set of eyes before you commit to a cabin, the 30-minute call is the next step.</p>
					<p class="oomph-quiz__cta">
						<a class="oomph-btn oomph-btn--primary" href="/discovery-call/">Start a conversation <span aria-hidden="true">&rarr;</span></a>
						<a class="oomph-btn oomph-btn--ghost" href="/luxury-cruise-planning/">No, just the guide for now</a>
					</p>
				</div>
			</div>

		</div>
	</section>
</main>

<?php
get_footer();
