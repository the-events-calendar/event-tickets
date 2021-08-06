<?php
/**
 * The Template for displaying the Tickets Commerce Payments Settings.
 *
 * @version TBD
 */

use TEC\Tickets\Commerce\Gateways\PayPal\REST\On_Boarding;
use TEC\Tickets\Commerce\Gateways\PayPal\SignUp\Onboard;

$paypal_seller_status = tribe( Onboard::class )->get_seller_status();

$display = '<div class="tec-tickets-commerce-paypal-connect">';

if ( 'active' === $paypal_seller_status ) {
	$display .= '<p>' . esc_html__( 'PayPal Status: Connected', 'event-tickets' ) . '</p>';
} else {
	$paypal_connect_url = tribe( On_Boarding::class )->get_paypal_signup_link();
	$connect_button = '<div class="tec-tickets-commerce-connect-paypal-button"><a href=' . esc_url( $paypal_connect_url ) . ' id="connect_to_paypal">' . wp_kses( 'Connect Automatically with <i>PayPal</i>', 'post' ) . '</a></div>';

	$display .= '<h2>' . esc_html__( 'Accept online payments with PayPal!', 'event-tickets' ) . '</h2>
				' . esc_html__( 'Start selling tickets to your events today with PayPal. Attendees can purchase tickets directly on your site using debt or credit cards with no additional fees.',
			'event-tickets' ) . $connect_button;
}
$path = tribe_resource_url( 'images/admin/paypal_logo.png', false, null, Tribe__Tickets__Main::instance() );
$display .= '</div><div class="tec-tickets-commerce-paypal-logo"><img src=' . esc_url( $path ) . ' alt="Tickets Commerce PayPal Logo">';

if ( 'active' !== $paypal_seller_status ) {
	$display .= '
		<ul>
			<li>' . esc_html__( 'Credit and debit card payments', 'event-tickets' ) . '</li>
			<li>' . esc_html__( 'Easy, no API key connection', 'event-tickets' ) . '</li>
			<li>' . esc_html__( 'Accept payments from around the world', 'event-tickets' ) . '</li>
			<li>' . esc_html__( 'Support 3D Secure Payments', 'event-tickets' ) . '</li>
		</ul>
	';
}

$display .= '</div>';

$tickets_fields = [
	'tribe-form-content-start' => [
		'type' => 'html',
		'html' => '<div class="tribe-settings-form-wrap">',
	],
	'tickets-commerce-header' => [
		'type' => 'html',
		'html' => '<div class="tec-tickets-commerce-toggle"><label class="switch"><input type="checkbox"><span class="slider round"></span></label><h2>' . esc_html__( 'Enable TicketsCommerce', 'event-tickets' ) . '</h2></div>',
	],
	'tickets-commerce-description' => [
		'type' => 'html',
		'html' => '<div class="tec-tickets-commerce-description">' . esc_html__( 'TicketsCommerce allows you to accept payments for tickets with Event Tickets and Event Tickets Plus. Configure payments through PayPal, allowing users to pay with credit card or their PayPal account. Learn More about payment processing with TicketsCommerce.' ) . '</div>',
	],
	'tickets-commerce-paypal-description' => [
		'type' => 'html',
		'html' => '<div class="tec-tickets-commerce-paypal">' . $display . '</div>',
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
