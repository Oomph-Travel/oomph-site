<?php
/**
 * The Plainsend signup form, in one place.
 *
 * Three surfaces need the same markup — the sitewide footer, the Cruise Travel
 * Trends landing page, and (next) the cabin quiz — differing only in which
 * Plainsend form receives the signup and what the button says. Duplicating it
 * would mean the honeypot or the timing field going missing from one copy and
 * nobody noticing until signups quietly stopped arriving.
 *
 * Markup lives here rather than in the plugin, and the endpoint lives in the
 * plugin: presentation in the theme, integration in oomph-travel-core.
 *
 * @package OomphChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders a Plainsend signup form.
 *
 * The privacy microcopy under each form is deliberately NOT rendered here.
 * It is page-specific wording sitting outside the form element, and the footer
 * already styles its own (.oomph-signup__privacy).
 *
 * Every form asks the same four things — first name and email required, last
 * name and phone optional — so a contact arrives with as much as they chose to
 * give, wherever they signed up. Only the two are enforced; the other two are
 * marked optional in the label (R39) rather than demanded, which keeps the form
 * short enough to finish. Plainsend stores whatever arrives and never insists
 * on more than the address.
 *
 * @param string $key  Which Plainsend form receives this — 'newsletter',
 *                     'trends-guide'. Resolved per environment, so staging
 *                     posts to the staging copy of the same form.
 * @param array  $args label, button, source, id.
 */
function oomph_signup_form( string $key = 'newsletter', array $args = array() ): void {
	$endpoint = class_exists( '\OomphTravel\Core\Plainsend' )
		? \OomphTravel\Core\Plainsend::endpoint( $key )
		: '';

	// No endpoint means the plugin is missing or deactivated. Rendering a form
	// that posts nowhere would take addresses and drop them, so render nothing.
	if ( ! $endpoint ) {
		return;
	}

	$args = wp_parse_args(
		$args,
		array(
			'label'  => 'Email address',
			'button' => 'Sign me up',
			// Named so the GA4 report says where the signup came from rather
			// than merely that one happened (R11).
			'source' => $key,
			'id'     => 'oomph-signup-' . $key,
		)
	);
	?>
	<?php
	/*
	 * `novalidate` is deliberately absent. The browser enforces `required` on
	 * first name and email and checks the address is shaped like one, before
	 * the submit event fires — so the two required fields are guaranteed present
	 * with accessible native messaging and no custom validation code. The
	 * enhancement script (newsletter.js) only ever runs on an already-valid
	 * form.
	 */
	?>
	<form class="oomph-signup" method="post" action="<?php echo esc_url( $endpoint ); ?>"
	      data-source="<?php echo esc_attr( $args['source'] ); ?>">
		<div class="oomph-field">
			<label class="oomph-field__label oomph-signup__label" for="<?php echo esc_attr( $args['id'] ); ?>-first">
				First name
			</label>
			<input class="oomph-field__input oomph-signup__input"
			       type="text" id="<?php echo esc_attr( $args['id'] ); ?>-first" name="first_name"
			       autocomplete="given-name" required>
		</div>

		<div class="oomph-field">
			<label class="oomph-field__label oomph-signup__label" for="<?php echo esc_attr( $args['id'] ); ?>">
				<?php echo esc_html( $args['label'] ); ?>
			</label>
			<input class="oomph-field__input oomph-signup__input"
			       type="email" id="<?php echo esc_attr( $args['id'] ); ?>" name="email"
			       autocomplete="email" inputmode="email"
			       placeholder="you@example.com" required>
		</div>

		<?php
		/*
		 * Last name and phone are optional and say so in the label (R39), so
		 * nobody is turned away for withholding a number. Plainsend keeps a
		 * phone number exactly as typed and never sends a text to it.
		 */
		?>
		<div class="oomph-field">
			<label class="oomph-field__label oomph-field__label--optional oomph-signup__label"
			       for="<?php echo esc_attr( $args['id'] ); ?>-last">
				Last name
			</label>
			<input class="oomph-field__input oomph-signup__input"
			       type="text" id="<?php echo esc_attr( $args['id'] ); ?>-last" name="last_name"
			       autocomplete="family-name">
		</div>

		<div class="oomph-field">
			<label class="oomph-field__label oomph-field__label--optional oomph-signup__label"
			       for="<?php echo esc_attr( $args['id'] ); ?>-phone">
				Phone number
			</label>
			<input class="oomph-field__input oomph-signup__input"
			       type="tel" id="<?php echo esc_attr( $args['id'] ); ?>-phone" name="phone"
			       autocomplete="tel" inputmode="tel">
		</div>

		<?php
		/*
		 * Hidden from people, irresistible to bots. Positioned off-screen
		 * rather than display:none, because some bots skip fields they can
		 * tell are hidden — and this one is meant to be filled in.
		 */
		?>
		<div class="oomph-signup__trap" aria-hidden="true">
			<label for="<?php echo esc_attr( $args['id'] ); ?>-website">Website</label>
			<input type="text" id="<?php echo esc_attr( $args['id'] ); ?>-website"
			       name="website" tabindex="-1" autocomplete="off">
		</div>
		<input type="hidden" name="elapsed_ms" value="">

		<button class="oomph-btn oomph-btn--primary oomph-signup__submit" type="submit">
			<?php echo esc_html( $args['button'] ); ?>
		</button>

		<?php /* Announced to screen readers when it changes, not on load. */ ?>
		<p class="oomph-signup__message" role="status" aria-live="polite"></p>
	</form>
	<?php
}
