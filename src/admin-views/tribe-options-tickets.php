<?php
/**
 * Event Tickets settings tab.
 *
 * @version 5.23.0
 * @since 4.10.2 Update tooltip text for Confirmation email sender address and allow it to be saved as empty.
 * @since 4.10.9 Use function for text.
 * @since 5.23.0 Updated to use modern settings UI components.
 *
 * @since 4.7
 */

/**
 * Filters the list of post types to ignore when determining which post types should support tickets.
 *
 * @since 5.1.6
 *
 * @param string[] $post_types_to_ignore Array of post type names that should not support tickets.
 */
$post_types_to_ignore = apply_filters(
	'tribe_tickets_settings_post_type_ignore_list',
	[
		'attachment',
	]
);

$all_post_type_objects = get_post_types( [ 'public' => true ], 'objects' );
$all_post_types        = [];

foreach ( $all_post_type_objects as $post_type => $post_type_object ) {
	$should_ignore = false;

	foreach ( $post_types_to_ignore as $ignore ) {
		if ( preg_match( '/' . preg_quote( $ignore, '/' ) . '/', $post_type ) ) {
			$should_ignore = true;
			break;
		}
	}

	if ( $should_ignore ) {
		continue;
	}

	$all_post_types[ $post_type ] = $post_type_object->labels->singular_name;
}

/**
 * Filters the list of post types that can have tickets.
 *
 * @since 5.1.6
 *
 * @param array<string, string> $all_post_types Associative array of post types, with post type names as
 *                                              keys and singular display names as values.
 */
$all_post_types = apply_filters( 'tribe_tickets_settings_post_types', $all_post_types );

$options = get_option( Tribe__Main::OPTIONNAME, [] );

/**
 * Filters the list of ticketing solutions that support login requirements.
 *
 * This controls options for requiring users to log in before they can RSVP or purchase tickets.
 *
 * @since 5.1.6
 *
 * @param array<string, string> $ticket_systems Associative array of ticket systems, with system
 *                                              identifiers as keys (like 'event-tickets_rsvp') and
 *                                              translated labels as values.
 */
$ticket_addons = apply_filters(
	'tribe_tickets_settings_systems_supporting_login_requirements',
	[
		'event-tickets_rsvp' => sprintf(
		// Translators: %s: singular RSVP label.
			_x( 'Require users to log in before they %s', 'login requirement setting', 'event-tickets' ),
			tribe_get_rsvp_label_singular( 'require_login_to_rsvp_setting' )
		),
		'event-tickets_all'  => sprintf(
		// Translators: %s: plural tickets label in lowercase.
			_x( 'Require users to log in before they purchase %s', 'login requirement setting', 'event-tickets' ),
			tribe_get_ticket_label_plural_lowercase( 'require_login_to_purchase_setting' )
		),
	]
);

$info_box       = [
	'tec-settings-general-title' => [
		'type' => 'html',
		'html' => '<div class="tec-settings-form__header-block tec-settings-form__header-block--horizontal">'
					. '<h3 id="tec-settings-addons-title" class="tec-settings-form__section-header">'
					. _x( 'General', 'Integrations section header', 'event-tickets' )
					. '</h3>'
					. '</div>',
	],
];
$tickets_fields = [
	'tec-settings-general-ticket-fields-div-start' => [
		'type' => 'html',
		'html' => '<div class="tec-settings-form__content-section">',
	],
	// Ticket Settings Header.
	'tickets-title'                                => [
		'type' => 'html',
		'html' => '<h3 id="tec-tickets-settings-tickets" class="tec-settings-form__section-header tec-settings-form__section-header--sub">' .
					sprintf(
					// Translators: %s: singular ticket label.
						_x( '%s Settings', 'tickets fields settings title', 'event-tickets' ),
						tribe_get_ticket_label_singular( 'tickets_fields_settings_title' )
					) . '</h3>',
	],
	// Post types that can have tickets.
	'ticket-enabled-post-types'                    => [
		'type'            => 'checkbox_list',
		'label'           => esc_html(
			sprintf(
			// Translators: %s: plural tickets label in lowercase.
				_x(
					'Post types that can have %s',
					'tickets fields settings enabled post types',
					'event-tickets'
				),
				tribe_get_ticket_label_plural_lowercase( 'tickets_fields_settings_enabled_post_types' )
			)
		),
		// Only set the default to tribe_events if the ticket-enabled-post-types index has never been saved.
		'default'         => array_key_exists( 'ticket-enabled-post-types', (array) $options ) ? false : 'tribe_events',
		'options'         => $all_post_types,
		'can_be_empty'    => true,
		'validation_type' => 'options_multi',
	],
	'event_tickets_uninstall'                      => [
		'type'            => 'checkbox_bool',
		'label'           => esc_html__( 'Remove all Event Tickets data upon uninstall?', 'event-tickets' ),
		'tooltip'         => esc_html__( 'If checked, all Event Tickets data will be removed from the database when the plugin is uninstalled.', 'event-tickets' ),
		'default'         => false,
		'validation_type' => 'boolean',
		'parent_option'   => false,
	],
	'tec-settings-general-ticket-fields-div-end'   => [
		'type' => 'html',
		'html' => '</div">',
	],
];

$tec_fields  = [];
$misc_fields = [];

/**
 * If The Events Calendar is active let's add an option to control the position
 * of the ticket forms in the events view.
 */
if ( class_exists( 'Tribe__Events__Main' ) ) {
	$ticket_form_location_options = [
		'tribe_events_single_event_after_the_meta'     => __( 'Below the event details [default]', 'event-tickets' ),
		'tribe_events_single_event_before_the_meta'    => __( 'Above the event details', 'event-tickets' ),
		'tribe_events_single_event_after_the_content'  => __( 'Below the event description', 'event-tickets' ),
		'tribe_events_single_event_before_the_content' => __( 'Above the event description', 'event-tickets' ),
	];

	$tec_fields['tec-settings-general-tec-fields-div-start'] = [
		'type' => 'html',
		'html' => '<div class="tec-settings-form__content-section">',
	];

	// TEC Integration header.
	$tec_fields['tec-header'] = [
		'type' => 'html',
		'html' => '<h3 id="tec-tickets-settings-tec-integration" class="tec-settings-form__section-header tec-settings-form__section-header--sub">' .
					esc_html__( 'The Events Calendar Integration', 'event-tickets' ) . '</h3>',
	];

	// TEC integration fields.
	$tec_fields['ticket-rsvp-form-location'] = [
		'type'            => 'dropdown',
		'label'           => esc_html(
			sprintf(
			// Translators: %s: singular RSVP label.
				_x( 'Location of %s form', 'form location setting', 'event-tickets' ),
				tribe_get_rsvp_label_singular( 'form_location_setting' )
			)
		),
		'tooltip'         => esc_html__( 'This setting only impacts events made with the classic editor.', 'event-tickets' ),
		'options'         => $ticket_form_location_options,
		'validation_type' => 'options',
		'parent_option'   => Tribe__Events__Main::OPTIONNAME,
		'default'         => reset( $ticket_form_location_options ),
	];

	$tec_fields['ticket-commerce-form-location'] = [
		'type'            => 'dropdown',
		'label'           => esc_html(
			sprintf(
			// Translators: %s: plural tickets label.
				_x( 'Location of %s form', 'form location setting', 'event-tickets' ),
				tribe_get_ticket_label_plural( 'form_location_setting' )
			)
		),
		'tooltip'         => esc_html__( 'This setting only impacts events made with the classic editor.', 'event-tickets' ),
		'options'         => $ticket_form_location_options,
		'validation_type' => 'options',
		'parent_option'   => Tribe__Events__Main::OPTIONNAME,
		'default'         => reset( $ticket_form_location_options ),
	];

	$tec_fields['ticket-display-tickets-left-threshold'] = [
		'type'            => 'text',
		'label'           => esc_html(
			sprintf(
			// Translators: %s: plural tickets label in lowercase.
				_x( 'Display # %s left threshold', 'tickets remaining threshold label', 'event-tickets' ),
				tribe_get_ticket_label_plural_lowercase( 'remaining_threshold_setting_label' )
			)
		),
		'tooltip'         => esc_html(
			sprintf(
			// Translators: %1$s: plural tickets label in lowercase.
				_x( 'If this number is less than the number of %1$s left for sale on your event, this will prevent the "# of %1$s left" text from showing on your website. You can leave this blank if you would like to always show the text.', 'tickets remaining threshold tooltip', 'event-tickets' ),
				tribe_get_ticket_label_plural_lowercase( 'remaining_threshold_setting_tooltip' )
			)
		),
		'validation_type' => 'int',
		'size'            => 'small',
		'can_be_empty'    => true,
		'parent_option'   => Tribe__Events__Main::OPTIONNAME,
	];

	$tec_fields['tec-settings-general-tec-fields-div-end'] = [
		'type' => 'html',
		'html' => '</div>',
	];
} else {
	$sample_date = strtotime( 'January 15 ' . gmdate( 'Y' ) );

	$misc_fields['tec-settings-general-misc-fields-div-start'] = [
		'type' => 'html',
		'html' => '<div class="tec-settings-form__content-section">',
	];

	// Miscellaneous header.
	$misc_fields['misc-header'] = [
		'type' => 'html',
		'html' => '<h3 id="tec-tickets-settings-misc" class="tec-settings-form__section-header tec-settings-form__section-header--sub">' .
					esc_html__( 'Miscellaneous Settings', 'event-tickets' ) . '</h3>',
	];

	// Miscellaneous fields.
	$misc_fields['debugEvents'] = [
		'type'            => 'checkbox_bool',
		'label'           => esc_html__( 'Debug mode', 'event-tickets' ),
		'tooltip'         => sprintf(
		// Translators: %s Debug bar plugin link.
			esc_html__(
				'Enable this option to log debug information. By default this will log to your server PHP error log. If you\'d like to see the log messages in your browser, then we recommend that you install the %s and look for the "Tribe" tab in the debug output.',
				'event-tickets'
			),
			'<a href="https://wordpress.org/extend/plugins/debug-bar/" target="_blank">' . esc_html__( 'Debug Bar Plugin', 'event-tickets' ) . '</a>'
		),
		'default'         => false,
		'validation_type' => 'boolean',
	];

	$misc_fields['datepickerFormat'] = [
		'type'            => 'dropdown',
		'label'           => esc_html__( 'Compact Date Format', 'event-tickets' ),
		'tooltip'         => esc_html__( 'Select the date format used for elements with minimal space, such as in datepickers.', 'event-tickets' ),
		'default'         => 1,
		'options'         => [
			'0'  => gmdate( 'Y-m-d', $sample_date ),
			'1'  => gmdate( 'n/j/Y', $sample_date ),
			'2'  => gmdate( 'm/d/Y', $sample_date ),
			'3'  => gmdate( 'j/n/Y', $sample_date ),
			'4'  => gmdate( 'd/m/Y', $sample_date ),
			'5'  => gmdate( 'n-j-Y', $sample_date ),
			'6'  => gmdate( 'm-d-Y', $sample_date ),
			'7'  => gmdate( 'j-n-Y', $sample_date ),
			'8'  => gmdate( 'd-m-Y', $sample_date ),
			'9'  => gmdate( 'Y.m.d', $sample_date ),
			'10' => gmdate( 'm.d.Y', $sample_date ),
			'11' => gmdate( 'd.m.Y', $sample_date ),
		],
		'validation_type' => 'options',
	];

	$misc_fields['tec-settings-general-misc-fields-div-end'] = [
		'type' => 'html',
		'html' => '</div>',
	];
}

// Authentication requirements fields.
$auth_fields = [
	'tec-settings-general-auth-fields-div-start' => [
		'type' => 'html',
		'html' => '<div class="tec-settings-form__content-section">',
	],
	// Authentication Requirements header.
	'auth-header'                                => [
		'type' => 'html',
		'html' => '<h3 id="tec-tickets-settings-authentication" class="tec-settings-form__section-header tec-settings-form__section-header--sub">' .
					esc_html__( 'Login Requirements', 'event-tickets' ) . '</h3>',
	],
	// Authentication Requirements description.
	'auth-description'                           => [
		'type' => 'html',
		'html' => '<p class="tec-settings-form__description-text">' .
					sprintf(
					// Translators: %1$s: singular RSVP label, %2$s: plural tickets label in lowercase, %3$s: opening link tag to WP general settings, %4$s: closing link tag.
						_x(
							'You can require that users log into your site before they are able to %1$s (or buy %2$s). Please review your WordPress Membership option (via the %3$sGeneral Settings admin screen%4$s) before adjusting this setting.',
							'ticket authentication requirements',
							'event-tickets'
						),
						tribe_get_rsvp_label_singular( 'authentication_requirements_advice' ),
						tribe_get_ticket_label_plural_lowercase( 'authentication_requirements_advice' ),
						'<a href="' . esc_url( get_admin_url( null, 'options-general.php' ) ) . '" target="_blank">',
						'</a>'
					) .
					'</p>',
	],
	// Authentication Requirements field.
	'ticket-authentication-requirements'         => [
		'type'            => 'checkbox_list',
		'options'         => $ticket_addons,
		'validation_type' => 'options_multi',
		'can_be_empty'    => true,
	],
	'tec-settings-general-auth-fields-div-end'   => [
		'type' => 'html',
		'html' => '</div>',
	],
];

$commerce_fields = [];

/**
 * Hide Legacy PayPal settings for new installations.
 */
if ( tec_tribe_commerce_is_available() ) {
	$commerce_fields = include 'tribe-commerce-settings.php';
}

/**
 * Filters the commerce fields to add or modify settings for payment gateways.
 *
 * @since 5.1.6
 *
 * @param array<string, array{
 *     type: string,
 *     label?: string,
 *     tooltip?: string,
 *     default?: mixed,
 *     validation_type?: string,
 *     options?: array<string, string>,
 *     can_be_empty?: bool,
 *     html?: string,
 *     parent_option?: string|bool,
 *     fieldset_attributes?: array<string, mixed>,
 *     size?: string,
 *     class?: string|array<string>
 * }> $commerce_fields Array of field definitions for commerce/payment settings.
 */
$commerce_fields = (array) apply_filters( 'tec_tickets_commerce_settings', $commerce_fields );

$tickets_fields = array_merge(
	$info_box,
	$tickets_fields,
	$tec_fields,
	$auth_fields,
	$commerce_fields,
	$misc_fields,
);

/**
 * Filters the fields to be registered in the Settings > Tickets tab.
 *
 * A field definition is one suitable to be consumed by the `Tribe__Settings_Tab` class.
 *
 * @since 5.1.6
 *
 * @see Tribe__Settings_Tab
 * @see Tribe__Field
 *
 * @param array<string, array{
 *     type: string,
 *     label?: string,
 *     tooltip?: string,
 *     default?: mixed,
 *     validation_type?: string,
 *     options?: array<string, string>,
 *     can_be_empty?: bool,
 *     html?: string,
 *     parent_option?: string|bool,
 *     fieldset_attributes?: array<string, mixed>,
 *     size?: string,
 *     class?: string|array<string>
 * }> $tickets_fields An associative array of field definitions to register.
 */
$tickets_fields = apply_filters( 'tribe_tickets_settings_tab_fields', $tickets_fields );

$tickets_tab = [
	'priority' => 20,
	'fields'   => $tickets_fields,
];
