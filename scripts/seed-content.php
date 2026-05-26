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

// Journal permalink — posts live at /journal/{slug}/.
if ( get_option( 'permalink_structure' ) !== '/journal/%postname%/' ) {
	global $wp_rewrite;
	update_option( 'permalink_structure', '/journal/%postname%/' );
	if ( isset( $wp_rewrite ) ) {
		$wp_rewrite->set_permalink_structure( '/journal/%postname%/' );
	}
	$report[] = 'permalink structure -> /journal/%postname%/';
} else {
	$report[] = 'permalink structure already /journal/ (skip)';
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
 * 4c. Cabin Quiz form (email + hidden quiz_result) — email gate for the quiz.
 * ------------------------------------------------------------------------- */
if ( class_exists( 'FluentForm\\App\\Models\\Form' ) ) {
	$FormModel = 'FluentForm\\App\\Models\\Form';
	$Helper    = 'FluentForm\\App\\Helpers\\Helper';
	$cq = $FormModel::where( 'title', 'Cabin Quiz' )->first();
	if ( $cq ) {
		$report[] = "form 'Cabin Quiz' exists (#{$cq->id}) — skip";
	} else {
		$cq_fields = array(
			'fields' => array(
				array(
					'index' => 0, 'element' => 'input_email',
					'attributes' => array( 'type' => 'email', 'name' => 'email', 'value' => '', 'class' => '', 'placeholder' => 'you@example.com' ),
					'settings' => array(
						'container_class' => '', 'label' => 'Email', 'label_placement' => 'hidden', 'admin_field_label' => '', 'help_message' => '',
						'validation_rules' => array(
							'required' => array( 'value' => true, 'message' => 'Email is required', 'global' => false ),
							'email'    => array( 'value' => true, 'message' => 'Please enter a valid email', 'global' => true ),
						),
						'conditional_logics' => array(),
					),
					'editor_options' => array( 'title' => 'Email', 'icon_class' => 'dashicon dashicons dashicons-email', 'template' => 'inputText' ),
					'uniqElKey' => 'el_cq_email_1',
				),
				array(
					'index' => 1, 'element' => 'input_text',
					'attributes' => array( 'type' => 'text', 'name' => 'quiz_result', 'value' => '', 'class' => '', 'placeholder' => '' ),
					'settings' => array(
						'container_class' => 'ff-hidden-field', 'label' => 'Cabin profile', 'label_placement' => 'hidden', 'admin_field_label' => 'Cabin profile', 'help_message' => '',
						'validation_rules' => array( 'required' => array( 'value' => false, 'message' => '', 'global' => true ) ),
						'conditional_logics' => array(),
					),
					'editor_options' => array( 'title' => 'Simple Text', 'icon_class' => 'icon-text-width', 'template' => 'inputText' ),
					'uniqElKey' => 'el_cq_result_1',
				),
			),
			'submitButton' => array(
				'uniqElKey' => 'el_cq_submit_1', 'element' => 'button',
				'attributes' => array( 'type' => 'submit', 'class' => '' ),
				'settings' => array( 'container_class' => '', 'align' => 'left', 'button_style' => 'default', 'button_size' => 'md', 'color' => '#14171A', 'button_ui' => array( 'type' => 'default', 'text' => 'Send me my results', 'img_url' => '' ), 'normal_styles' => array(), 'hover_styles' => array() ),
				'editor_options' => array( 'title' => 'Submit Button' ),
			),
		);
		$cqf = $FormModel::create( array(
			'title' => 'Cabin Quiz', 'form_fields' => json_encode( $cq_fields ),
			'status' => 'published', 'appearance_settings' => '', 'type' => 'form',
			'has_payment' => 0, 'conditions' => '', 'created_by' => 1,
		) );
		$Helper::setFormMeta( $cqf->id, 'formSettings', array(
			'confirmation' => array( 'redirectTo' => 'samePage', 'messageToShow' => '<p>Check your inbox — your results and the Cabin Selection Guide are on the way.</p>', 'samePageFormBehavior' => 'hide_form', 'customPage' => null, 'redirectUrl' => '' ),
			'restrictions' => array( 'requireLogin' => array( 'enabled' => false ) ),
			'layout' => array( 'labelPlacement' => 'top', 'errorMessagePlacement' => 'inline' ),
		) );
		$Helper::setFormMeta( $cqf->id, 'notifications', array(
			'name' => 'Cabin quiz lead', 'sendTo' => array( 'type' => 'email', 'email' => apply_filters( 'oomph_lead_notify_email', get_option( 'admin_email' ) ), 'field' => '', 'routing' => array() ),
			'fromName' => '', 'fromEmail' => '', 'replyTo' => '{inputs.email}', 'bcc' => '',
			'subject' => 'New cabin quiz lead ({inputs.quiz_result})', 'message' => '{all_data}', 'enabled' => true,
		) );
		$report[] = "form 'Cabin Quiz' CREATED (#{$cqf->id})";
	}
}

/* ---------------------------------------------------------------------------
 * 4d. Cruise Travel Trends form (email-only) — emails the guide download link.
 *     Looks up the uploaded "Cruise Travel Trends" PDF for the per-env URL.
 * ------------------------------------------------------------------------- */
if ( class_exists( 'FluentForm\\App\\Models\\Form' ) ) {
	$FormModel = 'FluentForm\\App\\Models\\Form';
	$Helper    = 'FluentForm\\App\\Helpers\\Helper';

	$guide_url = '';
	$att = get_posts( array( 'post_type' => 'attachment', 'title' => 'Cruise Travel Trends', 'numberposts' => 1, 'fields' => 'ids' ) );
	if ( $att ) {
		$guide_url = wp_get_attachment_url( $att[0] );
		// The PDF's auto-slug "cruise-travel-trends" collides with the landing
		// page slug — free it so /cruise-travel-trends/ resolves to the page.
		$att_post = get_post( $att[0] );
		if ( $att_post && 'cruise-travel-trends' === $att_post->post_name ) {
			wp_update_post( array( 'ID' => $att[0], 'post_name' => 'cruise-travel-trends-guide' ) );
			$report[] = 'freed slug: guide attachment → cruise-travel-trends-guide';
		}
	}

	$tg = $FormModel::where( 'title', 'Cruise Travel Trends' )->first();
	if ( $tg ) {
		$report[] = "form 'Cruise Travel Trends' exists (#{$tg->id}) — skip" . ( $guide_url ? '' : ' (WARNING: guide PDF not in media)' );
	} elseif ( '' === $guide_url ) {
		$report[] = "SKIP 'Cruise Travel Trends' form — upload the PDF to media (title 'Cruise Travel Trends') first, then re-run.";
	} else {
		$tg_fields = array(
			'fields' => array(
				array(
					'index' => 0, 'element' => 'input_email',
					'attributes' => array( 'type' => 'email', 'name' => 'email', 'value' => '', 'class' => '', 'placeholder' => 'you@example.com' ),
					'settings' => array(
						'container_class' => '', 'label' => 'Email', 'label_placement' => 'hidden', 'admin_field_label' => '', 'help_message' => '',
						'validation_rules' => array(
							'required' => array( 'value' => true, 'message' => 'Email is required', 'global' => false ),
							'email'    => array( 'value' => true, 'message' => 'Please enter a valid email', 'global' => true ),
						),
						'conditional_logics' => array(),
					),
					'editor_options' => array( 'title' => 'Email', 'icon_class' => 'dashicon dashicons dashicons-email', 'template' => 'inputText' ),
					'uniqElKey' => 'el_tg_email_1',
				),
			),
			'submitButton' => array(
				'uniqElKey' => 'el_tg_submit_1', 'element' => 'button',
				'attributes' => array( 'type' => 'submit', 'class' => '' ),
				'settings' => array( 'container_class' => '', 'align' => 'left', 'button_style' => 'default', 'button_size' => 'md', 'color' => '#14171A', 'button_ui' => array( 'type' => 'default', 'text' => 'Send me the guide', 'img_url' => '' ), 'normal_styles' => array(), 'hover_styles' => array() ),
				'editor_options' => array( 'title' => 'Submit Button' ),
			),
		);
		$tgf = $FormModel::create( array(
			'title' => 'Cruise Travel Trends', 'form_fields' => json_encode( $tg_fields ),
			'status' => 'published', 'appearance_settings' => '', 'type' => 'form',
			'has_payment' => 0, 'conditions' => '', 'created_by' => 1,
		) );
		$Helper::setFormMeta( $tgf->id, 'formSettings', array(
			'confirmation' => array( 'redirectTo' => 'samePage', 'messageToShow' => '<p>Check your inbox — the guide is on its way.</p>', 'samePageFormBehavior' => 'hide_form', 'customPage' => null, 'redirectUrl' => '' ),
			'restrictions' => array( 'requireLogin' => array( 'enabled' => false ) ),
			'layout' => array( 'labelPlacement' => 'top', 'errorMessagePlacement' => 'inline' ),
		) );
		// Single feed: deliver to the subscriber, BCC the advisor.
		$Helper::setFormMeta( $tgf->id, 'notifications', array(
			'name'      => 'Cruise Travel Trends delivery',
			'sendTo'    => array( 'type' => 'email', 'email' => '{inputs.email}', 'field' => '', 'routing' => array() ),
			'fromName'  => 'Eric Hempel · Oomph Travel',
			'fromEmail' => '',
			'replyTo'   => '',
			'bcc'       => apply_filters( 'oomph_lead_notify_email', get_option( 'admin_email' ) ),
			'subject'   => 'Your Cruise Travel Trends guide',
			'message'   => '<p>Thanks for grabbing the guide — here it is:</p>'
				. '<p><a href="' . esc_url( $guide_url ) . '">Download Cruise Travel Trends (2026–2027)</a></p>'
				. '<p>If you\'re weighing a cruise in the next year and want a second set of eyes before you book, a free 30-minute call is the next step.</p>'
				. '<p>— Eric Hempel, Oomph Travel</p>',
			'enabled'   => true,
		) );
		$report[] = "form 'Cruise Travel Trends' CREATED (#{$tgf->id}) → guide: $guide_url";
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

/* ---------------------------------------------------------------------------
 * 6. Service hub pages (templates bind by slug via page-{slug}.php)
 * ------------------------------------------------------------------------- */
$service_pages = array(
	'custom-italy-travel'                 => 'Custom Italy Travel',
	'multi-generational-travel-planning'  => 'Multi-Generational Travel Planning',
	'trip-quiz'                           => 'Trip Quiz', // cabin quiz (page-trip-quiz.php)
	'cruise-travel-trends'                => 'Cruise Travel Trends', // guide landing (page-cruise-travel-trends.php)
	'journal'                             => 'Journal', // post archive (page-journal.php)
);
foreach ( $service_pages as $slug => $title ) {
	if ( get_page_by_path( $slug ) ) {
		$report[] = "page /$slug/ exists — skip";
		continue;
	}
	$pid = wp_insert_post( array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => $title,
		'post_name'    => $slug,
		'post_content' => '',
	) );
	$report[] = is_wp_error( $pid ) ? "ERROR creating /$slug/: " . $pid->get_error_message() : "page /$slug/ CREATED (#{$pid})";
}

/* ---------------------------------------------------------------------------
 * 7. First journal posts (adapted from the Cruise Travel Trends guide).
 *    Create-if-missing by slug — Eric's later edits are preserved.
 * ------------------------------------------------------------------------- */
$cruise_cat = term_exists( 'Cruise', 'category' );
if ( ! $cruise_cat ) {
	$cruise_cat = wp_insert_term( 'Cruise', 'category' );
}
$cruise_cat_id = is_array( $cruise_cat ) ? (int) $cruise_cat['term_id'] : 0;

$slow_cruise = <<<'HTML'
<p>Fifteen years ago, a Mediterranean cruise meant eight ports in ten days. Embark by noon, sail by six, repeat. You got a passport stamp, a postcard, and a tired group of guests who had eaten lunch in three countries.</p>
<p>The lines I work with have spent the last two years quietly redesigning that. For 2026 and 2027, the brochures are full of phrases that did not appear a decade ago: <em>overnight in port. Late-evening departure. Two-night stay.</em></p>
<h2>What is actually changing</h2>
<p>Azamara's 2026 itineraries now include roughly 28 ports with consecutive overnights. Regent's expanded Concierge Collection added 32 itineraries between late 2026 and late 2027 — including the line's first full winter Mediterranean season, partly because winter avoids the crowds. Cunard added 22 overnight stays and 26 late-evening departures to its 2026–2027 schedule. And Explora Journeys built Explora III's first season around what they call longer stays, overnight immersions, and a more unhurried pace of discovery.</p>
<blockquote>The ships that overnight in port are the ones whose passengers come back saying they actually saw the place.</blockquote>
<h2>Why it matters for your trip</h2>
<p>The math is simple. A ship that overnights in Bordeaux gets you a sun-warmed afternoon in Saint-Émilion and dinner back on board, then dinner the next night at a family-run table in town. A ship that arrives at eight a.m. and leaves at five p.m. gets you a coach tour and a souvenir.</p>
<p>For repeat cruisers, this is the single most important shift in how to book. Two ten-night sailings with overnights now feel longer and richer than one fourteen-night sailing without.</p>
<h2>The one question to ask before you book</h2>
<p>Before you commit to any 2026 or 2027 sailing, ask: <strong>how many ports include an overnight or a late departure?</strong> The answer will tell you more about the trip than the ship's name will.</p>
<!-- TODO: Eric — add a first-hand line: a specific overnight you have sailed and what it changed about the trip. -->
HTML;

$first_cruise = <<<'HTML'
<p>The trends I write about can sound like inside language. They are not meant to. If you have never sailed premium or luxury before, here is the short version of how I would plan a first one.</p>
<h2>1. Pick the destination first, not the line</h2>
<p>The right line depends on the trip. Alaska is best served by a small-to-mid-size luxury ship. The Mediterranean by a smaller ship that can call in city ports. Japan by a line with strong port programming. The first question is always where you want to go.</p>
<h2>2. Length matters more than category</h2>
<p>A ten-night sailing in a comfortable balcony cabin will give you a richer experience than a seven-night sailing in a suite. Length is the single most important variable in how the trip feels.</p>
<h2>3. Book mid-ship, low to mid deck</h2>
<p>First-time cruisers consistently underestimate motion. Mid-ship on a lower deck is the most stable cabin location. Move higher and forward once you know how your body handles the sea. (If you are not sure, my <a href="/trip-quiz/">cabin quiz</a> will point you to the right category.)</p>
<h2>4. Choose all-inclusive for your first sailing</h2>
<p>It removes the constant pricing decisions that make a first cruise mentally exhausting. Regent, Silversea's Door-to-Door fares, and Explora Journeys are the cleanest entries.</p>
<h2>5. Do not book the cheapest sailing</h2>
<p>A repositioning sailing with five sea days in a row, or shoulder season with unpredictable weather, is the wrong introduction. Pay the modest premium for an established itinerary in a reliable season.</p>
<h2>6. Use an advisor</h2>
<p>The things that go wrong on a first cruise — wrong cabin, wrong itinerary, wrong line — are not rare, and they are not always cheap to fix. A good advisor catches them before your deposit.</p>
<!-- TODO: Eric — open with a first-hand line: a first-timer you planned for and how it landed. -->
HTML;

$journal_posts = array(
	array(
		'slug'    => 'the-slow-cruise',
		'title'   => 'The Slow Cruise: Why Ships Are Finally Staying Longer in Port',
		'excerpt' => 'Cruise lines are rebuilding 2026–2027 itineraries around overnight stays and late departures — fewer ports, more time in each. Here is why it matters, and the one question to ask before you book.',
		'content' => $slow_cruise,
	),
	array(
		'slug'    => 'first-premium-cruise',
		'title'   => "Your First Premium Cruise: How I'd Plan It",
		'excerpt' => 'Six rules for a first premium or luxury cruise: pick the destination before the line, length over cabin category, book mid-ship, and why all-inclusive is the easiest way in.',
		'content' => $first_cruise,
	),
);

foreach ( $journal_posts as $jp ) {
	$exists = get_posts( array( 'post_type' => 'post', 'name' => $jp['slug'], 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ) );
	if ( $exists ) {
		$report[] = "post /journal/{$jp['slug']}/ exists — skip";
		continue;
	}
	$pid = wp_insert_post( array(
		'post_type'    => 'post',
		'post_status'  => 'publish',
		'post_title'   => $jp['title'],
		'post_name'    => $jp['slug'],
		'post_excerpt' => $jp['excerpt'],
		'post_content' => $jp['content'],
		'post_author'  => 1,
	) );
	if ( ! is_wp_error( $pid ) && $cruise_cat_id ) {
		wp_set_post_categories( $pid, array( $cruise_cat_id ) );
	}
	$report[] = is_wp_error( $pid ) ? "ERROR post {$jp['slug']}: " . $pid->get_error_message() : "post /journal/{$jp['slug']}/ CREATED (#{$pid})";
}

flush_rewrite_rules( false );

echo "\n=== Oomph content seed ===\n";
foreach ( $report as $line ) {
	echo " - $line\n";
}
echo "==========================\n";
