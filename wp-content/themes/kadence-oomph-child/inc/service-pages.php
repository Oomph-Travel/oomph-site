<?php
/**
 * Data-driven service hub pages (Custom Italy, Multi-Generational).
 *
 * Content lives here as a per-slug data array (version-controlled, deploys via
 * git — no DB/ACF dependency). `oomph_render_service_page( $slug )` renders the
 * markup; the thin page-{slug}.php templates just call it. FAQs are exposed to
 * the plugin's schema via the `oomph_page_faqs` filter so the FAQPage JSON-LD
 * stays single-sourced with what's on the page.
 *
 * The existing Luxury Cruise Planning page uses its own service-page.php
 * template + ACF; these two are intentionally code-managed instead.
 *
 * TODO (Eric): verify the region lists, group sizes, and any specifics flagged
 * inline before treating this copy as final.
 *
 * @package OomphChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Content for a service hub page, keyed by slug. Returns array|null.
 */
function oomph_service_page_data( string $slug ): ?array {
	$data = array(

		'custom-italy-travel' => array(
			'eyebrow'  => 'Service · Custom Italy',
			'headline' => 'Italy, planned by someone who keeps going back.',
			'subhead'  => 'Custom, region-by-region Italian journeys — built around how you travel, not a packaged tour. Private drivers, vetted local guides, and the stays that actually deliver, with one named advisor from the first call to the last flight home.',
			'for_title'    => 'Who this is for',
			'for'      => array(
				'You want an itinerary built around your pace and your interests — not a fixed group tour.',
				"You're marking something: an anniversary, a milestone birthday, a long-promised trip.",
				'You\'d rather have one advisor who has walked these towns than a 1-800 number.',
			),
			'not_title'    => "Who this isn't for",
			'not'      => array(
				'You want the cheapest package and will book whatever is on sale.',
				"You've already built the whole trip and only need someone to click \"book.\"",
				'You want five countries in ten days. I plan slower trips than that.',
			),
			'do_title' => 'What I actually do',
			'do'       => array(
				array( 'Region-by-region itinerary design.', 'A plan built one region at a time — the town worth a night, the one worth an afternoon, and the drive between them that\'s worth doing in daylight.' ),
				array( 'Stays that live up to the photos.', 'Agriturismi, family-run hotels, and the villa rentals that deliver. I steer you away from the ones that photograph better than they live.' ),
				array( 'Private drivers and vetted guides.', 'The guide who makes a hill town come alive, and the driver who knows the back way when the coast road is jammed.' ),
				array( 'Restaurant strategy and reservations.', 'Not a list off the internet — the places worth planning a day around, booked before you land.' ),
				array( 'Pacing built around your energy.', 'Mornings you\'ll actually use, afternoons that aren\'t a forced march. The plan bends to you, not the other way around.' ),
				array( 'Logistics that trip people up.', 'Trains, transfers, internal flights, the ZTL driving zones and the luggage hand-offs — handled before they become a problem.' ),
			),
			'why_title' => 'Italy rewards local knowledge.',
			'why'      => array(
				array( 'The right town, the right amount of time.', 'Most Italy itineraries spend a day where you want three and three where you want one. Knowing the difference is the whole job.' ),
				array( 'The villa photos sometimes lie.', 'I know which properties deliver, which ones have a hard bed and a hot kitchen, and which views are worth the extra night.' ),
				array( 'When something closes, one call.', 'A strike, a festival, a sudden closure — it happens on every trip. With an advisor it\'s one phone call instead of a scramble in a town where you don\'t speak the language.' ),
				array( 'One person across the whole trip.', 'Not a different agent for hotels, transfers, and guides. One inbox, one phone number, first call to last flight home.' ),
			),
			'coverage_eyebrow' => 'Coverage',
			'coverage_title' => 'Regions I plan.',
			'coverage' => array(
				array( 'Puglia', 'The heel of Italy — trulli towns, sea-salt air, and the stretch between Lecce and Polignano a Mare.' ),
				array( 'Sicily', 'Baroque towns, Etna, and the markets. A region that rewards a driver who knows the roads.' ),
				array( 'Tuscany &amp; the hill towns', 'Florence as an anchor, then the slower country — Siena, the Val d\'Orcia, a villa with a view.' ),
				array( 'The Lakes', 'Como, Maggiore, Garda. Grand-hotel mornings and boat afternoons.' ),
				array( 'The Dolomites', 'Mountain air and serious food, for the trip that wants altitude over coastline.' ),
				array( 'The Amalfi Coast', 'Positano, Ravello, the boat days. Best paced with somewhere quiet to come back to.' ),
			),
			'coverage_note' => 'Rome, Florence, and Venice anchor most trips. Working toward a formal Italy Destination Specialist certification.',
			'faqs' => array(
				array( 'Do you charge a planning fee?', 'No — I don\'t charge a planning fee. Suppliers pay a commission on what you book, and that commission doesn\'t change your price. You get itinerary design, the stays and guides worth booking, and the logistics handled — at no added cost to you.' ),
				array( 'Can you take over a trip I\'ve already started planning?', 'Often, yes. The earlier I\'m involved the more I can shape — but if you\'ve already booked a hotel or two, I can build the rest of the trip around them. Bring what you have to the call and I\'ll tell you honestly where I can add value.' ),
				array( 'How far ahead should I start?', 'For spring and fall in the popular regions, six to nine months is comfortable — the best villas and guides book early. I\'ve turned around shorter timelines, but the runway buys you the good options.' ),
				array( 'Do you book flights?', 'I advise on routing and timing and coordinate flights with the rest of the trip. International air is often best handled a particular way; I\'ll tell you when it\'s worth using miles and when it isn\'t.' ),
				array( 'Which regions do you plan?', 'Puglia, Sicily, Tuscany, the Lakes, the Dolomites, the Amalfi Coast, and the classic anchors — Rome, Florence, Venice. If you have a region in mind that isn\'t listed, ask.' ),
				array( 'Can you plan an Italy trip for three generations?', 'Yes — multi-generational Italy is one of the things I plan most. Pace, mobility, and dietary needs change the plan; see my multi-generational planning page, or just mention it on the call.' ),
			),
		),

		'multi-generational-travel-planning' => array(
			'eyebrow'  => 'Service · Multi-Generational',
			'headline' => 'The trip that works for everyone — planned around the slowest walker.',
			'subhead'  => 'Multi-generational travel is a hundred small decisions, not one big one. Pace, mobility, dietary needs, room configurations, and the special-occasion choreography — planned so the trip works for the grandparents, the parents, the teens, and the toddler.',
			'for_title'    => 'Who this is for',
			'for'      => array(
				"You're planning a trip across two or three generations and want it to actually land.",
				'Someone in the group has mobility or dietary needs that the plan has to respect.',
				"You're marking a milestone — a big anniversary, a reunion, a once-in-a-decade gathering.",
			),
			'not_title'    => "Who this isn't for",
			'not'      => array(
				'Everyone travels the same way and a standard package would do fine.',
				"You've already booked the rooms and flights and just need them confirmed.",
				'You want the lowest price above all else, even if the trip is a strain to take.',
			),
			'do_title' => 'What I actually do',
			'do'       => array(
				array( 'Plan around the constraints first.', 'Pace for the slowest walker, food for the pickiest eater, naps for the toddler. Get those right and the highlights take care of themselves.' ),
				array( 'Room and cabin configurations that work.', 'Connecting rooms, adjacent cabins, a suite for the grandparents near the family. Booked together while there\'s still inventory to hold.' ),
				array( 'Mobility and accessibility, handled.', 'Step-free routes, accessible rooms, transfers that fit a wheelchair, ports and properties briefed before you arrive.' ),
				array( 'Dietary needs briefed ahead.', 'Allergies and restrictions communicated to kitchens in advance, so dinner isn\'t a negotiation every night.' ),
				array( 'Special-occasion choreography.', 'The anniversary dinner, the surprise, the group photo at the right hour — the small things that make the trip the one they talk about.' ),
				array( 'A plan B for the hard days.', 'A rest afternoon when someone\'s tired, a backup for weather, a quiet option when the group needs to split for a few hours.' ),
			),
			'why_title' => 'The trip works, or it doesn\'t. There\'s no middle ground.',
			'why'      => array(
				array( 'One plan, many needs.', 'A toddler\'s nap and a grandparent\'s knee and a teenager\'s boredom all in one itinerary. Balancing them is the work, and it takes someone who has done it before.' ),
				array( 'The details that derail a group.', 'A restaurant that can\'t seat ten. A transfer that doesn\'t fit a wheelchair. A room with stairs nobody mentioned. I find these before you do.' ),
				array( 'When the day shifts, one call.', 'Someone gets tired, weather turns, a plan falls through — with an advisor, it\'s one phone call instead of fifteen people standing on a corner deciding what to do.' ),
				array( 'One person across the whole trip.', 'Not a different contact for each piece. One inbox and one phone number, from the first call to the last flight home.' ),
			),
			'coverage_eyebrow' => 'Where these trips go',
			'coverage_title' => 'Cruise or land — whatever fits the group.',
			'coverage' => array(
				array( 'Premium &amp; luxury cruises', 'A cruise solves the "different interests, one base" problem — everyone together at dinner, off doing their own thing by day. Connecting cabins held together.' ),
				array( 'Custom Italy &amp; Europe', 'A villa or a cluster of rooms for the family that just wants to be together, with day trips paced for every age.' ),
				array( 'Milestone reunions', 'Big anniversaries, landmark birthdays, the gathering that pulls everyone to one place for once.' ),
				array( 'Special-occasion trips', 'When the trip itself is the gift, and it has to land.' ),
			),
			'coverage_note' => 'Not sure whether a cruise or a land trip fits your group? That\'s exactly what the discovery call is for.',
			'faqs' => array(
				array( 'What makes a multi-generational trip different to plan?', 'It\'s a hundred small decisions, not one. Pace for the slowest walker, food for the pickiest eater, room configurations that actually work, and a plan B for the day someone needs to rest. I plan around the constraints first, then the highlights.' ),
				array( 'Do you charge a planning fee?', 'No — I don\'t charge a planning fee. Suppliers pay a commission on what you book, and that doesn\'t change your price — so you get the planning a multi-generational trip genuinely takes at no added cost to you.' ),
				array( 'How many people can you plan for?', 'From a trip with the grandparents to a full reunion of fifteen or more across several rooms or cabins. The larger the group, the earlier we should start.' ),
				array( 'Can you handle mobility and dietary needs?', 'Yes — that\'s central to how I plan, not an afterthought. Step-free routes, accessible rooms, transfers that fit a wheelchair, kitchens briefed on allergies. Tell me what you\'re working with and I\'ll build around it.' ),
				array( 'Cruise or land for a multi-gen trip?', 'Both work; it depends on the group. A cruise solves the "different interests, one base" problem; a villa solves the "we just want to be together" one. I\'ll talk you through the trade-offs on the call.' ),
				array( 'How far ahead should we book?', 'For groups, sooner is better — connecting rooms, adjacent cabins, and large tables book up first. Six to twelve months out for peak season.' ),
			),
		),

	);

	return $data[ $slug ] ?? null;
}

/**
 * Feed the plugin's FAQPage schema for these code-managed pages.
 */
add_filter(
	'oomph_page_faqs',
	static function ( $faqs, $post ) {
		if ( ! $post ) {
			return $faqs;
		}
		$data = oomph_service_page_data( $post->post_name );
		if ( $data && ! empty( $data['faqs'] ) ) {
			$out = array();
			foreach ( $data['faqs'] as $row ) {
				$out[] = array( 'question' => $row[0], 'answer' => wp_strip_all_tags( $row[1] ) );
			}
			return $out;
		}
		return $faqs;
	},
	10,
	2
);

/**
 * Render a full service hub page from its data array.
 */
function oomph_render_service_page( string $slug ): void {
	$d = oomph_service_page_data( $slug );
	if ( ! $d ) {
		echo '<main class="oomph-service"><div class="oomph-container"><p>Service not found.</p></div></main>';
		return;
	}
	?>
	<main id="primary" class="oomph-service" role="main">
		<a class="skip-link sr-only sr-only-focusable" href="#oomph-content">Skip to main content</a>
		<div id="oomph-content"></div>

		<?php /* 1. HERO */ ?>
		<section class="oomph-section oomph-hero" aria-label="<?php echo esc_attr( wp_strip_all_tags( $d['headline'] ) ); ?>">
			<div class="oomph-container oomph-hero__inner">
				<p class="oomph-eyebrow"><?php echo esc_html( strtoupper( $d['eyebrow'] ) ); ?></p>
				<h1 class="oomph-hero__headline oomph-italic-display"><?php echo esc_html( $d['headline'] ); ?></h1>
				<p class="oomph-hero__subhead"><?php echo esc_html( $d['subhead'] ); ?></p>
				<p class="oomph-hero__cta">
					<a class="oomph-btn oomph-btn--primary" href="/discovery-call/">Book a Discovery Call <span aria-hidden="true">&rarr;</span></a>
					<span class="oomph-btn-microcopy">Free 30-minute call. No pressure, no obligation.</span>
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

		<?php /* 3. WHO FOR / NOT FOR */ ?>
		<section class="oomph-section is-style-oomph-quiet-premium" aria-labelledby="who-for-title">
			<div class="oomph-container">
				<div class="oomph-section__intro">
					<p class="oomph-eyebrow">Pre-qualifying</p>
					<h2 id="who-for-title">Who this is for, and who it isn't.</h2>
				</div>
				<div class="oomph-grid oomph-grid--2 oomph-who-for">
					<div class="oomph-who-for__col">
						<h3 class="oomph-card__headline"><?php echo esc_html( $d['for_title'] ); ?></h3>
						<ul class="oomph-bullets">
							<?php foreach ( $d['for'] as $b ) : ?><li><?php echo esc_html( $b ); ?></li><?php endforeach; ?>
						</ul>
					</div>
					<div class="oomph-who-for__col">
						<h3 class="oomph-card__headline"><?php echo esc_html( $d['not_title'] ); ?></h3>
						<ul class="oomph-bullets oomph-bullets--negative">
							<?php foreach ( $d['not'] as $b ) : ?><li><?php echo esc_html( $b ); ?></li><?php endforeach; ?>
						</ul>
					</div>
				</div>
			</div>
		</section>

		<?php /* 4. WHAT I DO */ ?>
		<section class="oomph-section" aria-labelledby="do-title">
			<div class="oomph-container">
				<div class="oomph-section__intro">
					<p class="oomph-eyebrow">Deliverables</p>
					<h2 id="do-title"><?php echo esc_html( $d['do_title'] ); ?></h2>
				</div>
				<div class="oomph-grid oomph-grid--3">
					<?php foreach ( $d['do'] as $row ) : ?>
						<article class="oomph-card">
							<h3 class="oomph-card__headline"><?php echo esc_html( $row[0] ); ?></h3>
							<p><?php echo esc_html( $row[1] ); ?></p>
						</article>
					<?php endforeach; ?>
				</div>
				<p class="oomph-section__cta" style="text-align: center; margin-top: var(--space-7);">
					<a class="oomph-btn oomph-btn--primary" href="/discovery-call/">Book a Discovery Call <span aria-hidden="true">&rarr;</span></a>
				</p>
			</div>
		</section>

		<?php /* 5. WHY AN ADVISOR */ ?>
		<section class="oomph-section is-style-oomph-cabin-notes" aria-labelledby="why-title">
			<div class="oomph-container oomph-container--prose">
				<p class="oomph-eyebrow" style="color: var(--color-champagne);">Why an advisor</p>
				<h2 id="why-title" class="oomph-italic-display" style="color: var(--color-paper); font-size: var(--text-h1);"><?php echo esc_html( $d['why_title'] ); ?></h2>
				<div style="color: var(--color-paper);">
					<?php foreach ( $d['why'] as $row ) : ?>
						<p><strong><?php echo esc_html( $row[0] ); ?></strong> <?php echo esc_html( $row[1] ); ?></p>
					<?php endforeach; ?>
				</div>
				<p style="margin-top: var(--space-7);">
					<a class="oomph-btn oomph-btn--inverse" href="/discovery-call/">Book a Discovery Call <span aria-hidden="true">&rarr;</span></a>
				</p>
			</div>
		</section>

		<?php /* 6. COVERAGE */ ?>
		<section class="oomph-section" aria-labelledby="coverage-title">
			<div class="oomph-container">
				<div class="oomph-section__intro">
					<p class="oomph-eyebrow"><?php echo esc_html( $d['coverage_eyebrow'] ); ?></p>
					<h2 id="coverage-title"><?php echo esc_html( $d['coverage_title'] ); ?></h2>
				</div>
				<div class="oomph-grid oomph-grid--2">
					<?php foreach ( $d['coverage'] as $row ) : ?>
						<article class="oomph-card oomph-card--bordered">
							<h3 class="oomph-card__headline"><?php echo wp_kses_post( $row[0] ); ?></h3>
							<p><?php echo wp_kses_post( $row[1] ); ?></p>
						</article>
					<?php endforeach; ?>
				</div>
				<?php if ( ! empty( $d['coverage_note'] ) ) : ?>
					<p class="oomph-coverage__note" style="margin-top: var(--space-5); color: var(--text-muted);"><em><?php echo esc_html( $d['coverage_note'] ); ?></em></p>
				<?php endif; ?>
			</div>
		</section>

		<?php /* 7. HOW I WORK */ ?>
		<section class="oomph-section" aria-labelledby="how-title">
			<div class="oomph-container">
				<div class="oomph-section__intro">
					<p class="oomph-eyebrow">How I work</p>
					<h2 id="how-title">Three steps. One conversation to start.</h2>
				</div>
				<div class="oomph-grid oomph-grid--3">
					<div>
						<p class="oomph-eyebrow">Step One &middot; Discover</p>
						<h3 class="oomph-italic-display" style="font-size: var(--text-h3);">A free 30-minute call.</h3>
						<p>We talk about the trip you're imagining — who's going, when, where you've already been, what you'd never do again. By the end I know whether I'm the right advisor for you, and you know what comes next.</p>
					</div>
					<div>
						<p class="oomph-eyebrow">Step Two &middot; Design</p>
						<h3 class="oomph-italic-display" style="font-size: var(--text-h3);">One proposal, not five.</h3>
						<p>I do the narrowing for you and bring back one plan we refine together — not a stack of options to sort through yourself.</p>
					</div>
					<div>
						<p class="oomph-eyebrow">Step Three &middot; Depart</p>
						<h3 class="oomph-italic-display" style="font-size: var(--text-h3);">Eyes on it the whole time.</h3>
						<p>If something changes while you're traveling, I'm reachable. The point of an advisor isn't the planning; it's the person on call when the day shifts.</p>
					</div>
				</div>
			</div>
		</section>

		<?php /* 8. FAQ */ ?>
		<section class="oomph-section is-style-oomph-european-itinerary" aria-labelledby="faq-title">
			<div class="oomph-container oomph-container--prose">
				<p class="oomph-eyebrow">Frequently asked</p>
				<h2 id="faq-title">The questions I get most.</h2>
				<div class="oomph-faq">
					<?php foreach ( $d['faqs'] as $row ) : ?>
						<details class="oomph-faq__item">
							<summary class="oomph-faq__question"><?php echo esc_html( $row[0] ); ?></summary>
							<div class="oomph-faq__answer"><?php echo wp_kses_post( wpautop( $row[1] ) ); ?></div>
						</details>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<?php /* 9. FINAL CTA */ ?>
		<section class="oomph-section is-style-oomph-cabin-notes" aria-labelledby="final-cta-title">
			<div class="oomph-container" style="text-align: center;">
				<p class="oomph-eyebrow" style="color: var(--color-champagne);">First call</p>
				<h2 id="final-cta-title" class="oomph-italic-display" style="font-size: var(--text-h1); max-width: 22ch; margin-inline: auto;">Worth a thirty-minute call?</h2>
				<p style="margin-top: var(--space-6);">
					<a class="oomph-btn oomph-btn--inverse" href="/discovery-call/">Book a Discovery Call <span aria-hidden="true">&rarr;</span></a>
					<span class="oomph-btn-microcopy" style="color: var(--color-champagne);">Free 30-minute call. No pressure, no obligation.</span>
				</p>
			</div>
		</section>
	</main>

	<aside class="oomph-sticky-cta" aria-label="Quick contact">
		<a class="oomph-btn oomph-btn--primary" href="/discovery-call/">Book a Discovery Call <span aria-hidden="true">&rarr;</span></a>
	</aside>
	<?php
}
