<?php
/**
 * Custom site footer.
 *
 * Renders a full-width brand footer (brand + explore + contact + newsletter,
 * a credentials/social strip, and a bottom bar) on Kadence's
 * `kadence_before_footer` hook, and hides Kadence's default footer markup
 * via a body class + CSS so the two don't stack.
 *
 * Content is here in code (version-controlled, deploys via git). Social URLs
 * are filterable via `oomph_social_links`; the newsletter embeds a Fluent
 * Forms form titled "Newsletter Signup" (created by scripts/seed-content.php).
 *
 * @package OomphChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Social profile URLs. Confirm/replace with the real profiles before launch.
 * Only platforms with a non-empty URL render an icon.
 */
function oomph_social_links(): array {
	return apply_filters(
		'oomph_social_links',
		array(
			'facebook'  => 'https://www.facebook.com/profile.php?id=100087673102528',
			'instagram' => 'https://www.instagram.com/oomph_travel/',
			'linkedin'  => 'https://www.linkedin.com/in/erichempeloomphtravel/',
		)
	);
}

/**
 * Minimal inline SVG icons (currentColor) for the footer social row.
 */
function oomph_social_icon( string $network ): string {
	$icons = array(
		'facebook'  => '<path d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06c0 5 3.66 9.15 8.44 9.94v-7.03H7.9v-2.9h2.54V9.85c0-2.51 1.49-3.9 3.78-3.9 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56v1.87h2.78l-.44 2.9h-2.34V22c4.78-.79 8.44-4.94 8.44-9.94Z"/>',
		'instagram' => '<path d="M12 2c2.72 0 3.06.01 4.12.06 1.07.05 1.8.22 2.43.47.66.25 1.22.6 1.77 1.15.55.55.9 1.11 1.15 1.77.25.63.42 1.36.47 2.43.05 1.07.06 1.4.06 4.12s-.01 3.06-.06 4.12c-.05 1.07-.22 1.8-.47 2.43a4.9 4.9 0 0 1-1.15 1.77c-.55.55-1.11.9-1.77 1.15-.63.25-1.36.42-2.43.47-1.07.05-1.4.06-4.12.06s-3.06-.01-4.12-.06c-1.07-.05-1.8-.22-2.43-.47a4.9 4.9 0 0 1-1.77-1.15 4.9 4.9 0 0 1-1.15-1.77c-.25-.63-.42-1.36-.47-2.43C2.01 15.06 2 14.72 2 12s.01-3.06.06-4.12c.05-1.07.22-1.8.47-2.43.25-.66.6-1.22 1.15-1.77.55-.55 1.11-.9 1.77-1.15.63-.25 1.36-.42 2.43-.47C8.94 2.01 9.28 2 12 2Zm0 1.8c-2.67 0-2.99.01-4.04.06-.98.04-1.5.21-1.86.35-.47.18-.8.4-1.15.75-.35.35-.57.68-.75 1.15-.14.36-.31.88-.35 1.86-.05 1.05-.06 1.37-.06 4.04s.01 2.99.06 4.04c.04.98.21 1.5.35 1.86.18.47.4.8.75 1.15.35.35.68.57 1.15.75.36.14.88.31 1.86.35 1.05.05 1.37.06 4.04.06s2.99-.01 4.04-.06c.98-.04 1.5-.21 1.86-.35.47-.18.8-.4 1.15-.75.35-.35.57-.68.75-1.15.14-.36.31-.88.35-1.86.05-1.05.06-1.37.06-4.04s-.01-2.99-.06-4.04c-.04-.98-.21-1.5-.35-1.86a3.1 3.1 0 0 0-.75-1.15 3.1 3.1 0 0 0-1.15-.75c-.36-.14-.88-.31-1.86-.35-1.05-.05-1.37-.06-4.04-.06Zm0 3.07a5.13 5.13 0 1 1 0 10.26 5.13 5.13 0 0 1 0-10.26Zm0 1.8a3.33 3.33 0 1 0 0 6.66 3.33 3.33 0 0 0 0-6.66Zm5.34-3.2a1.2 1.2 0 1 1 0 2.4 1.2 1.2 0 0 1 0-2.4Z"/>',
		'linkedin'  => '<path d="M20.45 20.45h-3.56v-5.57c0-1.33-.02-3.04-1.85-3.04-1.85 0-2.14 1.45-2.14 2.94v5.67H9.34V9h3.42v1.56h.05c.48-.9 1.64-1.85 3.37-1.85 3.6 0 4.27 2.37 4.27 5.46v6.28ZM5.34 7.43a2.07 2.07 0 1 1 0-4.13 2.07 2.07 0 0 1 0 4.13ZM7.12 20.45H3.55V9h3.57v11.45ZM22.22 0H1.77C.79 0 0 .77 0 1.73v20.54C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.73V1.73C24 .77 23.2 0 22.22 0Z"/>',
	);
	if ( empty( $icons[ $network ] ) ) {
		return '';
	}
	return '<svg class="oomph-social__icon" viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true" focusable="false">' . $icons[ $network ] . '</svg>';
}

/**
 * Render the custom footer.
 */
function oomph_render_footer(): void {
	$mark = get_stylesheet_directory_uri() . '/assets/images/logo-mark.svg';

	// Explore — only link pages that exist and are published.
	$explore = array();
	foreach ( array(
		'about'                              => 'About',
		'luxury-cruise-planning'             => 'Luxury Cruise Planning',
		'custom-italy-travel'                => 'Custom Italy',
		'multi-generational-travel-planning' => 'Multi-Generational',
		'journal'                            => 'Journal',
		'client-stories'                     => 'Client Stories',
		'trip-quiz'                          => 'Cabin Quiz',
		'cruise-travel-trends'               => 'Cruise Trends Guide',
		'discovery-call'                     => 'Discovery Call',
	) as $slug => $label ) {
		$p = get_page_by_path( $slug );
		if ( $p && 'publish' === get_post_status( $p ) ) {
			$explore[] = '<li><a href="' . esc_url( home_url( "/$slug/" ) ) . '">' . esc_html( $label ) . '</a></li>';
		}
	}

	// Group Cruises — the CPT archive, only when there's a published sailing to
	// show. Slotted right after Luxury Cruise Planning.
	if ( function_exists( 'oomph_has_published_sailings' ) && oomph_has_published_sailings() ) {
		$gc_link = get_post_type_archive_link( 'oomph_cruise' );
		if ( $gc_link ) {
			$gc_item = '<li><a href="' . esc_url( $gc_link ) . '">Group Cruises</a></li>';
			$pos     = null;
			foreach ( $explore as $i => $html ) {
				if ( false !== strpos( $html, 'luxury-cruise-planning' ) ) {
					$pos = $i + 1;
					break;
				}
			}
			if ( null === $pos ) {
				$explore[] = $gc_item;
			} else {
				array_splice( $explore, $pos, 0, $gc_item );
			}
		}
	}

	$socials = oomph_social_links();

	// Newsletter — posts to Plainsend, our own email app. The endpoint is
	// environment-aware and lives in the core plugin; the markup below is
	// presentation and lives here.
	//
	// This replaced a Fluent Forms embed, which brought 43 KB of stylesheet to
	// every page on the site for one email field (see the 2026-07-28 LCP
	// audit). Plainsend also gives the thing that embed never had: a
	// confirmation step before anybody joins the list, a working unsubscribe,
	// and automatic removal of addresses that bounce.
	$newsletter_endpoint = class_exists( '\OomphTravel\Core\Plainsend' )
		? \OomphTravel\Core\Plainsend::endpoint()
		: '';

	$privacy = get_page_by_path( 'privacy-policy' );
	$privacy_published = $privacy && 'publish' === get_post_status( $privacy );
	?>
	<footer class="oomph-footer" role="contentinfo">
		<div class="oomph-container oomph-footer__grid">

			<div class="oomph-footer__col oomph-footer__brand">
				<img class="oomph-footer__logo" src="<?php echo esc_url( $mark ); ?>" alt="Oomph Travel" width="56" height="56" loading="lazy" decoding="async">
				<p class="oomph-footer__tagline">Life is short — travel with Oomph.</p>
				<p class="oomph-footer__blurb">Premium cruises and custom European journeys, planned by one named advisor — first call to last flight home.</p>
			</div>

			<?php if ( $explore ) : ?>
			<nav class="oomph-footer__col oomph-footer__explore" aria-label="Footer">
				<h2 class="oomph-footer__heading">Explore</h2>
				<ul><?php echo implode( '', $explore ); // phpcs:ignore WordPress.Security.EscapeOutput ?></ul>
			</nav>
			<?php endif; ?>

			<div class="oomph-footer__col oomph-footer__contact">
				<h2 class="oomph-footer__heading">Get in touch</h2>
				<ul>
					<li><a href="mailto:eric@oomphtravel.com">eric@oomphtravel.com</a></li>
					<li><a href="tel:+13607754644">(360) 775-4644</a></li>
					<li>Port Angeles, WA</li>
				</ul>
			</div>

			<div class="oomph-footer__col oomph-footer__newsletter">
				<h2 class="oomph-footer__heading">Trip notes, now and then</h2>
				<p class="oomph-footer__blurb">First-hand notes from the road and the occasional cabin tip. No noise.</p>
				<?php if ( $newsletter_endpoint ) : ?>
					<form class="oomph-signup" method="post" action="<?php echo esc_url( $newsletter_endpoint ); ?>" novalidate>
						<div class="oomph-field">
							<label class="oomph-field__label oomph-signup__label" for="oomph-signup-email">Email address</label>
							<input class="oomph-field__input oomph-signup__input"
							       type="email" id="oomph-signup-email" name="email"
							       autocomplete="email" inputmode="email"
							       placeholder="you@example.com" required>
						</div>

						<?php
						/*
						 * Hidden from people, irresistible to bots. Positioned
						 * off-screen rather than display:none, because some
						 * bots skip fields they can tell are hidden — and this
						 * one is meant to be filled in.
						 */
						?>
						<div class="oomph-signup__trap" aria-hidden="true">
							<label for="oomph-signup-website">Website</label>
							<input type="text" id="oomph-signup-website" name="website" tabindex="-1" autocomplete="off">
						</div>
						<input type="hidden" name="elapsed_ms" value="">

						<button class="oomph-btn oomph-btn--primary oomph-signup__submit" type="submit">Sign me up</button>

						<?php /* Announced to screen readers when it changes, not on load. */ ?>
						<p class="oomph-signup__message" role="status" aria-live="polite"></p>
					</form>

					<?php /* R42 — privacy microcopy under every form. */ ?>
					<p class="oomph-footer__note oomph-signup__privacy">
						One email to confirm, then trip notes now and then. Unsubscribe in one click,
						any time.
					</p>
				<?php else : ?>
					<p class="oomph-footer__note"><em>Newsletter signup connects once the core plugin is active.</em></p>
				<?php endif; ?>
			</div>

		</div>

		<div class="oomph-container oomph-footer__strip">
			<ul class="oomph-footer__credentials">
				<li>CLIA Member</li>
				<li>Nexion Affiliated</li>
				<li>Silversea Ultra-Luxury Specialist</li>
				<li>BritAgent Pro</li>
			</ul>
			<?php if ( array_filter( $socials ) ) : ?>
			<ul class="oomph-footer__social" aria-label="Social links">
				<?php foreach ( $socials as $network => $url ) : ?>
					<?php if ( $url ) : $icon = oomph_social_icon( $network ); ?>
						<?php if ( $icon ) : ?>
						<li><a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( ucfirst( $network ) ); ?>"><?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput ?></a></li>
						<?php endif; ?>
					<?php endif; ?>
				<?php endforeach; ?>
			</ul>
			<?php endif; ?>
		</div>

		<div class="oomph-footer__bottom">
			<div class="oomph-container oomph-footer__bottom-inner">
				<p>&copy; <?php echo esc_html( date_i18n( 'Y' ) ); ?> Oomph Travel LLC</p>
				<?php if ( $privacy_published ) : ?>
				<p><a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>">Privacy Policy</a></p>
				<?php endif; ?>
			</div>
		</div>
	</footer>
	<?php
}
add_action( 'kadence_before_footer', 'oomph_render_footer' );

/**
 * Hide Kadence's default footer markup so it doesn't stack under ours.
 */
add_filter(
	'body_class',
	static function ( $classes ) {
		$classes[] = 'oomph-has-custom-footer';
		return $classes;
	}
);
