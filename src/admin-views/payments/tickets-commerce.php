<?php
/**
 * The Template for displaying the Tickets Commerce Payments Settings.
 *
 * @version TBD
 */

use TEC\Tickets\Commerce\Gateways\PayPal\Merchant;
use TEC\Tickets\Commerce\Gateways\PayPal\Signup;

$is_merchant_active = tribe( Merchant::class )->is_active();

$display = '<div class="tec-tickets-commerce-paypal-connect">';

if ( $is_merchant_active ) {
	$disconnect_url = Tribe__Settings::instance()->get_url( [ 'tab' => 'payments', 'tc-action' => 'paypal-disconnect' ] );
	$name = get_option( 'tickets_commerce_merchant_id' );

	$disconnect = ' <a href="' . esc_url( $disconnect_url ) . '">' . esc_html__( 'Disconnect', 'event-tickets' ) . '</a>';
	$display .= '<p>' . esc_html__( 'PayPal Status: Connected', 'event-tickets' ) . '</p>';
	$display .= '<p>' . esc_html( sprintf( __( 'Connected as: %1$s', 'event-tickets' ), $name ) ) . $disconnect . '</p>';
} else {
	$display .= '<h2>' . esc_html__( 'Accept online payments with PayPal!', 'event-tickets' ) . '</h2>
				' . esc_html__( 'Start selling tickets to your events today with PayPal. Attendees can purchase tickets directly on your site using debt or credit cards with no additional fees.',
			'event-tickets' ) . tribe( Signup::class )->get_link_html();
}
$path    = tribe_resource_url( 'images/admin/paypal_logo.png', false, null, Tribe__Tickets__Main::instance() );
$display .= '</div><div class="tec-tickets-commerce-paypal-logo"><img src=' . esc_url( $path ) . ' alt="Tickets Commerce PayPal Logo">';

$display .= '
	<ul>
		<li>' . esc_html__( 'Credit and debit card payments', 'event-tickets' ) . '</li>
		<li>' . esc_html__( 'Easy, no API key connection', 'event-tickets' ) . '</li>
		<li>' . esc_html__( 'Accept payments from around the world', 'event-tickets' ) . '</li>
		<li>' . esc_html__( 'Support 3D Secure Payments', 'event-tickets' ) . '</li>
	</ul>
';

$display .= '</div>';

$tickets_fields = [
	'tribe-form-content-start'            => [
		'type' => 'html',
		'html' => '<div class="tribe-settings-form-wrap tec-tickets-commerce-payments">',
	],
	'tickets-commerce-header'             => [
		'type' => 'html',
		'html' => '<div class="tec-tickets-commerce-toggle"><label class="tec-tickets-commerce-switch"><input type="checkbox"><span class="tec-tickets-commerce-slider round"></span></label><h2>' . esc_html__( 'Enable TicketsCommerce', 'event-tickets' ) . '</h2></div>',
	],
	'tickets-commerce-description'        => [
		'type' => 'html',
		'html' => '<div class="tec-tickets-commerce-description">' . esc_html__( 'TicketsCommerce allows you to accept payments for tickets with Event Tickets and Event Tickets Plus. Configure payments through PayPal, allowing users to pay with credit card or their PayPal account. Learn More about payment processing with TicketsCommerce.' ) . '</div>',
	],
	'tickets-commerce-paypal-description' => [
		'type' => 'html',
		'html' => '<div class="tec-tickets-commerce-paypal">' . $display . '</div>',
	],
	'tribe-form-content-end'              => [
		'type' => 'html',
		'html' => '</div>',
	],
];

/**
 * Filters the fields to be registered in the Events > Settings > Payments tab.
 *
 * @see Tribe__Field
 * @see Tribe__Settings_Tab
 *
 * @param array $tickets_fields An associative array of fields definitions to register.
 *
 */
$tickets_fields = apply_filters( 'tribe_tickets_commerce_payments_settings_tab_fields', $tickets_fields );

$tickets_tab = [
	'priority'  => 20,
	'fields'    => $tickets_fields,
	'show_save' => false,
];
