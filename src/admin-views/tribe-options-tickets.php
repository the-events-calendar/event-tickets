<?php

$currency_code_options = array(
	'AUD' => __( 'Australian Dollar (AUD)', 'event-tickets' ),
	'BRL' => __( 'Brazilian Real  (BRL)', 'event-tickets' ),
	'CAD' => __( 'Canadian Dollar (CAD)', 'event-tickets' ),
	'CZK' => __( 'Czech Koruna (CZK)', 'event-tickets' ),
	'DKK' => __( 'Danish Krone (DKK)', 'event-tickets' ),
	'EUR' => __( 'Euro (EUR)', 'event-tickets' ),
	'HKD' => __( 'Hong Kong Dollar (HKD)', 'event-tickets' ),
	'HUF' => __( 'Hungarian Forint (HUF)', 'event-tickets' ),
	'ILS' => __( 'Israeli New Sheqel (ILS)', 'event-tickets' ),
	'JPY' => __( 'Japanese Yen (JPY)', 'event-tickets' ),
	'MYR' => __( 'Malaysian Ringgit (MYR)', 'event-tickets' ),
	'MXN' => __( 'Mexican Peso (MXN)', 'event-tickets' ),
	'NOK' => __( 'Norwegian Krone (NOK)', 'event-tickets' ),
	'NZD' => __( 'New Zealand Dollar (NZD)', 'event-tickets' ),
	'PHP' => __( 'Philippine Peso (PHP)', 'event-tickets' ),
	'PLN' => __( 'Polish Zloty (PLN)', 'event-tickets' ),
	'GBP' => __( 'Pound Sterling (GBP)', 'event-tickets' ),
	'SGD' => __( 'Singapore Dollar (SGD)', 'event-tickets' ),
	'SEK' => __( 'Swedish Krona (SEK)', 'event-tickets' ),
	'CHF' => __( 'Swiss Franc (CHF)', 'event-tickets' ),
	'TWD' => __( 'Taiwan New Dollar (TWD)', 'event-tickets' ),
	'THB' => __( 'Thai Baht (THB)', 'event-tickets' ),
	'USD' => __( 'U.S. Dollar (USD)', 'event-tickets' ),
);

/**
 * Filters the available currency code options for PayPal
 *
 * @since TBD
 *
 * @param array $currency_code_options
 */
$currency_code_options = apply_filters( 'tribe_tickets_paypal_currency_code_options', $currency_code_options );

$post_types_to_ignore = apply_filters( 'tribe_tickets_settings_post_type_ignore_list', array(
	'attachment',
) );

$all_post_type_objects = get_post_types( array( 'public' => true ), 'objects' );
$all_post_types = array();

foreach ( $all_post_type_objects as $post_type => $post_type_object ) {
	$should_ignore = false;

	foreach ( $post_types_to_ignore as $ignore ) {
		if ( preg_match( '/' . preg_quote( $ignore ) . '/', $post_type ) ) {
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
$options = get_option( Tribe__Main::OPTIONNAME, array() );

/**
 * List of ticketing solutions that support login requirements (ie, disabling or
 * enabling the ticket form according to whether a user is logged in or not).
 *
 * @param array $ticket_systems
 */
$ticket_addons = apply_filters( 'tribe_tickets_settings_systems_supporting_login_requirements', array(
	'event-tickets_rsvp' => __( 'Require users to log in before they RSVP', 'event-tickets' ),
	'event-tickets_all'  => __( 'Require users to log in before they purchase tickets', 'event-tickets' ),
) );

$tickets_fields = array(
	'tribe-form-content-start'  => array(
		'type' => 'html',
		'html' => '<div class="tribe-settings-form-wrap">',
	),
	'tickets-title'             => array(
		'type' => 'html',
		'html' => '<h3>' . esc_html__( 'Ticket Settings', 'event-tickets' ) . '</h3>',
	),
	'ticket-enabled-post-types' => array(
		'type'            => 'checkbox_list',
		'label'           => esc_html__( 'Post types that can have tickets', 'event-tickets' ),
		// only set the default to tribe_events if the ticket-endabled-post-types index has never been saved
		'default'         => array_key_exists( 'ticket-enabled-post-types', $options ) ? false : 'tribe_events',
		'options'         => $all_post_types,
		'can_be_empty'    => false,
		'validation_type' => 'options_multi',
	)
);

/**
 * If  The Events Calendar is active let's add an option to control the position
 * of the ticket forms in the events view.
 */
if ( class_exists( 'Tribe__Events__Main' ) ) {
	$ticket_form_location_options = array(
		'tribe_events_single_event_after_the_meta'     => __( 'Below the event details [default]', 'event-tickets' ),
		'tribe_events_single_event_before_the_meta'    => __( 'Above the event details', 'event-tickets' ),
		'tribe_events_single_event_after_the_content'  => __( 'Below the event description', 'event-tickets' ),
		'tribe_events_single_event_before_the_content' => __( 'Above the event description', 'event-tickets' ),
	);

	$tickets_fields = array_merge( $tickets_fields, array(
		'ticket-rsvp-form-location'     => array(
			'type'            => 'dropdown',
			'label'           => esc_html__( 'Location of RSVP form', 'event-tickets' ),
			'options'         => $ticket_form_location_options,
			'validation_type' => 'options',
			'parent_option'   => Tribe__Events__Main::OPTIONNAME,
			'default'         => reset( $ticket_form_location_options ),
		),
		'ticket-commerce-form-location' => array(
			'type'            => 'dropdown',
			'label'           => esc_html__( 'Location of Tickets form', 'event-tickets' ),
			'options'         => $ticket_form_location_options,
			'validation_type' => 'options',
			'parent_option'   => Tribe__Events__Main::OPTIONNAME,
			'default'         => reset( $ticket_form_location_options ),
		),
	) );
}

$tickets_fields = array_merge( $tickets_fields, array(

		'ticket-authentication-requirements-heading' => array(
			'type' => 'html',
			'html' => '<h3>' . __( 'Login Requirements', 'event-tickets' ) . '</h3>',
		),
		'ticket-authentication-requirements-advice'  => array(
			'type' => 'html',
			'html' => '<p>'
			          . sprintf( __( 'You can require that users log into your site before they are able to RSVP (or buy tickets). Please review your WordPress Membership option (via the General Settings admin screen) before adjusting this setting.',
					'event-tickets' ), '<a href="' . esc_url( get_admin_url( null, 'options-general.php' ) ) . '" target="_blank">', '</a>' )
			          . '</p>',
		),
		'ticket-authentication-requirements'         => array(
			'type'            => 'checkbox_list',
			'options'         => $ticket_addons,
			'validation_type' => 'options_multi',
			'can_be_empty'    => true,
		),
	)
);

$tickets_fields = array_merge(
	$tickets_fields,
	array(
		'ticket-paypal-heading' => array(
			'type' => 'html',
			'html' => '<h3>' . __( 'Tribe Commerce', 'event-tickets' ) . '</h3>',
		),
		'ticket-paypal-email' => array(
			'type'            => 'text',
			'label'           => esc_html__( 'PayPal Email', 'event-tickets' ),
			'tooltip'         => esc_html__( 'Email address that will receive PayPal payments.', 'event-tickets' ),
			'size'            => 'medium',
			'default'         => '',
			'validation_type' => 'html',
		),
		'ticket-paypal-sandbox' => array(
			'type'            => 'checkbox_bool',
			'label'           => esc_html__( 'PayPal Sandbox', 'event-tickets' ),
			'tooltip'         => esc_html__( 'Enables PayPal Sandbox mode for testing.', 'event-tickets' ),
			'default'         => false,
			'validation_type' => 'boolean',
		),
		'ticket-paypal-identity-token' => array(
			'type'            => 'text',
			'label'           => esc_html__( 'PayPal Identity Token', 'event-tickets' ),
			'tooltip'         => esc_html__( 'This is an optional field that will allow you to identify pending and successful payments without the need for PayPal IPN. To obtain your identifier, log into your PayPal account, click on Profile, then click on Website Payment Preferences. Here, enable Payment Data Transfer. You will then see your PayPal Identity Token displayed.', 'event-tickets' ),
			'size'            => 'medium',
			'default'         => '',
			'validation_type' => 'html',
		),
		'ticket-currency-heading' => array(
			'type' => 'html',
			'html' => '<h3>' . __( 'Currency', 'event-tickets' ) . '</h3>',
		),
		'ticket-paypal-currency-code' => array(
			'type'            => 'dropdown',
			'label'           => esc_html__( 'Currency Code', 'event-tickets' ),
			'tooltip'         => esc_html__( 'The currency that will be used for PayPal purchases.', 'event-tickets' ),
			'default'         => 'USD',
			'validation_type' => 'options',
			'options'         => $currency_code_options,
		),
		'defaultCurrencySymbol' => array(
			'type'            => 'text',
			'label'           => esc_html__( 'Symbol', 'event-tickets' ),
			'size'            => 'small',
			'default'         => '$',
			'validation_type' => 'html',
		),
		'reverseCurrencyPosition' => array(
			'type'            => 'checkbox_bool',
			'label'           => esc_html__( 'Symbol Follows Value', 'event-tickets' ),
			'tooltip'         => esc_html__( 'The currency symbol normally precedes the value. Enabling this option positions the symbol after the value.', 'event-tickets' ),
			'default'         => false,
			'validation_type' => 'boolean',
		),
		'ticket-currency-decimal' => array(
			'type'            => 'text',
			'label'           => esc_html__( 'Decimal Separator', 'event-tickets' ),
			'size'            => 'small',
			'default'         => '.',
			'validation_type' => 'html',
		),
		'ticket-currency-thousands' => array(
			'type'            => 'text',
			'label'           => esc_html__( 'Thousands Separator', 'event-tickets' ),
			'size'            => 'small',
			'default'         => ',',
			'validation_type' => 'html',
		),
	)
);

$tickets_fields = array_merge( $tickets_fields, array(
	'tribe-form-content-end'                     => array(
		'type' => 'html',
		'html' => '</div>',
	),
) );

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

$tickets_tab = array(
	'priority' => 20,
	'fields' => $tickets_fields,
);
