<?php
/**
 * Tickets Commerce: Checkout Purchaser Info.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/checkout/purchaser-info.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since 5.3.0
 *
 * @version 5.3.0
 *
 * @var \Tribe__Template $this [Global] Template object.
 * @var array[] $items [Global] List of Items on the cart to be checked out.
 * @var bool $must_login Global] Whether login is required to buy tickets or not.
 */

// Bail if the cart is empty.
if ( empty( $items ) ) {
	return;
}

// Bail if user needs to login, but is not logged in.
if ( $must_login && ! is_user_logged_in() ) {
	return;
}

$info_title   = __( 'Purchaser info', 'event-tickets' );
$show_address = false;
foreach ( $gateways as $gateway ) {
	// Check if Stripe is active and enabled.
	if (
		'stripe' === $gateway::get_key()
		&& $gateway::is_enabled()
		&& $gateway::is_active()
	) {
		$payment_methods = ( new TEC\Tickets\Commerce\Gateways\Stripe\Merchant() )->get_payment_method_types();
		// If more than one payment method, or if only one but not a card, we need to show the address fields.
		if (
			1 < count( $payment_methods )
			|| (
				1 === count( $payment_methods )
				&& 'card' !== $payment_methods[0]
			)
		) {
			$info_title   = __( 'Billing info', 'event-tickets' );
			$show_address = true;
		}
	}
}

?>
<div class="tribe-tickets__form tribe-tickets__commerce-checkout-purchaser-info-wrapper tribe-common-b2">
	<h4 class="tribe-common-h5 tribe-tickets__commerce-checkout-purchaser-info-title"><?php echo esc_html( $info_title ); ?></h4>
	<?php $this->template( 'checkout/purchaser-info/name', [ 'show_address' => $show_address ] ); ?>
	<?php $this->template( 'checkout/purchaser-info/email' ); ?>
	<?php if ( $show_address ) : ?>
		<?php $this->template( 'checkout/purchaser-info/address' ); ?>
		<div class="tribe-tickets__commerce-checkout-address-wrapper">
			<?php $this->template( 'checkout/purchaser-info/city' ); ?>
			<?php $this->template( 'checkout/purchaser-info/state' ); ?>
			<?php $this->template( 'checkout/purchaser-info/zip' ); ?>
			<?php $this->template( 'checkout/purchaser-info/country' ); ?>
		</div>
		<button id="tec-tc-gateway-stripe-render-payment" class="tribe-common-c-btn tribe-tickets__commerce-checkout-form-submit-button">
			<?php esc_html_e( 'Proceed to payment', 'event-tickets' ); ?>
		</button>
	<?php endif; ?>
</div>
