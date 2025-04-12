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
 */

if ( ! $is_connected ) {
	return;
}

// Fetch fresh merchant data to ensure we have the latest information
$merchant_data = $merchant->fetch_merchant_data();

// Get merchant details - preferring data from the API if available
$merchant_name = $merchant->get_merchant_name();
$merchant_email = $merchant->get_merchant_email();
$merchant_currency = $merchant->get_merchant_currency();

$test_mode = TEC\Tickets\Commerce\Gateways\Square\Gateway::is_test_mode();
?>

<div class="tec-tickets__admin-settings-tickets-commerce-gateway tec-tickets__admin-settings-tickets-commerce-gateway--connected" role="region" aria-labelledby="tec-tickets-commerce-square-settings-heading">
	<h3 id="tec-tickets-commerce-square-settings-heading" class="screen-reader-text"><?php esc_html_e( 'Square Connection Status', 'event-tickets' ); ?></h3>

	<div id="tec-tickets__admin-settings-tickets-commerce-gateway-connect" class="tec-tickets__admin-settings-tickets-commerce-gateway-connect">
		<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connected" aria-labelledby="tec-tickets-commerce-square-connection-details">
			<h4 id="tec-tickets-commerce-square-connection-details" class="screen-reader-text"><?php esc_html_e( 'Connection Details', 'event-tickets' ); ?></h4>

			<!-- Connection Info -->
			<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-row">
				<span class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-label" id="square-connected-to-label">
					<?php esc_html_e( 'Connected to:', 'event-tickets' ); ?>
				</span>
				<span class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-value" aria-labelledby="square-connected-to-label">
					<?php echo esc_html( $merchant_name ?: __( 'Square Account', 'event-tickets' ) ); ?>
					<?php if ( ! empty( $merchant_email ) ) : ?>
					<span class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-subtext" aria-label="<?php esc_attr_e( 'Account email', 'event-tickets' ); ?>">
						<?php echo esc_html( $merchant_email ); ?>
					</span>
					<?php endif; ?>
				</span>
			</div>

			<!-- Square Status -->
			<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-row">
				<span class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-label" id="square-status-label">
					<?php esc_html_e( 'Status:', 'event-tickets' ); ?>
				</span>
				<span class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-value" aria-labelledby="square-status-label">
					<span class="dashicons dashicons-yes" aria-hidden="true"></span>
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
				<span class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-label" id="square-currency-label">
					<?php esc_html_e( 'Currency:', 'event-tickets' ); ?>
				</span>
				<span class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-value" aria-labelledby="square-currency-label">
					<?php echo esc_html( $merchant_currency ); ?>
				</span>
			</div>

			<?php if ( isset( $merchant_data['merchant']['country'] ) ) : ?>
			<!-- Country Info -->
			<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-row">
				<span class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-label" id="square-country-label">
					<?php esc_html_e( 'Country:', 'event-tickets' ); ?>
				</span>
				<span class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-value" aria-labelledby="square-country-label">
					<?php echo esc_html( $merchant_data['merchant']['country'] ); ?>
				</span>
			</div>
			<?php endif; ?>

			<!-- Disconnect Button -->
			<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-row">
				<span class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-label" aria-hidden="true"></span>
				<span class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-value">
					<a
						href="#"
						class="tec-tickets__admin-settings-tickets-commerce-gateway-disconnect-square-button"
						id="tec-tickets__admin-settings-tickets-commerce-gateway-disconnect-square"
						data-nonce="<?php echo esc_attr( $disconnect_nonce ); ?>"
						aria-label="<?php esc_attr_e( 'Disconnect from Square payment gateway', 'event-tickets' ); ?>"
					>
						<?php esc_html_e( 'Disconnect from Square', 'event-tickets' ); ?>
					</a>
				</span>
			</div>

			<?php $this->template( 'settings/tickets-commerce/square/connect/help-links' ); ?>
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
