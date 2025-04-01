<?php
/**
 * Event Tickets settings tab
 *
 * @since 4.7
 * @since 4.10.2 Update tooltip text for Confirmation email sender address and allow it to be saved as empty
 * @since 4.10.9 Use function for text.
 * @since TBD Updated to use modern settings UI components
 *
 * @version TBD
 */

use TEC\Common\Admin\Entities\Div;
use TEC\Common\Admin\Entities\Heading;
use TEC\Common\Admin\Entities\Paragraph;
use TEC\Common\Admin\Entities\Plain_Text;
use Tribe\Utils\Element_Classes;

$post_types_to_ignore = apply_filters( 'tribe_tickets_settings_post_type_ignore_list', [
	'attachment',
] );

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

$all_post_types = apply_filters( 'tribe_tickets_settings_post_types', $all_post_types );

$options = get_option( Tribe__Main::OPTIONNAME, [] );

/**
 * List of ticketing solutions that support login requirements (ie, disabling or
 * enabling the ticket form according to whether a user is logged in or not).
 *
 * @param array $ticket_systems
 */
$ticket_addons = apply_filters( 'tribe_tickets_settings_systems_supporting_login_requirements', [
	'event-tickets_rsvp' => sprintf( _x( 'Require users to log in before they %s', 'login requirement setting', 'event-tickets' ), tribe_get_rsvp_label_singular( 'require_login_to_rsvp_setting' ) ),
	'event-tickets_all'  => sprintf( _x( 'Require users to log in before they purchase %s', 'login requirement setting', 'event-tickets' ), tribe_get_ticket_label_plural_lowercase( 'require_login_to_purchase_setting' ) ),
]
);

// Create the tickets header section
$tickets_header = new Div( new Element_Classes( [ 'tec-settings-form__header-block' ] ) );
$tickets_header->add_child(
	new Heading(
		sprintf( _x( '%s Settings', 'tickets fields settings title', 'event-tickets' ), tribe_get_ticket_label_singular( 'tickets_fields_settings_title' ) ),
		3,
		new Element_Classes( 'tec-settings-form__section-header' )
	)
);

// Create post types ticket settings section
$post_types_section = tribe( 'settings' )->wrap_section_content(
	'ticket-enabled-post-types-section',
	[
		'ticket-enabled-post-types' => [
			'type'            => 'checkbox_list',
			'label'           => esc_html(
				sprintf(
					// Translators: %s: dynamic "tickets" text.
					_x(
						'Post types that can have %s',
						'tickets fields settings enabled post types',
						'event-tickets'
					),
					tribe_get_ticket_label_plural_lowercase( 'tickets_fields_settings_enabled_post_types' )
				)
			),
			// only set the default to tribe_events if the ticket-enabled-post-types index has never been saved
			'default'         => array_key_exists( 'ticket-enabled-post-types', $options ) ? false : 'tribe_events',
			'options'         => $all_post_types,
			'can_be_empty'    => true,
			'validation_type' => 'options_multi',
		],
		'event_tickets_uninstall'   => [
			'type'            => 'checkbox_bool',
			'label'           => esc_html__( 'Remove all Event Tickets data upon uninstall?', 'event-tickets' ),
			'tooltip'         => esc_html__( 'If checked, all Event Tickets data will be removed from the database when the plugin is uninstalled.', 'event-tickets' ),
			'default'         => false,
			'validation_type' => 'boolean',
			'parent_option'   => false,
		],
	]
);

$tickets_fields = [
	'tickets-title' => $tickets_header,
];

$tickets_fields = array_merge($tickets_fields, $post_types_section);

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

	// Create TEC integration header
	$tec_header = new Div( new Element_Classes( [ 'tec-settings-form__header-block' ] ) );
	$tec_header->add_child(
		new Heading(
			__( 'The Events Calendar Integration', 'event-tickets' ),
			3,
			new Element_Classes( 'tec-settings-form__section-header' )
		)
	);

	// Create TEC integration section without including the header in the wrapped content
	$tec_fields_section = tribe( 'settings' )->wrap_section_content(
		'tec-integration-section',
		[
			'ticket-rsvp-form-location' => [
				'type'            => 'dropdown',
				'label'           => esc_html( sprintf( _x( 'Location of %s form', 'form location setting', 'event-tickets' ), tribe_get_rsvp_label_singular( 'form_location_setting' ) ) ),
				'tooltip'         => esc_html__( 'This setting only impacts events made with the classic editor.', 'event-tickets' ),
				'options'         => $ticket_form_location_options,
				'validation_type' => 'options',
				'parent_option'   => Tribe__Events__Main::OPTIONNAME,
				'default'         => reset( $ticket_form_location_options ),
			],
			'ticket-commerce-form-location' => [
				'type'            => 'dropdown',
				'label'           => esc_html( sprintf( _x( 'Location of %s form', 'form location setting', 'event-tickets' ), tribe_get_ticket_label_plural( 'form_location_setting' ) ) ),
				'tooltip'         => esc_html__( 'This setting only impacts events made with the classic editor.', 'event-tickets' ),
				'options'         => $ticket_form_location_options,
				'validation_type' => 'options',
				'parent_option'   => Tribe__Events__Main::OPTIONNAME,
				'default'         => reset( $ticket_form_location_options ),
			],
			'ticket-display-tickets-left-threshold' => [
				'type'            => 'text',
				'label'           => esc_html( sprintf( _x( 'Display # %s left threshold', 'tickets remaining threshold label', 'event-tickets' ), tribe_get_ticket_label_plural_lowercase( 'remaining_threshold_setting_label' ) ) ),
				'tooltip'         => esc_html( sprintf( _x( 'If this number is less than the number of %1$s left for sale on your event, this will prevent the "# of %1$s left" text from showing on your website. You can leave this blank if you would like to always show the text.', 'tickets remaining threshold tooltip', 'event-tickets' ), tribe_get_ticket_label_plural_lowercase( 'remaining_threshold_setting_tooltip' ) ) ),
				'validation_type' => 'int',
				'size'            => 'small',
				'can_be_empty'    => true,
				'parent_option'   => Tribe__Events__Main::OPTIONNAME,
			],
		]
	);

	$tec_fields = [
		'tec-header' => $tec_header,
	];
	$tec_fields = array_merge($tec_fields, $tec_fields_section);
} else {
	$sample_date = strtotime( 'January 15 ' . date( 'Y' ) );

	// Create miscellaneous header
	$misc_header = new Div( new Element_Classes( [ 'tec-settings-form__header-block' ] ) );
	$misc_header->add_child(
		new Heading(
			__( 'Miscellaneous Settings', 'event-tickets' ),
			3,
			new Element_Classes( 'tec-settings-form__section-header' )
		)
	);

	// Create miscellaneous section without including the header in the wrapped content
	$misc_fields_content = tribe( 'settings' )->wrap_section_content(
		'misc-section',
		[
			'debugEvents' => [
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
			],
			'datepickerFormat' => [
				'type'            => 'dropdown',
				'label'           => esc_html__( 'Compact Date Format', 'event-tickets' ),
				'tooltip'         => esc_html__( 'Select the date format used for elements with minimal space, such as in datepickers.', 'event-tickets' ),
				'default'         => 1,
				'options'         => [
					'0'  => date( 'Y-m-d', $sample_date ),
					'1'  => date( 'n/j/Y', $sample_date ),
					'2'  => date( 'm/d/Y', $sample_date ),
					'3'  => date( 'j/n/Y', $sample_date ),
					'4'  => date( 'd/m/Y', $sample_date ),
					'5'  => date( 'n-j-Y', $sample_date ),
					'6'  => date( 'm-d-Y', $sample_date ),
					'7'  => date( 'j-n-Y', $sample_date ),
					'8'  => date( 'd-m-Y', $sample_date ),
					'9'  => date( 'Y.m.d', $sample_date ),
					'10' => date( 'm.d.Y', $sample_date ),
					'11' => date( 'd.m.Y', $sample_date ),
				],
				'validation_type' => 'options',
			],
		]
	);

	$misc_fields = [
		'misc-header' => $misc_header,
	];
	$misc_fields = array_merge($misc_fields, $misc_fields_content);
}

// Create authentication requirements header
$auth_header = new Div( new Element_Classes( [ 'tec-settings-form__header-block' ] ) );
$auth_header->add_child(
	new Heading(
		__( 'Login Requirements', 'event-tickets' ),
		3,
		new Element_Classes( 'tec-settings-form__section-header' )
	)
);

// Create auth paragraph as HTML field instead of using entities
$auth_description = [
	'auth-description' => [
		'type' => 'html',
		'html' => '<div class="tec-settings-form__description"><p>' . 
			sprintf(
				_x( 'You can require that users log into your site before they are able to %1$s (or buy %2$s). Please review your WordPress Membership option (via the %3$sGeneral Settings admin screen%4$s) before adjusting this setting.',
					'ticket authentication requirements',
					'event-tickets'
				),
				tribe_get_rsvp_label_singular( 'authentication_requirements_advice' ),
				tribe_get_ticket_label_plural_lowercase( 'authentication_requirements_advice' ),
				'<a href="' . esc_url( get_admin_url( null, 'options-general.php' ) ) . '" target="_blank">',
				'</a>'
			) . 
		'</p></div>',
	],
];

// Create authentication requirements section without including the header in the wrapped content
$auth_fields_section = tribe( 'settings' )->wrap_section_content(
	'auth-requirements-section',
	array_merge(
		$auth_description,
		[
			'ticket-authentication-requirements' => [
				'type'            => 'checkbox_list',
				'options'         => $ticket_addons,
				'validation_type' => 'options_multi',
				'can_be_empty'    => true,
			],
		]
	)
);

$authentication_fields_section = [
	'auth-header' => $auth_header,
];
$authentication_fields_section = array_merge($authentication_fields_section, $auth_fields_section);

$commerce_fields = [];

/**
 * Hide Legacy PayPal settings for new installations.
 */
if ( tec_tribe_commerce_is_available() ) {
	$commerce_fields = include 'tribe-commerce-settings.php';
}

/**
 * Allows for the specific filtering of the commerce fields.
 *
 * @since 5.1.6
 *
 * @param array $commerce_fields Which fields are already present from legacy.
 */
$commerce_fields = (array) apply_filters( 'tec_tickets_commerce_settings', $commerce_fields );

$tickets_fields = array_merge(
	$tickets_fields,
	$tec_fields,
	$authentication_fields_section,
	$commerce_fields,
	$misc_fields
);

/**
 * Filters the fields to be registered in the Settings > General tab.
 *
 * A field definition is one suitable to be consumed by the `Tribe__Settings_Tab` class.
 *
 * @see Tribe__Settings_Tab
 * @see Tribe__Field
 *
 * @param array $tickets_fields An associative array of fields definitions to register.
 */
$tickets_fields = apply_filters( 'tribe_tickets_settings_tab_fields', $tickets_fields );

$tickets_tab = [
	'priority' => 20,
	'fields'   => $tickets_fields,
];
