<?php
/**
 * Single Group Cruise (oomph_cruise).
 *
 * Branches on the ACF `sailing_type`:
 *   • distinctive_voyage → event-led Distinctive Voyages layout
 *     (docs/reference/dv-sailing-mockup.html), built from real fields with
 *     the site's Deep Marine tokens. No pricing or cabins bar — that data
 *     does not exist for these sailings.
 *   • hosted_group / amenity_departure → hosted group-cruise layout with
 *     pricing, real cabins-remaining scarcity, and the day-by-day itinerary.
 *
 * Event schema (with subEvents for shore events on DV) is emitted by the
 * oomph-travel-core plugin's class-schema.php — never here.
 *
 * Booking references (Voyage / US Group / CA Group #) are intentionally never
 * read into markup.
 *
 * @package OomphChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	$get = static function ( string $name ) {
		return function_exists( 'get_field' ) ? get_field( $name ) : null;
	};

	$sailing_type = (string) ( $get( 'sailing_type' ) ?: 'hosted_group' );
	$ship         = trim( (string) $get( 'cruise_ship_name' ) );
	$line         = trim( (string) $get( 'cruise_line' ) );
	$start        = (string) $get( 'cruise_dates_start' );
	$end          = (string) $get( 'cruise_dates_end' );
	$embark       = trim( (string) $get( 'embark_city' ) );
	$disembark    = trim( (string) $get( 'disembark_city' ) );

	// Itinerary label lives in the post title ("{Itinerary} — {Ship}, {F Y}").
	$title_full = get_the_title();
	$title_bits = explode( ' — ', $title_full );
	$itinerary  = count( $title_bits ) > 1 ? $title_bits[0] : $title_full;

	// Nights from the real dates — a derivation, never a guess.
	$nights = ( $start && $end ) ? (int) round( ( strtotime( $end ) - strtotime( $start ) ) / DAY_IN_SECONDS ) : 0;

	// Date helpers.
	$fmt_day = static function ( string $ymd ): string {
		return $ymd ? date_i18n( 'F j, Y', strtotime( $ymd ) ) : '';
	};
	$date_range = '';
	if ( $start && $end ) {
		$s = strtotime( $start );
		$e = strtotime( $end );
		if ( gmdate( 'Y-m', $s ) === gmdate( 'Y-m', $e ) ) {
			$date_range = date_i18n( 'F j', $s ) . '–' . date_i18n( 'j, Y', $e );
		} else {
			$date_range = date_i18n( 'M j', $s ) . ' – ' . date_i18n( 'M j, Y', $e );
		}
	} elseif ( $start ) {
		$date_range = $fmt_day( $start );
	}

	$route = ( $embark && $disembark ) ? "{$embark} to {$disembark}" : ( $embark ?: $disembark );

	// "Why this sailing" is the editorial post_content; render only when Eric
	// has replaced the import placeholder (fail-safe if a draft slips through).
	$why_raw     = get_the_content();
	$why_is_stub = ( '' === trim( $why_raw ) ) || ( false !== strpos( $why_raw, 'WHY THIS SAILING' ) && false !== strpos( $why_raw, 'Eric writes' ) );

	// Discovery CTA prefill line (displayed; the CTA still points at /discovery-call/).
	$prefill = sprintf( "I'm interested in %s, %s — %s", $ship, $fmt_day( $start ), $itinerary );

	$is_dv = ( 'distinctive_voyage' === $sailing_type );

	if ( $is_dv ) :

		/* ================================================================
		 * DISTINCTIVE VOYAGES — event-led layout
		 * ================================================================ */

		// Shore events 1–3 — collect only populated slots.
		$events = array();
		for ( $n = 1; $n <= 3; $n++ ) {
			$ename = trim( (string) $get( "shore_event_{$n}_name" ) );
			$edate = (string) $get( "shore_event_{$n}_date" );
			$eloc  = trim( (string) $get( "shore_event_{$n}_location" ) );
			if ( '' === $ename && '' === $edate && '' === $eloc ) {
				continue;
			}
			$events[] = array(
				'name'     => $ename,
				'date'     => $edate,
				'time'     => trim( (string) $get( "shore_event_{$n}_time" ) ),
				'length'   => trim( (string) $get( "shore_event_{$n}_length_hours" ) ),
				'location' => $eloc,
				'details'  => trim( (string) $get( "shore_event_{$n}_details" ) ),
			);
		}
		$lead_event = $events[0] ?? null;

		// Amenity comparison rows — driven by data, never the mockup's samples.
		// amenity_details lines when present, else the single amenity_summary.
		$amenity_details = trim( (string) $get( 'amenity_details' ) );
		$amenity_summary = trim( (string) $get( 'amenity_summary' ) );
		$amenity_rows    = array();
		if ( '' !== $amenity_details ) {
			foreach ( preg_split( '/\r\n|\r|\n/', $amenity_details ) as $line_txt ) {
				$line_txt = trim( $line_txt );
				if ( '' !== $line_txt ) {
					$amenity_rows[] = $line_txt;
				}
			}
		} elseif ( '' !== $amenity_summary ) {
			$amenity_rows[] = $amenity_summary;
		}

		$line_label = $line !== '' ? $line : 'the cruise line';
		?>

		<main id="primary" class="oomph-dv" role="main">
			<a class="skip-link sr-only sr-only-focusable" href="#oomph-content">Skip to main content</a>
			<div id="oomph-content"></div>

			<?php /* HERO — event-led when there's a shore event, itinerary-led otherwise. */ ?>
			<section class="oomph-dv-hero"<?php echo has_post_thumbnail() ? ' style="background-image: ' . esc_attr( 'var(--scrim-hero)' ) . ', url(' . esc_url( (string) get_the_post_thumbnail_url( null, 'full' ) ) . ');"' : ''; ?>>
				<div class="oomph-dv-hero__inner">
					<?php if ( $lead_event ) : ?>
						<p class="oomph-eyebrow oomph-dv-hero__eyebrow">
							<?php
							$eyebrow_bits = array_filter( array( $lead_event['location'], $fmt_day( $lead_event['date'] ), 'One sailing only' ) );
							echo esc_html( implode( '  ·  ', $eyebrow_bits ) );
							?>
						</p>
						<h1 class="oomph-dv-hero__title oomph-italic-display"><?php echo esc_html( $lead_event['name'] ); ?></h1>
						<p class="oomph-dv-hero__ship">
							A private shore event reserved for my guests aboard <strong><?php echo esc_html( $ship ); ?></strong><?php
							$tail = trim( ( $nights ? "{$nights}-night " : '' ) . ( $route ? "{$route} " : '' ) . 'sailing' );
							if ( $line || $date_range ) {
								echo ' — ' . ( $line ? esc_html( $line ) . '’s ' : '' ) . esc_html( $tail ) . ( $date_range ? ', ' . esc_html( $date_range ) : '' ) . '.';
							} else {
								echo '.';
							}
							?>
						</p>
					<?php else : ?>
						<p class="oomph-eyebrow oomph-dv-hero__eyebrow"><?php echo esc_html( trim( 'Distinctive Voyages' . ( $route ? '  ·  ' . $route : '' ) ) ); ?></p>
						<h1 class="oomph-dv-hero__title oomph-italic-display"><?php echo esc_html( $itinerary ); ?></h1>
						<p class="oomph-dv-hero__ship">
							<?php echo esc_html( trim( ( $line ? $line . '’s ' : '' ) . ( $nights ? "{$nights}-night " : '' ) . $ship . ' sailing' . ( $date_range ? ", {$date_range}" : '' ) ) ); ?>.
						</p>
					<?php endif; ?>

					<div class="oomph-dv-hero__ctas">
						<a class="oomph-btn oomph-btn--primary" href="/discovery-call/">Start a conversation</a>
						<?php if ( $lead_event && $lead_event['location'] ) : ?>
							<a class="oomph-btn oomph-btn--ghost-light" href="#event">See the day in <?php echo esc_html( $lead_event['location'] ); ?></a>
						<?php endif; ?>
					</div>
					<p class="oomph-dv-hero__note">Same <?php echo esc_html( $line_label ); ?> fare you'd pay booking direct. The difference is what's included.</p>
				</div>
			</section>

			<div class="oomph-dv-wrap">
				<div class="oomph-dv-grid">
					<div class="oomph-dv-main">

						<?php /* WHY THIS SAILING — editorial post_content, only when written. */ ?>
						<?php if ( ! $why_is_stub ) : ?>
							<section class="oomph-dv-block">
								<p class="oomph-dv-label">Why this sailing</p>
								<div class="oomph-prose oomph-dv-why"><?php the_content(); ?></div>
							</section>
						<?php endif; ?>

						<?php /* SAME FARE. MORE INCLUDED. — functional comparison, data-driven. */ ?>
						<?php if ( ! empty( $amenity_rows ) || $lead_event ) : ?>
							<section class="oomph-dv-block">
								<div class="oomph-dv-compare">
									<p class="oomph-dv-label">The honest math</p>
									<h2>Same fare. More included.</h2>
									<p class="oomph-dv-compare__sub"><?php echo esc_html( $line_label !== 'the cruise line' ? "{$line} sets the cruise fare — it's identical whether you book direct or with me. What changes is everything that comes with it." : "The cruise fare is identical whether you book direct or with me. What changes is everything that comes with it." ); ?></p>
									<div class="oomph-dv-compare__grid">
										<div class="oomph-dv-compare__col">
											<h3>Booking direct</h3>
											<p class="oomph-dv-compare__fare">The published fare</p>
											<p class="oomph-dv-compare__item"><span class="oomph-dv-compare__mark" aria-hidden="true">✓</span> <?php echo esc_html( trim( ( $nights ? "The {$nights}-night " : 'The ' ) . ( $route ? "{$route} " : '' ) . 'sailing' ) ); ?></p>
											<?php foreach ( $amenity_rows as $row_txt ) : ?>
												<p class="oomph-dv-compare__item is-off"><span class="oomph-dv-compare__mark" aria-hidden="true">—</span> <?php echo esc_html( $row_txt ); ?></p>
											<?php endforeach; ?>
											<?php if ( $lead_event && $lead_event['name'] ) : ?>
												<p class="oomph-dv-compare__item is-off"><span class="oomph-dv-compare__mark" aria-hidden="true">—</span> Exclusive shore event<?php echo $lead_event['location'] ? ' in ' . esc_html( $lead_event['location'] ) : ''; ?></p>
											<?php endif; ?>
										</div>
										<div class="oomph-dv-compare__col is-mine">
											<h3>Booking with me</h3>
											<p class="oomph-dv-compare__fare">The same published fare</p>
											<p class="oomph-dv-compare__item"><span class="oomph-dv-compare__mark" aria-hidden="true">✓</span> <?php echo esc_html( trim( ( $nights ? "The {$nights}-night " : 'The ' ) . ( $route ? "{$route} " : '' ) . 'sailing' ) ); ?></p>
											<?php foreach ( $amenity_rows as $row_txt ) : ?>
												<p class="oomph-dv-compare__item"><span class="oomph-dv-compare__mark" aria-hidden="true">✓</span> <?php echo esc_html( $row_txt ); ?></p>
											<?php endforeach; ?>
											<?php if ( $lead_event && $lead_event['name'] ) : ?>
												<p class="oomph-dv-compare__item"><span class="oomph-dv-compare__mark" aria-hidden="true">✓</span> Exclusive shore event<?php echo $lead_event['location'] ? ' in ' . esc_html( $lead_event['location'] ) : ''; ?></p>
											<?php endif; ?>
										</div>
									</div>
									<p class="oomph-dv-compare__foot">Booking with me also means one advisor across the whole booking. These amenities come through my Travel Leaders Network membership and apply only to this departure.</p>
								</div>
							</section>
						<?php endif; ?>

						<?php /* SHORE EVENT TICKETS — one card per populated event. */ ?>
						<?php if ( ! empty( $events ) ) : ?>
							<section class="oomph-dv-block" id="event">
								<p class="oomph-dv-label">The exclusive shore event<?php echo count( $events ) > 1 ? 's' : ''; ?></p>
								<h2><?php echo count( $events ) > 1 ? 'What I’ve arranged ashore' : 'One morning ' . esc_html( $lead_event['location'] ? 'in ' . $lead_event['location'] : 'ashore' ); ?></h2>
								<?php foreach ( $events as $ev ) : ?>
									<div class="oomph-dv-ticket">
										<div class="oomph-dv-ticket__visual" aria-hidden="true"></div>
										<div class="oomph-dv-ticket__body">
											<p class="oomph-dv-label">Included with this sailing</p>
											<h3 class="oomph-italic-display"><?php echo esc_html( $ev['name'] ); ?></h3>
											<table class="oomph-dv-ticket__meta">
												<tbody>
													<?php if ( $ev['date'] ) : ?><tr><th>Date</th><td><?php echo esc_html( date_i18n( 'l, F j, Y', strtotime( $ev['date'] ) ) ); ?></td></tr><?php endif; ?>
													<?php if ( $ev['time'] ) : ?><tr><th>Departs</th><td><?php echo esc_html( $ev['time'] ); ?></td></tr><?php endif; ?>
													<?php if ( '' !== $ev['length'] ) : ?><tr><th>Length</th><td><?php echo esc_html( rtrim( rtrim( $ev['length'], '0' ), '.' ) ); ?> hours</td></tr><?php endif; ?>
													<?php if ( $ev['location'] ) : ?><tr><th>Port</th><td><?php echo esc_html( $ev['location'] ); ?></td></tr><?php endif; ?>
												</tbody>
											</table>
											<?php if ( $ev['details'] ) : ?>
												<p><?php echo esc_html( $ev['details'] ); ?></p>
											<?php else : ?>
												<p>Reserved for Distinctive Voyages guests on this departure. Full event details arrive with your booking documents.</p>
											<?php endif; ?>
											<span class="oomph-dv-ticket__once">This event runs once — then it's gone</span>
										</div>
									</div>
								<?php endforeach; ?>
							</section>
						<?php endif; ?>

						<?php /* WHAT COMES WITH IT — narrative, only when Eric supplied details. */ ?>
						<?php if ( '' !== $amenity_details ) : ?>
							<section class="oomph-dv-block">
								<p class="oomph-dv-label">What comes with it</p>
								<div class="oomph-prose"><?php echo wp_kses_post( wpautop( $amenity_details ) ); ?></div>
							</section>
						<?php endif; ?>

						<?php /* FAQ — program-level, sailing-agnostic. */ ?>
						<section class="oomph-dv-block">
							<p class="oomph-dv-label">Questions I get asked</p>
							<h2>Before you ask</h2>
							<div class="oomph-dv-faq">
								<?php
								$dv_faqs = apply_filters(
									'oomph_dv_faqs',
									array(
										array(
											'q' => 'Does booking through you cost more?',
											'a' => 'No. The cruise fare is set by the cruise line and is the same whether you book direct or with me. The added amenities come through my network membership at no extra cost to you.',
										),
										array(
											'q' => 'Is this a group cruise? Will I be locked into group activities?',
											'a' => 'No. You sail as an independent guest with full run of the ship. Any welcome reception or shore event is an invitation, not an obligation.',
										),
										array(
											'q' => 'What if the sailing sells out or the amenity program fills?',
											'a' => "Amenity space on these departures is limited and separate from the ship's overall capacity. If it fills, I'll tell you straight and we'll look at the next departure that carries the same inclusions.",
										),
										array(
											'q' => 'Can you also handle flights, hotels, and the days around the cruise?',
											'a' => 'Yes — pre- and post-cruise planning is most of what I do. Time on either end of the sailing is usually worth building in, and I map that with you.',
										),
									)
								);
								foreach ( $dv_faqs as $faq ) :
									?>
									<details class="oomph-dv-faq__item">
										<summary><?php echo esc_html( $faq['q'] ); ?></summary>
										<div class="oomph-prose"><p><?php echo esc_html( $faq['a'] ); ?></p></div>
									</details>
								<?php endforeach; ?>
							</div>
						</section>

					</div>

					<?php /* AT A GLANCE — sticky sidebar. */ ?>
					<aside class="oomph-dv-side" aria-label="Sailing at a glance">
						<h2 class="oomph-dv-side__title oomph-italic-display">At a glance</h2>
						<table class="oomph-dv-side__table">
							<tbody>
								<?php if ( $ship ) : ?><tr><th>Ship</th><td><?php echo esc_html( $ship ); ?></td></tr><?php endif; ?>
								<?php if ( $line ) : ?><tr><th>Line</th><td><?php echo esc_html( $line ); ?></td></tr><?php endif; ?>
								<?php if ( $route ) : ?><tr><th>Route</th><td><?php echo esc_html( $route ); ?></td></tr><?php endif; ?>
								<?php if ( $date_range ) : ?><tr><th>Dates</th><td><?php echo esc_html( $date_range ); ?></td></tr><?php endif; ?>
								<?php if ( $nights ) : ?><tr><th>Nights</th><td><?php echo esc_html( (string) $nights ); ?></td></tr><?php endif; ?>
								<tr><th>Program</th><td>Distinctive Voyages</td></tr>
								<?php if ( $lead_event ) : ?><tr><th>Event</th><td><?php echo esc_html( trim( ( $lead_event['location'] ?: '' ) . ( $lead_event['date'] ? ' · ' . date_i18n( 'M j', strtotime( $lead_event['date'] ) ) : '' ) ) ); ?></td></tr><?php endif; ?>
							</tbody>
						</table>
						<a class="oomph-btn oomph-btn--primary oomph-dv-side__cta" href="/discovery-call/">Start a conversation</a>
						<p class="oomph-dv-side__foot">Fare set by <?php echo esc_html( $line_label ); ?>. Amenities added through my Travel Leaders Network membership at no additional cost.</p>
					</aside>
				</div>
			</div>

			<?php /* FINAL CTA */ ?>
			<section class="oomph-dv-cta" id="cta">
				<p class="oomph-dv-label oomph-dv-cta__label">Next step</p>
				<h2 class="oomph-italic-display">Interested in this sailing?</h2>
				<p class="oomph-dv-cta__lede">Tell me about your travelers and your dates. If this departure isn't the right fit, I'll say so and find the one that is.</p>
				<p class="oomph-dv-cta__prefill">“<?php echo esc_html( $prefill ); ?>”</p>
				<p>
					<a class="oomph-btn oomph-btn--primary" href="/discovery-call/">Start a conversation</a>
					<span class="oomph-btn-microcopy oomph-dv-cta__micro">Email, text, or a quick call — whatever's easiest for you.</span>
				</p>
			</section>

		</main>

	<?php else : ?>

		<?php
		/* ================================================================
		 * HOSTED GROUP CRUISE (and amenity_departure) — commercial layout
		 * ================================================================ */
		$price        = $get( 'cruise_price_per_person' );
		$single_supp  = $get( 'cruise_single_supplement' );
		$cabins       = $get( 'cruise_cabins_remaining' );
		$deposit      = $get( 'cruise_deposit_amount' );
		$deposit_ddl  = (string) $get( 'cruise_deposit_deadline' );
		$itinerary_rows = $get( 'cruise_itinerary' );
		$inclusions   = trim( (string) $get( 'cruise_inclusions' ) );
		$exclusions   = trim( (string) $get( 'cruise_exclusions' ) );
		$region_terms = get_the_terms( get_the_ID(), 'oomph_region' );
		$region_name  = ( is_array( $region_terms ) && $region_terms ) ? $region_terms[0]->name : '';
		$eyebrow_lead = ( 'amenity_departure' === $sailing_type ) ? 'Amenity Departure' : 'Hosted Group Cruise';
		$has_thumb    = has_post_thumbnail();
		?>

		<main id="primary" class="oomph-cruise" role="main">
			<a class="skip-link sr-only sr-only-focusable" href="#oomph-content">Skip to main content</a>
			<div id="oomph-content"></div>

			<section class="oomph-hero <?php echo $has_thumb ? 'oomph-hero--imaged' : 'oomph-cruise-hero--plain'; ?>" aria-label="<?php echo esc_attr( $itinerary ); ?>">
				<?php if ( $has_thumb ) : ?>
					<picture class="oomph-hero__media">
						<img src="<?php echo esc_url( (string) get_the_post_thumbnail_url( null, 'full' ) ); ?>" alt="" width="1600" height="1067" fetchpriority="high" decoding="async">
					</picture>
				<?php endif; ?>
				<div class="oomph-container oomph-hero__inner">
					<p class="oomph-eyebrow"><?php echo esc_html( strtoupper( trim( $eyebrow_lead . ( $region_name ? ' · ' . $region_name : '' ) ) ) ); ?></p>
					<h1 class="oomph-hero__headline oomph-italic-display"><?php echo esc_html( $itinerary ); ?></h1>
					<p class="oomph-hero__subhead"><?php echo esc_html( trim( implode( ' · ', array_filter( array( $line, $ship, $route, $date_range ) ) ) ) ); ?></p>
					<p class="oomph-hero__cta">
						<a class="oomph-btn oomph-btn--primary" href="/discovery-call/">Start a conversation</a>
						<span class="oomph-btn-microcopy">Email, text, or a quick call — whatever's easiest for you.</span>
					</p>
				</div>
			</section>

			<section class="oomph-section" aria-label="Sailing details">
				<div class="oomph-container oomph-cruise-grid">
					<div class="oomph-cruise-main">

						<?php if ( ! $why_is_stub ) : ?>
							<div class="oomph-prose"><?php the_content(); ?></div>
						<?php endif; ?>

						<?php if ( is_array( $itinerary_rows ) && ! empty( $itinerary_rows ) ) : ?>
							<div class="oomph-cruise-itin">
								<p class="oomph-eyebrow">Day by day</p>
								<h2>The itinerary.</h2>
								<?php foreach ( $itinerary_rows as $day ) : ?>
									<?php
									$d_num  = is_array( $day ) ? ( $day['day'] ?? '' ) : '';
									$d_port = is_array( $day ) ? trim( (string) ( $day['port'] ?? '' ) ) : '';
									$d_act  = is_array( $day ) ? trim( (string) ( $day['activity'] ?? '' ) ) : '';
									if ( '' === $d_port && '' === $d_act ) {
										continue;
									}
									?>
									<details class="oomph-cruise-itin__day">
										<summary><span class="oomph-cruise-itin__num"><?php echo esc_html( $d_num ? 'Day ' . $d_num : '—' ); ?></span> <?php echo esc_html( $d_port ); ?></summary>
										<?php if ( $d_act ) : ?><div class="oomph-prose"><p><?php echo esc_html( $d_act ); ?></p></div><?php endif; ?>
									</details>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<?php if ( '' !== $inclusions || '' !== $exclusions ) : ?>
							<div class="oomph-cruise-incl">
								<?php if ( '' !== $inclusions ) : ?>
									<div class="oomph-cruise-incl__col">
										<h2>What's included.</h2>
										<div class="oomph-prose"><?php echo wp_kses_post( wpautop( $inclusions ) ); ?></div>
									</div>
								<?php endif; ?>
								<?php if ( '' !== $exclusions ) : ?>
									<div class="oomph-cruise-incl__col">
										<h2>What isn't.</h2>
										<div class="oomph-prose"><?php echo wp_kses_post( wpautop( $exclusions ) ); ?></div>
									</div>
								<?php endif; ?>
							</div>
						<?php endif; ?>

					</div>

					<aside class="oomph-cruise-side" aria-label="At a glance">
						<h2 class="oomph-dv-side__title oomph-italic-display">At a glance</h2>
						<table class="oomph-dv-side__table">
							<tbody>
								<?php if ( $ship ) : ?><tr><th>Ship</th><td><?php echo esc_html( $ship ); ?></td></tr><?php endif; ?>
								<?php if ( $line ) : ?><tr><th>Line</th><td><?php echo esc_html( $line ); ?></td></tr><?php endif; ?>
								<?php if ( $route ) : ?><tr><th>Route</th><td><?php echo esc_html( $route ); ?></td></tr><?php endif; ?>
								<?php if ( $date_range ) : ?><tr><th>Dates</th><td><?php echo esc_html( $date_range ); ?></td></tr><?php endif; ?>
								<?php if ( $nights ) : ?><tr><th>Nights</th><td><?php echo esc_html( (string) $nights ); ?></td></tr><?php endif; ?>
								<?php if ( is_numeric( $price ) && $price > 0 ) : ?><tr><th>From</th><td>$<?php echo esc_html( number_format( (float) $price ) ); ?> pp</td></tr><?php endif; ?>
								<?php if ( is_numeric( $single_supp ) && $single_supp > 0 ) : ?><tr><th>Single</th><td>+$<?php echo esc_html( number_format( (float) $single_supp ) ); ?></td></tr><?php endif; ?>
								<?php if ( is_numeric( $deposit ) && $deposit > 0 ) : ?><tr><th>Deposit</th><td>$<?php echo esc_html( number_format( (float) $deposit ) ); ?><?php echo $deposit_ddl ? ' by ' . esc_html( $fmt_day( $deposit_ddl ) ) : ''; ?></td></tr><?php endif; ?>
							</tbody>
						</table>
						<?php if ( is_numeric( $cabins ) && $cabins > 0 ) : ?>
							<p class="oomph-cruise-side__scarcity"><?php echo esc_html( (int) $cabins ); ?> cabin<?php echo (int) $cabins === 1 ? '' : 's'; ?> remaining</p>
						<?php endif; ?>
						<a class="oomph-btn oomph-btn--primary oomph-dv-side__cta" href="/discovery-call/">Start a conversation</a>
						<p class="oomph-dv-side__foot">One advisor across the whole booking — first call to last flight home.</p>
					</aside>
				</div>
			</section>

			<section class="oomph-section is-style-oomph-cabin-notes" aria-labelledby="cruise-final-cta">
				<div class="oomph-container" style="text-align:center;">
					<p class="oomph-eyebrow" style="color: var(--color-champagne);">First call</p>
					<h2 id="cruise-final-cta" class="oomph-italic-display" style="font-size: var(--text-h1); max-width: 24ch; margin-inline:auto;">Interested in this sailing?</h2>
					<p class="oomph-cruise-cta__prefill">“<?php echo esc_html( $prefill ); ?>”</p>
					<p style="margin-top: var(--space-6);">
						<a class="oomph-btn oomph-btn--inverse" href="/discovery-call/">Start a conversation</a>
						<span class="oomph-btn-microcopy" style="color: var(--color-champagne);">Email, text, or a quick call — whatever's easiest for you.</span>
					</p>
				</div>
			</section>

		</main>

	<?php endif; ?>

	<?php /* Sticky mobile CTA — R2. */ ?>
	<aside class="oomph-sticky-cta" aria-label="Quick contact">
		<a class="oomph-btn oomph-btn--primary" href="/discovery-call/">Start a conversation</a>
	</aside>

	<?php
endwhile;

get_footer();
