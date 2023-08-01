<?php
/**
 * The Template for displaying the Tickets Commerce PayPal Settings when inactive (not connected).
 *
 * @version 5.3.0
 *
 * @since   5.1.10
 * @since   5.3.0 Using generic CSS classes for gateway instead of PayPal.
 *
 * @var Tribe__Tickets__Admin__Views                  $this                  [Global] Template object.
 * @var string                                        $plugin_url            [Global] The plugin URL.
 * @var TEC\Tickets\Commerce\Gateways\PayPal\Merchant $merchant              [Global] The merchant class.
 * @var TEC\Tickets\Commerce\Gateways\PayPal\Signup   $signup                [Global] The Signup class.
 * @var bool                                          $is_merchant_active    [Global] Whether the merchant is active or not.
 * @var bool                                          $is_merchant_connected [Global] Whether the merchant is connected or not.
 * @var bool                                          $is_ssl                [Global] Whether the site is SSL or not.
 */

if ( ! empty( $is_merchant_connected ) ) {
	return;
}

// Determine whether or not site is using SSL.
$is_ssl = is_ssl();

?>

<h2 class="tec-tickets__admin-settings-tickets-commerce-gateway-title">
	<?php esc_html_e( 'Accept online payments with PayPal!', 'event-tickets' ); ?>
</h2>

<div class="tec-tickets__admin-settings-tickets-commerce-gateway-description">
	<p>
		<?php esc_html_e( 'Start selling tickets to your events today with PayPal. Attendees can purchase tickets directly on your site using debit or credit cards with no additional fees.', 'event-tickets' ); ?>
	</p>
	<?php 
	if ( $is_ssl ) :
		$this->template( 'settings/tickets-commerce/paypal/connect/signup-link', [ 'is_ssl' => $is_ssl ] );
	else :
		$this->template( 'settings/tickets-commerce/paypal/connect/non-ssl-notice', [ 'is_ssl' => $is_ssl ] ); 
	endif;
	?>
	<?php $this->template( 'settings/tickets-commerce/paypal/connect/help-links' ); ?>
</div>
