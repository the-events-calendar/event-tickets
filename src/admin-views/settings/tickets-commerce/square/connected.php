<?php
/**
 * The Template for displaying the Tickets Commerce Square Settings when connected.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Admin__Views                  $this              [Global] Template object.
 * @var TEC\Tickets\Commerce\Gateways\Square\Merchant $merchant          [Global] The merchant class.
 * @var TEC\Tickets\Commerce\Gateways\Square\Gateway  $gateway           [Global] The gateway class.
 * @var string                                        $settings_url      [Global] The URL to the settings page.
 * @var string                                        $disconnect_nonce  [Global] The nonce for disconnecting.
 * @var bool                                          $is_connected      [Global] Whether Square is connected.
 * @var string                                        $merchant_name     [Global] The merchant name.
 * @var string                                        $merchant_email    [Global] The merchant email.
 * @var string                                        $merchant_id       [Global] The merchant ID.
 * @var string                                        $merchant_currency [Global] The merchant currency.
 */

if ( ! $is_connected ) {
	return;
}

$test_mode = TEC\Tickets\Commerce\Gateways\Square\Gateway::is_test_mode();
?>

<div class="tec-tickets__admin-settings-tickets-commerce-gateway tec-tickets__admin-settings-tickets-commerce-gateway--connected">
	<div id="tec-tickets__admin-settings-tickets-commerce-gateway-connect" class="tec-tickets__admin-settings-tickets-commerce-gateway-connect">
		<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connected">
			<!-- Connection Info -->
			<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-row">
				<span class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-label">
					<?php esc_html_e( 'Connected to:', 'event-tickets' ); ?>
				</span>
				<span class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-value">
					<?php echo esc_html( $merchant_name ); ?>
					<span class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-subtext">
						<?php echo esc_html( $merchant_email ); ?>
					</span>
				</span>
			</div>

			<!-- Square Status -->
			<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-row">
				<span class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-label">
					<?php esc_html_e( 'Status:', 'event-tickets' ); ?>
				</span>
				<span class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-value">
					<span class="dashicons dashicons-yes"></span>
					<?php esc_html_e( 'Connected', 'event-tickets' ); ?>
					<?php if ( $test_mode ) : ?>
						<span class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-subtext">
							<?php esc_html_e( '(Test Mode)', 'event-tickets' ); ?>
						</span>
					<?php endif; ?>
				</span>
			</div>

			<!-- Currency Info -->
			<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-row">
				<span class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-label">
					<?php esc_html_e( 'Currency:', 'event-tickets' ); ?>
				</span>
				<span class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-value">
					<?php echo esc_html( $merchant_currency ); ?>
				</span>
			</div>

			<!-- Disconnect Button -->
			<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-row">
				<span class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-label"></span>
				<span class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-value">
					<a
						href="#"
						class="tec-tickets__admin-settings-tickets-commerce-gateway-disconnect-square-button"
						id="tec-tickets__admin-settings-tickets-commerce-gateway-disconnect-square"
						data-nonce="<?php echo esc_attr( $disconnect_nonce ); ?>"
					>
						<?php esc_html_e( 'Disconnect from Square', 'event-tickets' ); ?>
					</a>
				</span>
			</div>

			<!-- Help Links -->
			<div class="tec-tickets__admin-settings-tickets-commerce-gateway-help-links">
				<a href="https://evnt.is/1axt" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Learn more about Square', 'event-tickets' ); ?>
				</a>
				<a href="https://evnt.is/1axu" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Square requirements', 'event-tickets' ); ?>
				</a>
				<a href="https://evnt.is/1axv" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'About online payments', 'event-tickets' ); ?>
				</a>
			</div>
		</div>
	</div>

	<div class="tec-tickets__admin-settings-tickets-commerce-gateway-logo">
		<img
			src="<?php echo esc_url( $gateway->get_logo_url() ); ?>"
			alt="<?php esc_attr_e( 'Square logo', 'event-tickets' ); ?>"
			class="tec-tickets__admin-settings-tickets-commerce-gateway-logo-square"
			style="max-width: 300px;"
		/>
	</div>
</div>
