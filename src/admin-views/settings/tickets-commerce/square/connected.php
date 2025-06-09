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

// Fetch fresh merchant data to ensure we have the latest information.
$merchant_data = $merchant->fetch_merchant_data();

// Get merchant details - preferring data from the API if available.
$merchant_name     = $merchant->get_merchant_name();
$merchant_email    = $merchant->get_merchant_email();
$merchant_currency = $merchant->get_merchant_currency();

// Verify merchant has all required scopes for Square integration.
$scope_verification = tribe( \TEC\Tickets\Commerce\Gateways\Square\WhoDat::class )->verify_merchant_scopes();
$has_missing_scopes = ! empty( $scope_verification['missing_scopes'] );

$test_mode = TEC\Tickets\Commerce\Gateways\Square\Gateway::is_test_mode();
?>

<div
	class="tec-tickets__admin-settings-tickets-commerce-gateway tec-tickets__admin-settings-tickets-commerce-gateway--connected"
	id="tec-tickets__admin-settings-tickets-commerce-gateway-square-container"
	data-connect="<?php echo esc_attr__( 'Connect with Square', 'event-tickets' ); ?>"
	data-connecting="<?php echo esc_attr__( 'Connecting...', 'event-tickets' ); ?>"
	data-disconnecting="<?php echo esc_attr__( 'Disconnecting...', 'event-tickets' ); ?>"
	data-reconnect="<?php echo esc_attr__( 'Reconnect Account', 'event-tickets' ); ?>"
	data-connect-error="<?php echo esc_attr__( 'There was an error connecting to Square. Please try again.', 'event-tickets' ); ?>"
	data-disconnect-confirm="<?php echo esc_attr__( 'Are you sure you want to disconnect from Square?', 'event-tickets' ); ?>"
	data-disconnect-error="<?php echo esc_attr__( 'There was an error disconnecting from Square. Please try again.', 'event-tickets' ); ?>"
	data-connect-nonce="<?php echo esc_attr( wp_create_nonce( 'square-connect' ) ); ?>"
	role="region"
	aria-labelledby="tec-tickets-commerce-square-settings-heading"
>
	<h3 id="tec-tickets-commerce-square-settings-heading" class="screen-reader-text"><?php esc_html_e( 'Square Connection Status', 'event-tickets' ); ?></h3>
	<div id="tec-tickets__admin-settings-tickets-commerce-gateway-connect" class="tec-tickets__admin-settings-tickets-commerce-gateway-connect">
		<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connected" aria-labelledby="tec-tickets-commerce-square-connection-details">
			<h4 id="tec-tickets-commerce-square-connection-details" class="screen-reader-text"><?php esc_html_e( 'Connection Details', 'event-tickets' ); ?></h4>
			<!-- Connection Info -->
			<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-row">
				<span class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-label" id="square-connected-to-label">
					<?php esc_html_e( 'Connection:', 'event-tickets' ); ?>
				</span>
				<span class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-value" aria-labelledby="square-connected-to-label">
					<span class="dashicons dashicons-yes" aria-hidden="true"></span>
					<?php esc_html_e( 'Connected', 'event-tickets' ); ?>
					<?php $this->template( 'settings/tickets-commerce/square/connect/disconnect' ); ?>
				</span>
			</div>
			<!-- Square Status -->
			<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-row">
				<span class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-label" id="square-status-label">
					<?php esc_html_e( 'Account:', 'event-tickets' ); ?>
				</span>
				<span class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-value" aria-labelledby="square-status-label">
					<?php echo esc_html( $merchant_name ?: __( 'Square Account', 'event-tickets' ) ); ?>
					<?php if ( $test_mode ) : ?>
						<span class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-subtext">
							<?php esc_html_e( '(Test Mode)', 'event-tickets' ); ?>
						</span>
					<?php endif; ?>
				</span>
			</div>
			<?php $this->template( 'settings/tickets-commerce/square/connect/webhook-status' ); ?>
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
			<?php $this->template( 'settings/tickets-commerce/square/connect/missing-scopes' ); ?>

			<?php $this->template( 'settings/tickets-commerce/square/connect/help-links' ); ?>
		</div>
	</div>
	<div class="tec-tickets__admin-settings-tickets-commerce-gateway-logo">
		<img
			src="<?php echo esc_url( $gateway->get_logo_url() ); ?>"
			alt="<?php esc_attr_e( 'Square logo', 'event-tickets' ); ?>"
			class="tec-tickets__admin-settings-tickets-commerce-gateway-logo-image"
			style="max-width: 300px;"
		/>

		<ul>
			<li><?php esc_html_e( 'Credit cards, digital wallets, and contactless payments', 'event-tickets' ); ?></li>
			<li><?php esc_html_e( 'Quick, secure checkout experience', 'event-tickets' ); ?></li>
			<li><?php esc_html_e( 'Accept payments from around the world', 'event-tickets' ); ?></li>
			<li><?php esc_html_e( 'Seamless integration with Square POS', 'event-tickets' ); ?></li>
		</ul>

	</div>
</div>
