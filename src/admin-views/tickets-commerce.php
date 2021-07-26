<?php
/**
 * The Template for displaying the Tickets Commerce Payments Settings.
 *
 * @version TBD
 */
$paypal_seller_status = tribe( 'tickets.commerce.paypal.signup' )->get_seller_status();

if ( 'activee' === $paypal_seller_status ) {
	$display = '<div id="modern-tribe-info"><p>' . esc_html__( 'PayPal Status: Connected',
			'event-tickets' ) . '</p></div>';
} else {
	$paypal_connect_url = tribe( 'tickets.commerce.paypal.signup' )->get_paypal_signup_link();
	$connect_button     = '<a href=' . esc_html__( $paypal_connect_url ) . 'id="connect_to_paypal" class="button">' . esc_html__( 'Connect Automatically with PayPal',
			'event-tickets' ) . '</a>';
	$display            = '<div id="modern-tribe-info">
				<h2>' . esc_html__( 'Accept online payments with PayPal!', 'event-tickets' ) . '</h2>
				<p>' . esc_html__( 'Start selling tickets to your events today with PayPal. Attendees can purchase tickets directly on your site using debt or credit cards with no additional fees.',
			'event-tickets' ) . '</p>
				<ul>
					<li>' . esc_html__( 'Credit and debit card payments', 'event-tickets' ) . '</li>
					<li>' . esc_html__( 'Easy, no API key connection', 'event-tickets' ) . '</li>
					<li>' . esc_html__( 'Accept payments from around the world', 'event-tickets' ) . '</li>
					<li>' . esc_html__( 'Support 3D Secure Payments', 'event-tickets' ) . '</li>
				</ul>' . $connect_button . '</div>';
}

$tickets_fields = [
	'tribe-form-content-start' => [
		'type' => 'html',
		'html' => '<div class="tribe-settings-form-wrap">',
	],
	'tickets-commerce-header' => [
		'type' => 'html',
		'html' => '<h2>' . esc_html__( 'Enable TicketsCommerce', 'event-tickets' ) . '</h2>',
	],
	'tickets-commerce-description' => [
		'type' => 'html',
		'html' => '<p>' . esc_html__( 'TicketsCommerce allows you to accept payments for tickets with Event Tickets and Event Tickets Plus. Configure payments through PayPal, allowing users to pay with credit card or their PayPal account. Learn More about payment processing with TicketsCommerce.' ) . '</p>',
	],
	'tickets-commerce-paypal-description' => [
		'type' => 'html',
		'html' => $display,
	],
	'tribe-form-content-end' => [
		'type' => 'html',
		'html' => '</div>',
	],
];

/**
 * Filters the fields to be registered in the Events > Settings > Payments tab.
 *
 * @param  array  $tickets_fields  An associative array of fields definitions to register.
 *
 * @see Tribe__Field
 * @see Tribe__Settings_Tab
 */
$tickets_fields = apply_filters( 'tribe_tickets_commerce_payments_settings_tab_fields', $tickets_fields );

$tickets_tab = [
	'priority'  => 20,
	'fields'    => $tickets_fields,
	'show_save' => false,
];
