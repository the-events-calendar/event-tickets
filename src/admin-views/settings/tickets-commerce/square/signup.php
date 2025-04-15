<?php
/**
 * The Template for displaying the Tickets Commerce Square Settings when inactive (not connected).
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

defined( 'ABSPATH' ) || exit;

if ( $is_connected ) {
	return;
}

// Determine if the site is using SSL.
$is_ssl = is_ssl();

// We'll use JavaScript to handle the redirect instead of getting the URL here.
$connect_url = '#';
?>

<div class="tec-tickets__admin-settings-tickets-commerce-gateway">
	<div id="tec-tickets__admin-settings-tickets-commerce-gateway-connect" class="tec-tickets__admin-settings-tickets-commerce-gateway-connect">
		<h2 class="tec-tickets__admin-settings-tickets-commerce-gateway-title">
			<?php esc_html_e( 'Accept online payments with Square!', 'event-tickets' ); ?>
		</h2>
		<div class="tec-tickets__admin-settings-tickets-commerce-gateway-description">
			<p class="tec-tickets__admin-settings-tickets-commerce-gateway-description-text">
				<?php echo wp_kses( __( 'Start selling tickets to your events today with Square integration for Tickets Commerce. Enable credit card payments, Apple Pay, Google Pay, and more.<br>', 'event-tickets' ), [ 'br' => [] ] ); ?>
			</p>
			<?php if ( $is_ssl ) : ?>
				<div class="tec-tickets__admin-settings-tickets-commerce-gateway-signup-links">
					<?php $this->template( 'settings/tickets-commerce/square/connect/sandbox-notice' ); ?>
					<a
						href="<?php echo esc_url( $connect_url ); ?>"
						class="tec-tickets__admin-settings-tickets-commerce-gateway-connect-button-link tec-tickets__admin-settings-tickets-commerce-gateway-connect-square-button"
						id="tec-tickets__admin-settings-tickets-commerce-gateway-connect-square"
					>
						<?php esc_html_e( 'Connect with Square', 'event-tickets' ); ?>
					</a>
				</div>
			<?php else : ?>
				<div class="tec-tickets__admin-settings-tickets-commerce-gateway-non-ssl-notice">
					<?php echo wp_kses( __( '<strong>SSL Certificate Required</strong> - to connect Square and use credit card payments, you need to have an SSL certificate, and your site needs to be using HTTPS.', 'event-tickets' ), [ 'strong' => [] ] ); ?>
				</div>
			<?php endif; ?>
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
<?php
