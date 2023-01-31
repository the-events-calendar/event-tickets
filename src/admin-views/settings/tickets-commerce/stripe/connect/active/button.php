<?php
/**
 * The Template for displaying the Tickets Commerce Stripe connection details.
 *
 * @since   5.3.0
 *
 * @version 5.3.0
 *
 * @var string                                        $plugin_url      [Global] The plugin URL.
 * @var TEC\Tickets\Commerce\Gateways\Stripe\Signup   $signup          [Global] The Signup class.
 * @var TEC\Tickets\Commerce\Gateways\Stripe\Merchant $merchant        [Global] The Signup class.
 * @var array                                         $merchant_status [Global] Merchant Status data.
 */

if ( false === $merchant_status['connected'] ) {
	return;
}

?>
<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-row">
	<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-col1"></div>
	<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-col2">
		<div class="tec-tickets__admin-settings-tickets-commerce-gateways-item-button">
			<a
				class="tec-tickets__admin-settings-tickets-commerce-gateways-item-button-link"
				href="<?php echo esc_url( 'https://dashboard.stripe.com/settings' ); ?>"
				target="_blank"
				rel="noopener noreferrer"
			>
				<?php esc_html_e( 'Edit Your Stripe Settings', 'event-tickets' ); ?>
			</a>
		</div>
	</div>
</div>