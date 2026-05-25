<?php
/**
 * Idempotent content/config seeder for the Oomph Travel site.
 *
 * Recreates the DB-stored pieces that do NOT travel through the git deploy
 * (content flows prod -> local, never up): the Discovery Call intake form,
 * the /discovery-call/ page, and the Rank Math SEO meta. Safe to run on any
 * environment and safe to re-run (everything is create-if-missing / upsert).
 *
 * Run AFTER the code deploy, from an environment with WP-CLI:
 *   wp eval-file scripts/seed-content.php
 * (Fluent Forms must be installed + active first — see scripts/seed-content.sh,
 *  which handles the plugin install then calls this file.)
 *
 * @package OomphChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	echo "Run via: wp eval-file scripts/seed-content.php\n";
	return;
}

$report = array();

/* ---------------------------------------------------------------------------
 * 1. Site title + tagline
 * ------------------------------------------------------------------------- */
if ( get_option( 'blogname' ) !== 'Oomph Travel' ) {
	update_option( 'blogname', 'Oomph Travel' );
	$report[] = 'blogname -> Oomph Travel';
} else {
	$report[] = 'blogname already correct (skip)';
}
if ( get_option( 'blogdescription' ) !== 'Premium cruises & custom European travel' ) {
	update_option( 'blogdescription', 'Premium cruises & custom European travel' );
	$report[] = 'tagline set';
} else {
	$report[] = 'tagline already correct (skip)';
}

/* ---------------------------------------------------------------------------
 * 2. Rank Math — homepage SEO (front page = posts, so meta lives in options)
 * ------------------------------------------------------------------------- */
$home_title = 'Luxury Cruise & Italy Travel Advisor %sep% %sitename%';
$home_desc  = 'Premium and luxury cruises and custom Italy journeys, planned by one named advisor from first call to last flight home. Book a free discovery call.';

// Case A — front page = latest posts: homepage meta lives in the Rank Math option.
$titles = get_option( 'rank-math-options-titles' );
if ( is_array( $titles ) ) {
	$titles['homepage_title']       = $home_title;
	$titles['homepage_description'] = $home_desc;
	update_option( 'rank-math-options-titles', $titles );
	$report[] = 'Rank Math homepage option set (used when front page = latest posts)';
} else {
	$report[] = 'WARNING: rank-math-options-titles not found — is Rank Math active?';
}

// Case B — front page = a static page: meta lives on that page (e.g. staging/prod).
if ( 'page' === get_option( 'show_on_front' ) ) {
	$fp = (int) get_option( 'page_on_front' );
	if ( $fp ) {
		update_post_meta( $fp, 'rank_math_title', $home_title );
		update_post_meta( $fp, 'rank_math_description', $home_desc );
		$report[] = "home meta set on static front page (#$fp)";
	}
}

/* ---------------------------------------------------------------------------
 * 3. Rank Math — per-page meta (About, Cruise). Only if the page exists.
 * ------------------------------------------------------------------------- */
$page_meta = array(
	'about' => array(
		'rank_math_title'       => 'Eric Hempel, Cruise & Italy Advisor %sep% %sitename%',
		'rank_math_description' => 'Eric Hempel — travel advisor and Silversea Ultra-Luxury Specialist in Port Angeles, WA. I plan premium cruises and custom Italy trips. Let\'s talk.',
	),
	'luxury-cruise-planning' => array(
		'rank_math_title'       => 'Luxury Cruise Planning %sep% %sitename%',
		'rank_math_description' => 'Premium and ultra-luxury cruise planning by a Silversea specialist — cabin selection, onboard credit, pre and post extensions. Book a free discovery call.',
	),
);
foreach ( $page_meta as $slug => $meta ) {
	$page = get_page_by_path( $slug );
	if ( $page ) {
		foreach ( $meta as $k => $v ) {
			update_post_meta( $page->ID, $k, $v );
		}
		$report[] = "meta set on /$slug/ (#{$page->ID})";
	} else {
		$report[] = "page /$slug/ not found — meta skipped (create the page, then re-run)";
	}
}

/* ---------------------------------------------------------------------------
 * 4. Discovery Call intake form (Fluent Forms, single-step)
 * ------------------------------------------------------------------------- */
$form_id = null;
if ( class_exists( 'FluentForm\\App\\Models\\Form' ) ) {
	$FormModel = 'FluentForm\\App\\Models\\Form';
	$Helper    = 'FluentForm\\App\\Helpers\\Helper';
	$existing  = $FormModel::where( 'title', 'Discovery Call Intake' )->first();

	if ( $existing ) {
		$form_id  = $existing->id;
		$report[] = "form 'Discovery Call Intake' exists (#{$form_id}) — skip";
	} else {
		$req = function ( $msg ) {
			return array( 'required' => array( 'value' => true, 'message' => $msg, 'global' => false ) );
		};
		$opt = array( 'required' => array( 'value' => false, 'message' => 'This field is required', 'global' => true ) );

		$fields = array();
		$fields[] = array(
			'index' => 0, 'element' => 'input_name',
			'attributes' => array( 'name' => 'names', 'data-type' => 'name-element' ),
			'settings' => array( 'container_class' => '', 'admin_field_label' => 'Name', 'conditional_logics' => array() ),
			'fields' => array(
				'first_name' => array( 'element' => 'input_text', 'attributes' => array( 'type' => 'text', 'name' => 'first_name', 'value' => '', 'id' => '', 'class' => '', 'placeholder' => 'First name' ), 'settings' => array( 'container_class' => '', 'label' => 'First Name', 'help_message' => '', 'visible' => true, 'validation_rules' => $req( 'First name is required' ), 'conditional_logics' => array() ), 'editor_options' => array( 'template' => 'inputText' ) ),
				'middle_name' => array( 'element' => 'input_text', 'attributes' => array( 'type' => 'text', 'name' => 'middle_name', 'value' => '', 'id' => '', 'class' => '', 'placeholder' => '' ), 'settings' => array( 'container_class' => '', 'label' => 'Middle Name', 'help_message' => '', 'visible' => false, 'validation_rules' => $opt, 'conditional_logics' => array() ), 'editor_options' => array( 'template' => 'inputText' ) ),
				'last_name' => array( 'element' => 'input_text', 'attributes' => array( 'type' => 'text', 'name' => 'last_name', 'value' => '', 'id' => '', 'class' => '', 'placeholder' => 'Last name' ), 'settings' => array( 'container_class' => '', 'label' => 'Last Name', 'help_message' => '', 'visible' => true, 'validation_rules' => $req( 'Last name is required' ), 'conditional_logics' => array() ), 'editor_options' => array( 'template' => 'inputText' ) ),
			),
			'editor_options' => array( 'title' => 'Name', 'element' => 'name-fields', 'icon_class' => 'icon-user', 'template' => 'nameFields' ),
			'uniqElKey' => 'el_name_1',
		);
		$fields[] = array(
			'index' => 1, 'element' => 'input_email',
			'attributes' => array( 'type' => 'email', 'name' => 'email', 'value' => '', 'class' => '', 'placeholder' => 'you@example.com' ),
			'settings' => array( 'container_class' => '', 'label' => 'Email', 'admin_field_label' => '', 'label_placement' => '', 'help_message' => '', 'validation_rules' => array( 'required' => array( 'value' => true, 'message' => 'Email is required', 'global' => false ), 'email' => array( 'value' => true, 'message' => 'Please enter a valid email', 'global' => true ) ), 'conditional_logics' => array() ),
			'editor_options' => array( 'title' => 'Email', 'icon_class' => 'dashicon dashicons dashicons-email', 'template' => 'inputText' ),
			'uniqElKey' => 'el_email_1',
		);
		$fields[] = array(
			'index' => 2, 'element' => 'input_text',
			'attributes' => array( 'type' => 'text', 'name' => 'phone', 'value' => '', 'id' => '', 'class' => '', 'placeholder' => '' ),
			'settings' => array( 'container_class' => '', 'label' => 'Phone (optional)', 'admin_field_label' => '', 'label_placement' => '', 'help_message' => 'Only if you prefer a call back.', 'validation_rules' => $opt, 'conditional_logics' => array() ),
			'editor_options' => array( 'title' => 'Simple Text', 'icon_class' => 'icon-text-width', 'template' => 'inputText' ),
			'uniqElKey' => 'el_phone_1',
		);
		$fields[] = array(
			'index' => 3, 'element' => 'select',
			'attributes' => array( 'name' => 'trip_type', 'value' => '', 'id' => '', 'class' => '', 'placeholder' => '— Select —' ),
			'settings' => array( 'container_class' => '', 'label' => 'What kind of trip?', 'admin_field_label' => '', 'help_message' => '', 'validation_rules' => $opt, 'advanced_options' => array( array( 'label' => 'Luxury cruise', 'value' => 'Luxury cruise', 'calc_value' => '' ), array( 'label' => 'Custom Italy', 'value' => 'Custom Italy', 'calc_value' => '' ), array( 'label' => 'Multi-generational', 'value' => 'Multi-generational', 'calc_value' => '' ), array( 'label' => 'Not sure yet', 'value' => 'Not sure yet', 'calc_value' => '' ) ), 'conditional_logics' => array() ),
			'editor_options' => array( 'title' => 'Dropdown', 'icon_class' => 'icon-caret-square-o-down', 'template' => 'select' ),
			'uniqElKey' => 'el_trip_1',
		);
		$fields[] = array(
			'index' => 4, 'element' => 'input_text',
			'attributes' => array( 'type' => 'text', 'name' => 'timeframe', 'value' => '', 'id' => '', 'class' => '', 'placeholder' => 'e.g. Spring 2027' ),
			'settings' => array( 'container_class' => '', 'label' => 'When are you hoping to travel? (optional)', 'admin_field_label' => '', 'help_message' => '', 'validation_rules' => $opt, 'conditional_logics' => array() ),
			'editor_options' => array( 'title' => 'Simple Text', 'icon_class' => 'icon-text-width', 'template' => 'inputText' ),
			'uniqElKey' => 'el_time_1',
		);
		$fields[] = array(
			'index' => 5, 'element' => 'textarea',
			'attributes' => array( 'name' => 'message', 'value' => '', 'id' => '', 'class' => '', 'placeholder' => '', 'rows' => 4, 'cols' => 2, 'maxlength' => '' ),
			'settings' => array( 'container_class' => '', 'label' => 'Tell me about the trip (optional)', 'admin_field_label' => '', 'help_message' => '', 'validation_rules' => $opt, 'conditional_logics' => array() ),
			'editor_options' => array( 'title' => 'Text Area', 'icon_class' => 'ff-edit-textarea', 'template' => 'inputTextarea' ),
			'uniqElKey' => 'el_msg_1',
		);

		$form_fields = array(
			'fields'       => $fields,
			'submitButton' => array(
				'uniqElKey' => 'el_submit_1', 'element' => 'button',
				'attributes' => array( 'type' => 'submit', 'class' => '' ),
				'settings' => array( 'container_class' => '', 'align' => 'left', 'button_style' => 'default', 'button_size' => 'md', 'color' => '#14171A', 'button_ui' => array( 'type' => 'default', 'text' => 'Request My Discovery Call →', 'img_url' => '' ), 'normal_styles' => array(), 'hover_styles' => array() ),
				'editor_options' => array( 'title' => 'Submit Button' ),
			),
		);

		$form = $FormModel::create( array(
			'title' => 'Discovery Call Intake', 'form_fields' => json_encode( $form_fields ),
			'status' => 'published', 'appearance_settings' => '', 'type' => 'form',
			'has_payment' => 0, 'conditions' => '', 'created_by' => 1,
		) );
		$form_id = $form->id;

		$notify_to = apply_filters( 'oomph_lead_notify_email', get_option( 'admin_email' ) );
		$Helper::setFormMeta( $form_id, 'formSettings', array(
			'confirmation' => array( 'redirectTo' => 'samePage', 'messageToShow' => "<p>Thank you — I'll be in touch within one business day to set up your call.</p>", 'customPage' => null, 'samePageFormBehavior' => 'hide_form', 'redirectUrl' => '' ),
			'restrictions' => array( 'limitNumberOfEntries' => array( 'enabled' => false ), 'scheduleForm' => array( 'enabled' => false ), 'requireLogin' => array( 'enabled' => false ) ),
			'layout' => array( 'labelPlacement' => 'top', 'helpMessagePlacement' => 'with_label', 'errorMessagePlacement' => 'inline', 'asteriskPlacement' => 'asterisk-right' ),
		) );
		$Helper::setFormMeta( $form_id, 'notifications', array(
			'name' => 'Discovery Call request', 'sendTo' => array( 'type' => 'email', 'email' => $notify_to, 'field' => '', 'routing' => array() ),
			'fromName' => '', 'fromEmail' => '', 'replyTo' => '{inputs.email}', 'bcc' => '',
			'subject' => 'New Discovery Call request from {inputs.names.first_name}', 'message' => '{all_data}', 'enabled' => true,
		) );
		$report[] = "form 'Discovery Call Intake' CREATED (#{$form_id})";
	}
} else {
	$report[] = 'WARNING: Fluent Forms not active — form not created. Activate it, then re-run.';
}

/* ---------------------------------------------------------------------------
 * 4b. Newsletter Signup form (email-only) — embedded in the footer.
 * ------------------------------------------------------------------------- */
if ( class_exists( 'FluentForm\\App\\Models\\Form' ) ) {
	$FormModel = 'FluentForm\\App\\Models\\Form';
	$Helper    = 'FluentForm\\App\\Helpers\\Helper';
	$nl = $FormModel::where( 'title', 'Newsletter Signup' )->first();
	if ( $nl ) {
		$report[] = "form 'Newsletter Signup' exists (#{$nl->id}) — skip";
	} else {
		$nl_fields = array(
			'fields' => array(
				array(
					'index' => 0, 'element' => 'input_email',
					'attributes' => array( 'type' => 'email', 'name' => 'email', 'value' => '', 'class' => '', 'placeholder' => 'Your email' ),
					'settings' => array(
						'container_class' => '', 'label' => 'Email', 'label_placement' => 'hidden', 'admin_field_label' => '', 'help_message' => '',
						'validation_rules' => array(
							'required' => array( 'value' => true, 'message' => 'Email is required', 'global' => false ),
							'email'    => array( 'value' => true, 'message' => 'Please enter a valid email', 'global' => true ),
						),
						'conditional_logics' => array(),
					),
					'editor_options' => array( 'title' => 'Email', 'icon_class' => 'dashicon dashicons dashicons-email', 'template' => 'inputText' ),
					'uniqElKey' => 'el_nl_email_1',
				),
			),
			'submitButton' => array(
				'uniqElKey' => 'el_nl_submit_1', 'element' => 'button',
				'attributes' => array( 'type' => 'submit', 'class' => '' ),
				'settings' => array( 'container_class' => '', 'align' => 'left', 'button_style' => 'default', 'button_size' => 'md', 'color' => '#14171A', 'button_ui' => array( 'type' => 'default', 'text' => 'Subscribe', 'img_url' => '' ), 'normal_styles' => array(), 'hover_styles' => array() ),
				'editor_options' => array( 'title' => 'Submit Button' ),
			),
		);
		$nlf = $FormModel::create( array(
			'title' => 'Newsletter Signup', 'form_fields' => json_encode( $nl_fields ),
			'status' => 'published', 'appearance_settings' => '', 'type' => 'form',
			'has_payment' => 0, 'conditions' => '', 'created_by' => 1,
		) );
		$Helper::setFormMeta( $nlf->id, 'formSettings', array(
			'confirmation' => array( 'redirectTo' => 'samePage', 'messageToShow' => '<p>Thank you — you are on the list.</p>', 'samePageFormBehavior' => 'hide_form', 'customPage' => null, 'redirectUrl' => '' ),
			'restrictions' => array( 'requireLogin' => array( 'enabled' => false ) ),
			'layout' => array( 'labelPlacement' => 'top', 'errorMessagePlacement' => 'inline' ),
		) );
		$Helper::setFormMeta( $nlf->id, 'notifications', array(
			'name' => 'Newsletter signup', 'sendTo' => array( 'type' => 'email', 'email' => apply_filters( 'oomph_lead_notify_email', get_option( 'admin_email' ) ), 'field' => '', 'routing' => array() ),
			'fromName' => '', 'fromEmail' => '', 'replyTo' => '{inputs.email}', 'bcc' => '',
			'subject' => 'New newsletter signup', 'message' => '{all_data}', 'enabled' => true,
		) );
		$report[] = "form 'Newsletter Signup' CREATED (#{$nlf->id})";
	}
}

/* ---------------------------------------------------------------------------
 * 5. /discovery-call/ page (template binds by slug via page-discovery-call.php)
 * ------------------------------------------------------------------------- */
$dc = get_page_by_path( 'discovery-call' );
if ( $dc ) {
	$report[] = "page /discovery-call/ exists (#{$dc->ID}) — skip";
} else {
	$dc_id = wp_insert_post( array(
		'post_type'   => 'page',
		'post_status' => 'publish',
		'post_title'  => 'Discovery Call',
		'post_name'   => 'discovery-call',
		'post_content' => '',
	) );
	$report[] = is_wp_error( $dc_id ) ? 'ERROR creating /discovery-call/: ' . $dc_id->get_error_message() : "page /discovery-call/ CREATED (#{$dc_id})";
}

flush_rewrite_rules( false );

echo "\n=== Oomph content seed ===\n";
foreach ( $report as $line ) {
	echo " - $line\n";
}
echo "==========================\n";
