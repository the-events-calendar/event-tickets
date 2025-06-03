<?php
/**
 * The Template for displaying the Tickets Commerce Square Settings when connected.
 *
 * @since 5.24.0
 *
 * @version 5.24.0
 *
 * @var Tribe__Tickets__Admin__Views                  $this              [Global] Template object.
 * @var TEC\Tickets\Commerce\Gateways\Square\Merchant $merchant          [Global] The merchant class.
 * @var TEC\Tickets\Commerce\Gateways\Square\Gateway  $gateway           [Global] The gateway class.
 * @var string                                        $settings_url      [Global] The URL to the settings page.
 * @var string                                        $disconnect_nonce  [Global] The nonce for disconnecting.
 * @var bool                                          $is_connected      [Global] Whether Square is connected.
 */

defined( 'ABSPATH' ) || exit;

if ( ! $is_connected ) {
	return;
}
?>
<!-- Disconnect Button -->
<button
	type="button"
	class="tec-tickets__admin-settings-tickets-commerce-gateway-disconnect-square-button button button-link-delete"
	id="tec-tickets__admin-settings-tickets-commerce-gateway-disconnect-square"
	data-nonce="<?php echo esc_attr( $disconnect_nonce ); ?>"
	aria-label="<?php esc_attr_e( 'Disconnect from Square payment gateway', 'event-tickets' ); ?>"
>
	<?php esc_html_e( 'Disconnect', 'event-tickets' ); ?>
</button>


<!-- Disconnect Confirmation Dialog -->
<div id="tec-tickets__admin-settings-tickets-commerce-gateway-disconnect-square-dialog" class="tec-tickets__admin-settings-tickets-commerce-gateway-dialog" style="display: none;">
	<div class="tec-tickets__admin-settings-tickets-commerce-gateway-dialog-content">
		<h3><?php esc_html_e( 'Disconnect Square', 'event-tickets' ); ?></h3>
		<p><?php esc_html_e( 'Are you sure you want to disconnect from Square? This will disable payment processing for all tickets.', 'event-tickets' ); ?></p>
		<div class="tec-tickets__admin-settings-tickets-commerce-gateway-dialog-buttons">
			<button type="button" class="button button-secondary tec-tickets__admin-settings-tickets-commerce-gateway-disconnect-cancel">
				<?php esc_html_e( 'Cancel', 'event-tickets' ); ?>
			</button>
			<button type="button" class="button button-primary button-danger tec-tickets__admin-settings-tickets-commerce-gateway-disconnect-confirm">
				<?php esc_html_e( 'Disconnect', 'event-tickets' ); ?>
			</button>
		</div>
	</div>
</div>
