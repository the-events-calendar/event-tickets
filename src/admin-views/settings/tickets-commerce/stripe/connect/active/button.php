<?php
/**
 * The Template for displaying the Tickets Commerce Stripe connection details.
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

if ( false === $merchant_status['connected'] ) {
	return;
}

?>
<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-row">
	<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-col1"></div>
	<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-col2">
        <div class="tec-tickets__admin-settings-tickets-commerce-gateways-item-button">
            
        </div>
		<a
            class="tec-tickets__admin-settings-tickets-commerce-gateways-item-button-link"
            href="<?php echo esc_attr( 'https://dashboard.stripe.com/' ); ?>"
            target="_blank"
		>
			<?php esc_html_e( 'Edit Your Stripe Settings', 'event-tickets' ); ?>
		</a>
	</div>
</div>