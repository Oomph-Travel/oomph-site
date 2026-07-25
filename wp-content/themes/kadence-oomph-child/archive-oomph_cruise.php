<?php
/**
 * Group Cruise archive — /group-cruises/.
 *
 * Branded listing of published, upcoming sailings (Hosted Group Cruises,
 * Distinctive Voyages, Amenity Departures), soonest first, filterable by
 * cruise line and region. Query shaping + the card renderer live in
 * inc/cruise-archive.php. Event schema is per-sailing on the single pages,
 * not here.
 *
 * @package OomphChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$lines       = oomph_sailing_lines();
$regions     = oomph_sailing_regions();
$sel_line    = isset( $_GET['line'] ) ? sanitize_text_field( wp_unslash( $_GET['line'] ) ) : '';
$sel_region  = isset( $_GET['region'] ) ? sanitize_title( wp_unslash( $_GET['region'] ) ) : '';
$sel_type    = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : '';
$sel_type    = in_array( $sel_type, array( 'hosted', 'amenity' ), true ) ? $sel_type : '';
$has_filters = ( '' !== $sel_line || '' !== $sel_region || '' !== $sel_type );
$archive_url = get_post_type_archive_link( 'oomph_cruise' );
?>

<main id="primary" class="oomph-cruise oomph-cruise-archive" role="main">
	<a class="skip-link sr-only sr-only-focusable" href="#oomph-content">Skip to main content</a>
	<div id="oomph-content"></div>

	<section class="oomph-dv-hero oomph-cruise-archive__hero">
		<div class="oomph-dv-hero__inner">
			<p class="oomph-eyebrow oomph-dv-hero__eyebrow">Group Cruises · Distinctive Voyages · Amenity departures</p>
			<h1 class="oomph-dv-hero__title oomph-italic-display">Sailings worth booking with me.</h1>
			<p class="oomph-dv-hero__ship">The departures I host myself, the Distinctive Voyages sailings with a private shore event, and the wider list of sailings carrying amenities I've arranged through my Travel Leaders Network membership — all at the same fare you'd pay booking direct, with more included.</p>
		</div>
	</section>

	<div class="oomph-container oomph-cruise-archive__body">

		<?php /* Filters always render — the sailing-type select is always useful. */ ?>
		<form class="oomph-cruise-filters" method="get" action="<?php echo esc_url( (string) $archive_url ); ?>" aria-label="Filter sailings">
			<?php if ( ! empty( $lines ) ) : ?>
			<label class="oomph-cruise-filters__field">
				<span>Cruise line</span>
				<select name="line">
					<option value="">All lines</option>
					<?php foreach ( $lines as $line ) : ?>
						<option value="<?php echo esc_attr( $line ); ?>" <?php selected( $sel_line, $line ); ?>><?php echo esc_html( $line ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<?php endif; ?>

			<?php if ( ! empty( $regions ) ) : ?>
			<label class="oomph-cruise-filters__field">
				<span>Region</span>
				<select name="region">
					<option value="">All regions</option>
					<?php foreach ( $regions as $region ) : ?>
						<option value="<?php echo esc_attr( $region->slug ); ?>" <?php selected( $sel_region, $region->slug ); ?>><?php echo esc_html( $region->name ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<?php endif; ?>

			<label class="oomph-cruise-filters__field">
				<span>Sailing type</span>
				<select name="type">
					<option value="">All sailings</option>
					<option value="hosted" <?php selected( $sel_type, 'hosted' ); ?>>Hosted &amp; Distinctive Voyages</option>
					<option value="amenity" <?php selected( $sel_type, 'amenity' ); ?>>Amenity departures</option>
				</select>
			</label>

			<button type="submit" class="oomph-btn oomph-btn--primary">Show sailings</button>
			<?php if ( $has_filters ) : ?>
				<a class="oomph-cruise-filters__clear" href="<?php echo esc_url( (string) $archive_url ); ?>">Clear</a>
			<?php endif; ?>
		</form>

		<?php if ( have_posts() ) : ?>
			<div class="oomph-grid oomph-grid--3 oomph-cruise-archive__grid">
				<?php
				while ( have_posts() ) :
					the_post();
					oomph_render_sailing_card( get_the_ID() );
				endwhile;
				?>
			</div>

			<?php
			the_posts_pagination(
				array(
					'mid_size'           => 2,
					'prev_text'          => 'Previous',
					'next_text'          => 'Next',
					'screen_reader_text' => 'Sailings navigation',
				)
			);
			?>
		<?php else : ?>
			<div class="oomph-cruise-archive__empty">
				<p><?php echo $has_filters ? 'No sailings match those filters right now.' : "I don't have any sailings open at the moment."; ?></p>
				<?php if ( $has_filters ) : ?>
					<p><a class="oomph-btn oomph-btn--ghost" href="<?php echo esc_url( (string) $archive_url ); ?>">See all sailings</a></p>
				<?php else : ?>
					<p>Tell me what you're looking for and I'll let you know what's coming up.</p>
					<p><a class="oomph-btn oomph-btn--primary" href="/discovery-call/">Start a conversation</a></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>

	</div>

	<section class="oomph-dv-cta">
		<p class="oomph-dv-label oomph-dv-cta__label">Not sure which fits?</p>
		<h2 class="oomph-italic-display">Tell me how you like to sail.</h2>
		<p class="oomph-dv-cta__lede">Whether it's one of these departures or something I plan from scratch, start with a conversation and I'll point you to the right one.</p>
		<p>
			<a class="oomph-btn oomph-btn--primary" href="/discovery-call/">Start a conversation</a>
			<span class="oomph-btn-microcopy oomph-dv-cta__micro">Email, text, or a quick call — whatever's easiest for you.</span>
		</p>
	</section>

</main>

<aside class="oomph-sticky-cta" aria-label="Quick contact">
	<a class="oomph-btn oomph-btn--primary" href="/discovery-call/">Start a conversation</a>
</aside>

<?php
get_footer();
