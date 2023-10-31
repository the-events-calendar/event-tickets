<?php
/**
 * Event Tickets Plus App banner section.
 *
 * @since 5.6.2
 *
 * @var string $qr_src     URL for the connection QR code image.
 * @var string $site_url   The website URL.
 * @var string $api_key    The API key string.
 * @var string $nonce      The nonce that will be used for regenerating the connector key.
 * @var string $action_key The AJAX action key that will be used to regenerate the connector key.
 *
 */

?>
<div class="tec-tickets__admin-banner tec-tickets__admin-etp-app-settings-banner">
	<h3 class="heading"><?php esc_html_e( 'Connect Your Event Tickets Plus App', 'event-tickets-plus' ); ?></h3>
	<p class="tec-tickets__admin-etp-app-settings-banner__help-text"><?php esc_html_e( 'Scan the QR to connect with your tickets or manually enter the API key in your app settings.', 'event-tickets-plus' ); ?></p>

	<div class="tec-tickets__admin-etp-app-settings-connection-wrapper">
		<div class="tec-tickets__admin-etp-app-settings-qr-code">
			<img id="connection_qr_code" alt="qr_code_image" src="<?php echo esc_attr( $qr_src ); ?>" data-tickets-qr-connector-image>
		</div>
		<div class="tec-tickets__admin-etp-app-settings-manual-options">
			<label class="tec-tickets__admin-etp-app-settings-site-url-label"><?php esc_html_e( 'Website URL', 'event-tickets-plus' ) ?></label>
			<p class="tec-tickets__admin-etp-app-settings-site-url"><?php echo esc_url( $site_url ) ?></p>
			<label class="tec-tickets__admin-etp-app-settings-api-key-label"><?php esc_html_e( 'API Key', 'event-tickets-plus' ); ?></label>
			<input
				class="tec-tickets__admin-etp-app-settings-api-key"
				type="text"
				value="<?php echo esc_attr( $api_key ) ?>"
				name="tickets-plus-qr-options-api-key"
				disabled
				data-tickets-qr-connector-input
			/>
			<a
				class="tec-tickets__admin-etp-app-settings-generate-api-key"
				data-tickets-qr-connector
				data-tickets-qr-connector-nonce="<?php echo esc_attr( $nonce ) ?>"
				data-tickets-qr-connector-action="<?php echo esc_attr( $action_key ) ?>"
				data-tickets-qr-connector-container=".tec-tickets__admin-etp-app-settings-banner"
			>
				<?php esc_html_e( 'Refresh API Key', 'event-tickets-plus' ); ?>
			</a>
		</div>
	</div>
	<div
		id="tec-tickets__admin-etp-app-settings-confirmation-text"
		data-tickets-qr-connector-message
		class="tribe-common-a11y-hidden"
	>
		<?php esc_html_e( 'Refreshing the API key will disconnect existing users until they add the new key in their app settings.', 'event-tickets-plus' ) ?>
	</div>
</div>
