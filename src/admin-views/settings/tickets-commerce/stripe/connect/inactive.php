<?php
/**
 * The Template for displaying the Tickets Commerce Stripe Settings when inactive (not connected).
 *
 * @since   TBD
 *
 * @version TBD
 *
 * @var string                                        $plugin_url      [Global] The plugin URL.
 * @var TEC\Tickets\Commerce\Gateways\Stripe\Signup   $signup          [Global] The Signup class.
 * @var TEC\Tickets\Commerce\Gateways\Stripe\Merchant $merchant        [Global] The Signup class.
 * @var array                                         $merchant_status [Global] Merchant Status data.
 */

if ( true === $merchant_status['connected'] ) {
	return;
}

?>

<h2 class="tec-tickets__admin-settings-tickets-commerce-gateway-title">
	<?php esc_html_e( 'Accept online payments with Stripe!', 'event-tickets' ); ?>
</h2>

<div class="tec-tickets__admin-settings-tickets-commerce-gateway-description">
	<p>
		<?php echo __( 'Start selling tickets to your events today with Stripe integration for Tickets Commerce. Enable highly-configurable credit and debit card checkout with enhanced features like Afterpay.<br><br>Stripe charges a 3% fee for all transactions.', 'event-tickets' ); ?>
	</p>

	<div class="tec-tickets__admin-settings-tickets-commerce-gateway-signup-links">
		<?php $signup->get_link_html(); ?>
	</div>

	<?php $this->template( 'settings/tickets-commerce/stripe/connect/help-links' ); ?>
</div>
