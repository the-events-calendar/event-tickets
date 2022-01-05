<?php
/**
 * The Template for displaying the Tickets Commerce PayPal Settings when inactive (not connected).
 *
 * @version 5.1.10
 *
 * @since   5.1.10
 *
 * @var Tribe__Tickets__Admin__Views                  $this                  [Global] Template object.
 * @var string                                        $plugin_url            [Global] The plugin URL.
 * @var TEC\Tickets\Commerce\Gateways\PayPal\Merchant $merchant              [Global] The merchant class.
 * @var TEC\Tickets\Commerce\Gateways\PayPal\Signup   $signup                [Global] The Signup class.
 * @var bool                                          $is_merchant_active    [Global] Whether the merchant is active or not.
 * @var bool                                          $is_merchant_connected [Global] Whether the merchant is connected or not.
 */

if ( ! empty( $is_merchant_connected ) ) {
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
