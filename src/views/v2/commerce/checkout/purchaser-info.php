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
 * @since 5.19.3 Added a surrounding form to the purchaser info fields.
 *
 * @version 5.19.3
 *
 * @var Checkout_Shortcode $shortcode [Global] The checkout shortcode instance.
 * @var \Tribe__Template $this [Global] Template object.
 * @var array[] $items [Global] List of Items on the cart to be checked out.
 * @var bool $must_login Global] Whether login is required to buy tickets or not.\
 * @var array $billing_fields [Global] List of billing fields to be displayed.
 */

use TEC\Tickets\Commerce\Shortcodes\Checkout_Shortcode;

if ( ! $shortcode->should_display_purchaser_info() ) {
	return;
}

?>
<div class="tribe-tickets__form tribe-tickets__commerce-checkout-purchaser-info-wrapper tribe-common-b2">
	<h4 class="tribe-common-h5 tribe-tickets__commerce-checkout-purchaser-info-title"><?php echo esc_html( $shortcode->get_purchaser_info_title() ); ?></h4>
	<form class="tribe-tickets__commerce-checkout-purchaser-info-wrapper__form">
		<?php
		$this->template(
			'checkout/purchaser-info/name',
			[
				'show_address' => $shortcode->should_display_billing_info(),
				'field'        => $billing_fields['name'],
			]
		);
		?>
		<?php $this->template( 'checkout/purchaser-info/email', [ 'field' => $billing_fields['email'] ] ); ?>
		<?php if ( $shortcode->should_display_billing_info() ) : ?>
			<?php $this->template( 'checkout/purchaser-info/address', [ 'field' => $billing_fields['address'] ] ); ?>
			<div class="tribe-tickets__commerce-checkout-address-wrapper">
				<?php $this->template( 'checkout/purchaser-info/city', [ 'field' => $billing_fields['city'] ] ); ?>
				<?php $this->template( 'checkout/purchaser-info/state', [ 'field' => $billing_fields['state'] ] ); ?>
				<?php $this->template( 'checkout/purchaser-info/zip', [ 'field' => $billing_fields['zip'] ] ); ?>
				<?php $this->template( 'checkout/purchaser-info/country', [ 'field' => $billing_fields['country'] ] ); ?>
			</div>
			<button id="tec-tc-gateway-stripe-render-payment" class="tribe-common-c-btn tribe-tickets__commerce-checkout-form-submit-button">
				<?php esc_html_e( 'Proceed to payment', 'event-tickets' ); ?>
			</button>
		<?php endif; ?>
	</form>
</div>
