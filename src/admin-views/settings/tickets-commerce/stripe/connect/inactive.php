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

<h2 class="tec-tickets__admin-settings-tickets-commerce-stripe-title">
	<?php esc_html_e( 'Accept online payments with Stripe!', 'event-tickets' ); ?>
</h2>

<div class="tec-tickets__admin-settings-tickets-commerce-stripe-description">
	<p>
		<?php esc_html_e( 'Start selling tickets to your events today with Stripe. Attendees can purchase tickets directly on your site using debit or credit cards with no additional fees.', 'event-tickets' ); ?>
	</p>

	<div class="tec-tickets__admin-settings-tickets-commerce-stripe-signup-links">
		<?php $signup->get_link_html(); ?>
	</div>

	<?php $this->template( 'settings/tickets-commerce/stripe/connect/help-links' ); ?>
</div>
