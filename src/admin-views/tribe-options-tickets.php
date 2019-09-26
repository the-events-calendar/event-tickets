<?php

/**
 * Filter to allow users to add/alter ignored post types
 *
 * @since 4.7
 * @since 4.10.2 Update tooltip text for Confirmation email sender address and allow it to be saved as empty
 * @since TBD Use function for text.
 *
 * @version TBD
 */
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


include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

$tickets_plus_plugin       = 'event-tickets-plus/event-tickets-plus.php';
$available_plugins         = get_plugins();
$is_tickets_plus_available = array_key_exists( $tickets_plus_plugin, $available_plugins );


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

$tickets_fields = [
	'tribe-form-content-start'  => [
		'type' => 'html',
		'html' => '<div class="tribe-settings-form-wrap">',
	],
	'tickets-title'             => [
		'type' => 'html',
		'html' => '<h3>' . esc_html( sprintf( _x( '%s Settings', 'tickets fields settings title', 'event-tickets' ), tribe_get_ticket_label_singular( 'tickets_fields_settings_title' ) ) ) . '</h3>',
	],
	'ticket-enabled-post-types' => [
		'type'            => 'checkbox_list',
		'label'           => esc_html( sprintf( _x( 'Post types that can have %s', 'tickets fields settings enabled post types', 'event-tickets' ), tribe_get_ticket_label_singular_lowercase( 'tickets_fields_settings_enabled_post_types' ) ) ),
		// only set the default to tribe_events if the ticket-enabled-post-types index has never been saved
		'default'         => array_key_exists( 'ticket-enabled-post-types', $options ) ? false : 'tribe_events',
		'options'         => $all_post_types,
		'can_be_empty'    => true,
		'validation_type' => 'options_multi',
	],
];

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

	$tickets_fields['ticket-rsvp-form-location'] = [
		'type'            => 'dropdown',
		'label'           => esc_html( sprintf( _x( 'Location of %s form', 'form location setting', 'event-tickets' ), tribe_get_rsvp_label_singular( 'form_location_setting' ) ) ),
		'tooltip'         => esc_html__( 'This setting only impacts events made with the classic editor.', 'event-tickets' ),
		'options'         => $ticket_form_location_options,
		'validation_type' => 'options',
		'parent_option'   => Tribe__Events__Main::OPTIONNAME,
		'default'         => reset( $ticket_form_location_options ),
	];

	$tickets_fields['ticket-commerce-form-location'] = [
		'type'            => 'dropdown',
		'label'           => esc_html( sprintf( _x( 'Location of %s form', 'form location setting', 'event-tickets' ), tribe_get_ticket_label_plural( 'form_location_setting' ) ) ),
		'tooltip'         => esc_html__( 'This setting only impacts events made with the classic editor.', 'event-tickets' ),
		'options'         => $ticket_form_location_options,
		'validation_type' => 'options',
		'parent_option'   => Tribe__Events__Main::OPTIONNAME,
		'default'         => reset( $ticket_form_location_options ),
	];

	$tickets_fields['ticket-display-tickets-left-threshold'] = [
		'type'            => 'text',
		'label'           => esc_html( sprintf( _x( 'Display # %s left threshold', 'tickets remaining threshold label', 'event-tickets' ), tribe_get_ticket_label_plural_lowercase( 'remaining_threshold_setting_label' ) ) ),
		'tooltip'         => esc_html( sprintf( _x( 'If this number is less than the number of %1$s left for sale on your event, this will prevent the "# of %1$s left" text from showing on your website. You can leave this blank if you would like to always show the text.', 'tickets remaining threshold tooltip', 'event-tickets' ), tribe_get_ticket_label_plural_lowercase( 'remaining_threshold_setting_tooltip' ) ) ),
		'validation_type' => 'int',
		'size'            => 'small',
		'can_be_empty'    => true,
		'parent_option'   => Tribe__Events__Main::OPTIONNAME,
	];
}

$tickets_fields = array_merge( $tickets_fields, [
		'ticket-authentication-requirements-heading' => [
			'type' => 'html',
			'html' => '<h3>' . __( 'Login Requirements', 'event-tickets' ) . '</h3>',
		],
		'ticket-authentication-requirements-advice'  => [
			'type' => 'html',
			'html' => '<p>' . sprintf(
					_x( 'You can require that users log into your site before they are able to %1$s (or buy %2$s). Please review your WordPress Membership option (via the %3$sGeneral Settings admin screen%4$s) before adjusting this setting.',
					'ticket authentication requirements',
					'event-tickets'
					),
				tribe_get_rsvp_label_singular( 'authentication_requirements_advice' ),
				tribe_get_ticket_label_plural_lowercase( 'authentication_requirements_advice' ),
				'<a href="' . esc_url( get_admin_url( null, 'options-general.php' ) ) . '" target="_blank">',
				'</a>'
				) . '</p>',
		],
		'ticket-authentication-requirements'         => [
			'type'            => 'checkbox_list',
			'options'         => $ticket_addons,
			'validation_type' => 'options_multi',
			'can_be_empty'    => true,
		],
	]
);

$tickets_fields['ticket-paypal-heading'] = [
	'type' => 'html',
	'html' => '<h3>' . __( 'Tribe Commerce', 'event-tickets' ) . '</h3>',
];

if ( ! $is_tickets_plus_available ) {
	$plus_link = sprintf(
		'<a href="http://m.tri.be/19zl" target="_blank">%s</a>',
		__( 'Event Tickets Plus', 'tribe-common' )
	);

	$plus_link_2 = sprintf(
		'<a href="http://m.tri.be/19zl" target="_blank">%s</a>',
		__( 'Check it out!', 'tribe-common' )
	);

	$plus_message = sprintf(
		_x( 'Tribe Commerce is a light implementation of a commerce gateway using PayPal and simplified stock handling. If you need more advanced features, take a look at %1$s. In addition to integrating with your favorite ecommerce provider, Event Tickets Plus includes options to collect custom information for attendees, check users in via QR codes, and share stock between %2$s. %3$s', 'about Tribe Commerce', 'event-tickets' ),
		$plus_link,
		tribe_get_ticket_label_singular_lowercase( 'tickets_fields_settings_about_tribe_commerce' ),
		$plus_link_2
	);

	$tickets_fields['ticket-paypal-et-plus-header'] = [
		'type' => 'html',
		'html' => '<p>' . $plus_message . '</p>',
	];
}

//@todo clicking this should hide/show the settings
$tickets_fields['ticket-paypal-enable'] = [
	'type'            => 'checkbox_bool',
	'label'           => esc_html__( 'Enable Tribe Commerce ', 'event-tickets' ),
	'tooltip'         => esc_html__( 'Check this box if you wish to turn on Tribe Commerce functionality', 'event-tickets' ),
	'size'            => 'medium',
	'default'         => false,
	'validation_type' => 'boolean',
	'attributes'      => [ 'id' => 'ticket-paypal-enable-input' ],
];

$pages = get_pages( [ 'post_status' => 'publish', 'posts_per_page' => -1 ] );

if ( ! empty( $pages ) ) {
	$pages = array_combine( wp_list_pluck( $pages, 'ID' ), wp_list_pluck( $pages, 'post_title' ) );
}

// add an empty entry at the start
$pages = [ 0 => '' ] + $pages;
$default_page = reset( $pages );

$tpp_success_shortcode = 'tribe-tpp-success';

$paypal_currency_code_options = tribe( 'tickets.commerce.currency' )->generate_currency_code_options();

$current_user = get_user_by( 'id', get_current_user_id() );

// The KB article URL will change depending on whether ET+ is active or not
$paypal_setup_kb_url = class_exists( 'Tribe__Tickets_Plus__Main' )
	? 'http://m.tri.be/19yk'
	: 'http://m.tri.be/19yj';
$paypal_setup_kb_link = '<a href="' . esc_url( $paypal_setup_kb_url ) . '" target="_blank">' . esc_html__( 'these instructions', 'event-tickets' ) . '</a>';
$paypal_setup_note    = sprintf( esc_html_x( 'In order to use Tribe Commerce to sell %1$s, you must configure your PayPal account to communicate with your WordPress site. If you need help getting set up, follow %2$s', 'tickets fields settings PayPal setup', 'event-tickets' ), tribe_get_ticket_label_singular_lowercase( 'tickets_fields_settings_paypal_setup' ), $paypal_setup_kb_link );

$ipn_setup_line           = sprintf(
	'<span class="clear">%s</span><span class="clear">%s</span>',
	esc_html__( "Have you entered this site's address in the Notification URL field in IPN Settings?", 'event-tickets' ),
	sprintf(
		esc_html__( "Your site address is: %s", 'event-tickets' ),
		'<a href="' . esc_attr( home_url() ) . '" target="_blank">' . esc_html( home_url() ) . '</a>'
	)
);

$paypal_fields            = [
	'ticket-paypal-configure'         => [
		'type'            => 'wrapped_html',
		'label'           => esc_html__( 'Configure PayPal:', 'event-tickets' ),
		'html'            => '<p>' . $paypal_setup_note . '</p>',
		'validation_type' => 'html',
	],
	'ticket-paypal-email'             => [
		'type'            => 'email',
		'label'           => esc_html__( 'PayPal email to receive payments:', 'event-tickets' ),
		'size'            => 'large',
		'default'         => '',
		'validation_type' => 'email',
		'class'           => 'indent light-bordered checkmark checkmark-right checkmark-hide ipn-required',
	],
	'ticket-paypal-ipn-enabled'       => [
		'type'            => 'radio',
		'label'           => esc_html__( "Have you enabled instant payment notifications (IPN) in your PayPal account's Selling Tools?", 'event-tickets' ),
		'options'         => [
			'yes' => __( 'Yes', 'event-tickets' ),
			'no'  => __( 'No', 'event-tic->valuekets' ),
		],
		'size'            => 'large',
		'default'         => 'no',
		'validation_type' => 'options',
		'class'           => 'indent light-bordered checkmark checkmark-right checkmark-hide ipn-required',
	],
	'ticket-paypal-ipn-address-set'   => [
		'type'            => 'radio',
		'label'           => $ipn_setup_line,
		'options'         => [
			'yes' => __( 'Yes', 'event-tickets' ),
			'no'  => __( 'No', 'event-tickets' ),
		],
		'size'            => 'large',
		'default'         => 'no',
		'validation_type' => 'options',
		'class'           => 'indent light-bordered checkmark checkmark-right checkmark-hide ipn-required',
	],
	'ticket-paypal-ipn-config-status' => [
		'type'            => 'wrapped_html',
		'html'            => sprintf(
			'<strong>%1$s <span id="paypal-ipn-config-status" data-status="%2$s">%3$s</span></strong><p class="description"><i>%4$s</i></p>',
			esc_html__( 'PayPal configuration status:', 'event-tickets' ),
			esc_attr( tribe( 'tickets.commerce.paypal.handler.ipn' )->get_config_status( 'slug' ) ),
			esc_html( tribe( 'tickets.commerce.paypal.handler.ipn' )->get_config_status( 'label' ) ),
			esc_html__( 'For help creating and configuring your account, call PayPal at 1-844-720-4038 (USA)', 'event-tickets' )
		),
		'size'            => 'large',
		'default'         => 'no',
		'validation_type' => 'html',
		'class'           => 'indent light-bordered',
	],
	'ticket-paypal-sandbox'           => [
		'type'            => 'checkbox_bool',
		'label'           => esc_html__( 'PayPal Sandbox', 'event-tickets' ),
		'tooltip'         => esc_html__( 'Enables PayPal Sandbox mode for testing.', 'event-tickets' ),
		'default'         => false,
		'validation_type' => 'boolean',
	],
	'ticket-commerce-currency-code'   => [
		'type'            => 'dropdown',
		'label'           => esc_html__( 'Currency Code', 'event-tickets' ),
		'tooltip'         => esc_html__( 'The currency that will be used for Tribe Commerce transactions.', 'event-tickets' ),
		'default'         => 'USD',
		'validation_type' => 'options',
		'options'         => $paypal_currency_code_options,
	],
	'ticket-paypal-stock-handling'           => [
		'type'            => 'radio',
		'label'           => esc_html__( 'Stock Handling', 'event-tickets' ),
		'tooltip'         => esc_html( sprintf( _x( 'When a customer purchases a %s, PayPal might flag the order as Pending. The order will be Complete once payment is confirmed by PayPal.', 'tickets fields settings paypal stock handling', 'event-tickets' ), tribe_get_ticket_label_singular_lowercase( 'tickets_fields_settings_paypal_stock_handling' ) ) ),
		'default'         => 'on-pending',
		'validation_type' => 'options',
		'options'         => [
			'on-pending'  => esc_html__( 'Decrease available ticket stock as soon as a Pending order is created.', 'event-tickets' ),
			'on-complete' => esc_html__( 'Only decrease available ticket stock if an order is confirmed as Completed by PayPal.', 'event-tickets' ),
		],
		'tooltip_first' => true,
	],
	'ticket-paypal-success-page'      => [
		'type'            => 'dropdown',
		'label'           => esc_html__( 'Success page', 'event-tickets' ),
		'tooltip'         => esc_html(
			                     sprintf(
				                     __( 'After a successful PayPal order users will be redirected to this page; use the %s shortcode to display the order confirmation to the user in the page content.', 'event-tickets' ),
				                     "[$tpp_success_shortcode]"
			                     )
		                     ),
		'size'            => 'medium',
		'validation_type' => 'options',
		'options'         => $pages,
		'required'        => true,
	],
	'ticket-paypal-confirmation-email-sender-email' => [
		'type'            => 'email',
		'label'           => esc_html__( 'Confirmation email sender address', 'event-tickets' ),
		'tooltip'         => esc_html( sprintf( _x( 'Email address PayPal %s customers will receive confirmation from. Leave empty to use the default WordPress site email address.', 'tickets fields settings paypal confirmation email', 'event-tickets' ), tribe_get_ticket_label_plural_lowercase( 'tickets_fields_settings_paypal_confirmation_email' ) ) ),
		'size'            => 'medium',
		'default'         => $current_user->user_email,
		'validation_type' => 'email',
		'can_be_empty'    => true,
	],
	'ticket-paypal-confirmation-email-sender-name' => [
		'type'                => 'text',
		'label'               => esc_html__( 'Confirmation email sender name', 'event-tickets' ),
		'tooltip'             => esc_html( sprintf( _x( 'Sender name of the confirmation email sent to customers when confirming a %s purchase.', 'tickets fields settings paypal email sender', 'event-tickets' ), tribe_get_ticket_label_singular_lowercase( 'tickets_fields_settings_paypal_email_sender' ) ) ),
		'size'                => 'medium',
		'default'             => $current_user->user_nicename,
		'validation_callback' => 'is_string',
		'validation_type'     => 'textarea',
	],
	'ticket-paypal-confirmation-email-subject' => [
		'type'                => 'text',
		'label'               => esc_html__( 'Confirmation email subject', 'event-tickets' ),
		'tooltip'             => esc_html( sprintf( _x( 'Subject of the confirmation email sent to customers when confirming a %s purchase.', 'tickets fields settings paypal email subject', 'event-tickets' ), tribe_get_ticket_label_singular_lowercase( 'tickets_fields_settings_paypal_email_subject' ) ) ),
		'size'                => 'large',
		'default'             => esc_html( sprintf( _x( 'You have %s!', 'tickets fields settings paypal email subject', 'event-tickets' ), tribe_get_ticket_label_plural_lowercase( 'tickets_fields_settings_paypal_email_subject' ) ) ),
		'validation_callback' => 'is_string',
		'validation_type'     => 'textarea',
	],
];

if ( defined( 'WP_DEBUG' ) && true === WP_DEBUG ) {
	$ipn_fields = [
		'ticket-paypal-notify-history' => [
			'type'            => 'wrapped_html',
			'html'            => '<p>' .
			                     sprintf(
				                     esc_html__( 'You can see and manage your IPN Notifications history from the IPN Notifications settings area (%s).', 'event-tickets' ),
				                     tribe( 'tickets.commerce.paypal.links' )->ipn_notification_history( 'tag' )
			                     ) .
			                     '</p>',
			'size'            => 'medium',
			'validation_type' => 'html',
			'class'           => 'indent light-bordered',
		],
		'ticket-paypal-notify-url'     => [
			'type'            => 'text',
			'label'           => esc_html__( 'IPN Notify URL', 'event-tickets' ),
			'tooltip'         => sprintf(
					esc_html__( 'Override the default IPN notify URL with this value. This value must be the same set in PayPal IPN Notifications settings area (%s).', 'event-tickets' ),
					tribe( 'tickets.commerce.paypal.links' )->ipn_notification_settings( 'tag' )
			),
			'default'         => home_url(),
			'validation_type' => 'html',
		],
	];

	$paypal_fields = Tribe__Main::array_insert_after_key( 'ticket-paypal-ipn-config-status', $paypal_fields, $ipn_fields );
}

foreach ( $paypal_fields as $key => &$commerce_field ) {
	$field_classes = (array) Tribe__Utils__Array::get( $commerce_field, 'class', [] );
	array_push( $field_classes, 'tribe-dependent' );
	$commerce_field['class']               = implode( ' ', $field_classes );
	$existing_field_attributes             = Tribe__Utils__Array::get( $commerce_field, 'fieldset_attributes', [] );
	$additional_attributes = [
		'data-depends'              => '#ticket-paypal-enable-input',
		'data-condition-is-checked' => '',
	];
	if ( 'checkbox_bool' === $commerce_field['type'] ) {
		$additional_attributes['data-dependency-dont-disable'] = '1';
	}
	$commerce_field['fieldset_attributes'] = array_merge( $existing_field_attributes, $additional_attributes );
	$commerce_field['validate_if']         = new Tribe__Field_Conditional( 'ticket-paypal-enable', 'tribe_is_truthy' );
}

unset( $commerce_field );

$tickets_fields  = array_merge(
	$tickets_fields,
	$paypal_fields
);

$tickets_fields = array_merge( $tickets_fields, [
	'tribe-form-content-end' => [
		'type' => 'html',
		'html' => '</div>',
	],
]
);

/**
 * Filters the fields to be registered in the Events > Settings > Tickets tab.
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
	'fields' => $tickets_fields,
];