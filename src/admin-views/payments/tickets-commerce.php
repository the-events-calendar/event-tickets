<?php
/**
 * The Template for displaying the Tickets Commerce Payments Settings.
 *
 * @version TBD
 */
$paypal_seller_status = tribe( 'tickets.commerce.paypal.signup' )->get_seller_status();
$paypal_seller_status = 'active';

$display = '<div class="tec-tickets-commerce-paypal-connect">';

if ( 'active' === $paypal_seller_status ) {
	$display .= '<p>' . esc_html__( 'PayPal Status: Connected', 'event-tickets' ) . '</p>';
} else {
	$paypal_connect_url = tribe( 'tickets.commerce.paypal.signup' )->get_paypal_signup_link();
	$connect_button = '<div class="tec-tickets-commerce-connect-paypal-button"><a href=' . esc_url( $paypal_connect_url ) . ' id="connect_to_paypal">' . wp_kses( 'Connect Automatically with <i>PayPal</i>', 'post' ) . '</a></div>';

	$display .= '<h2>' . esc_html__( 'Accept online payments with PayPal!', 'event-tickets' ) . '</h2>
				' . esc_html__( 'Start selling tickets to your events today with PayPal. Attendees can purchase tickets directly on your site using debt or credit cards with no additional fees.',
			'event-tickets' ) . $connect_button;
}
$path = tribe_resource_url( 'images/admin/paypal_logo.png', false, null, Tribe__Tickets__Main::instance() );
$display .= '</div><div class="tec-tickets-commerce-paypal-logo"><img src=' . esc_url( $path ) . ' alt="Tickets Commerce PayPal Logo">';

if ( 'active' !== $paypal_seller_status ) {
	$display .= '<ul>
					<li>' . esc_html__( 'Credit and debit card payments', 'event-tickets' ) . '</li>
					<li>' . esc_html__( 'Easy, no API key connection', 'event-tickets' ) . '</li>
					<li>' . esc_html__( 'Accept payments from around the world', 'event-tickets' ) . '</li>
					<li>' . esc_html__( 'Support 3D Secure Payments', 'event-tickets' ) . '</li>
				</ul>';
}

$display .= '</div>';

?>
	<style>
	.switch {
		position: relative;
		display: inline-block;
		width: 40px;
		height: 22px;
		float: left;
		margin-right: 15px;
	}

	.switch input {
		opacity: 0;
		width: 0;
		height: 0;
	}

	.slider {
		position: absolute;
		cursor: pointer;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background-color: #ccc;
		-webkit-transition: .4s;
		transition: .4s;
	}

	.slider:before {
		position: absolute;
		content: "";
		height: 16px;
		width: 16px;
		left: 3px;
		bottom: 3px;
		background-color: white;
		-webkit-transition: .4s;
		transition: .4s;
	}

	input:checked + .slider {
		background-color: #278DBC;
	}

	input:focus + .slider {
		box-shadow: 0 0 1px #278DBC;
	}

	input:checked + .slider:before {
		-webkit-transform: translateX(18px);
		-ms-transform: translateX(18px);
		transform: translateX(18px);
	}

	.slider.round {
		border-radius: 34px;
	}

	.slider.round:before {
		border-radius: 50%;
	}

	.tec-tickets-commerce-toggle {
		margin-top: 45px;
	}

	.tec-tickets-commerce-toggle h2 {
		font-size: 20px;
		font-family: Helvetica, serif;
		font-style: normal;
		font-weight: bold;
		line-height: 23px;
		letter-spacing: 0.02em;
	}

	.tec-tickets-commerce-description {
		font-size: 13px;
		font-family: Helvetica, serif;
		font-style: normal;
		font-weight: normal;
		max-width: 84%;
		margin-bottom: 35px;
	}

	.tec-tickets-commerce-paypal {
		height: 325px;
		background: #FFFFFF;
		border: 1px solid #CCCCCC;
		box-sizing: border-box;
		border-radius: 4px;
		padding: 46px;
		display:flex;
		flex-direction:row;
		justify-content: space-around;
	}

	.tec-tickets-commerce-paypal-logo img {
		padding-right: 41px;
	}

	.tec-tickets-commerce-paypal-logo ul {
		list-style: none;
	}

	.tec-tickets-commerce-paypal-logo li {
		line-height: 1.8;
	}

	.tec-tickets-commerce-paypal-logo ul li::before {
		content: "\2022";
		color: #009BE1;
		font-weight: bold;
		display: inline-block;
		width: 10px;
		margin-left: -1em;
		font-size: 14px;
	}

	.tec-tickets-commerce-paypal-logo {
		font-style: italic;
		font-family: Helvetica, sans-serif, arial;
		font-weight: normal;
		font-size: 14px;
		line-height: 16px;
		letter-spacing: 0.02em;
	}

	.tec-tickets-commerce-paypal-connect {
		max-width: 552px;
	}

	.tec-tickets-commerce-paypal-connect,
	.tec-tickets-commerce-paypal-logo {
		display:flex;
		flex-direction:column;
	}

	.tec-tickets-commerce-connect-paypal-button a {
		position: absolute;
		background: #009BE1;
		border-radius: 4px;
		font-family: Helvetica, sans-serif, arial;
		font-style: normal;
		font-weight: bold;
		font-size: 16px;
		line-height: 18px;
		text-align: center;
		color: #FFFFFF;
		text-decoration: none;
		margin-top: 25px;
		padding: 15px 30px;
	}
</style>
<?php

//<div class="flex-table__renew-toggle">
/*	<button data-switch="<?php echo esc_attr( $toggle_state ); ?>" data-subscription-id="<?php echo esc_attr( $subscription->id ); ?>" class="switch-toggle subscription-auto-renew-toggle modal-toggle <?php echo esc_attr( implode( ' ', $toggle_classes ) ); ?>" data-switch-labels="" disabled aria-label="<?php echo esc_attr( $toggle_label ); ?>"></button>*/
//</div>

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
