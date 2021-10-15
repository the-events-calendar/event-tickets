<?php
/**
 * The Template for displaying the Tickets Commerce PayPal connection details.
 *
 * @version 5.1.10
 *
 * @since 5.1.10
 *
 * @var Tribe__Tickets__Admin__Views                  $this               [Global] Template object.
 * @var string                                        $plugin_url         [Global] The plugin URL.
 * @var TEC\Tickets\Commerce\Gateways\PayPal\Merchant $merchant           [Global] The merchant class.
 * @var TEC\Tickets\Commerce\Gateways\PayPal\Signup   $signup             [Global] The Signup class.
 * @var bool                                          $is_merchant_active [Global] Whether the merchant is active or not.
 */

if ( empty( $is_merchant_active ) ) {
	return;
}

?>
<div class="tec-tickets__admin-settings-tickets-commerce-paypal-connected-row">
	<div class="tec-tickets__admin-settings-tickets-commerce-paypal-connected-col1">
		<?php esc_html_e( 'Webhooks:', 'event-tickets' ); ?>
	</div>
	<div class="tec-tickets__admin-settings-tickets-commerce-paypal-connected-col2">
		<?php
		use TEC\Tickets\Commerce\Gateways\PayPal\Webhooks;
		use TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Events;

		$webhooks_events = tribe( Events::class );

		$event_types = tribe( Webhooks::class )->get_settings()['event_types'];

		foreach ( $event_types as $key => $event ) {
			$webhook_name = $webhooks_events->convert_to_commerce_status( $event['name'] )->get_nicename();
			$is_valid     = $webhooks_events->is_valid( $event['name'] );
			$classes      = [
				'tec-tickets__admin-settings-tickets-commerce-paypal-connected-webhook',
				'tec-tickets__admin-settings-tickets-commerce-paypal-connected-webhook--active' => ! empty( $is_valid ),
			]
			?>
			<div <?php tribe_classes( $classes ); ?>>
				<span class="tec-tickets__admin-settings-tickets-commerce-paypal-connected-webhook-name">
					<?php echo esc_html( $webhook_name ); ?>
				</span>
				<span class="tec-tickets__admin-settings-tickets-commerce-paypal-connected-webhook-error">
					<?php esc_html_e( 'payment connection error', 'event-tickets' ); ?>
				</span>
			</div>

			<?php
		}

	?>
	</div>
</div>
