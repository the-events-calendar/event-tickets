<?php
/**
 * The Template for displaying the Tickets Commerce Stripe connection details.
 *
 * @since 5.3.0
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

$name           = $merchant->get_client_id();
$disconnect_url = $signup->generate_disconnect_url();

?>
<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-row">
	<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-col1">
		<?php esc_html_e( 'Connected as:', 'event-tickets' ); ?>
	</div>
	<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-col2">
		<span class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-text-name">
			<?php echo esc_html( $name ); ?>
		</span>
		<a
			href="<?php echo esc_url( $disconnect_url ); ?>"
			class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-text-disconnect-link"
		>
			<?php esc_html_e( 'Disconnect', 'event-tickets' ); ?>
		</a>
	</div>
</div>
