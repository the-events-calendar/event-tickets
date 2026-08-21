<?php
/**
 * The Template for displaying the Tickets Commerce PayPal connect button.
 *
 * @version TBD
 *
 * @since 5.1.9
 * @since TBD Render an error notice when no signup URL could be obtained.
 *
 * @var Tribe__Template $this         Template object.
 * @var string|false    $url          The PayPal signup URL, false when it could not be generated.
 * @var string          $country_code The country code the signup URL was generated for.
 */

defined( 'ABSPATH' ) || exit;

$countries             = tribe( \TEC\Tickets\Commerce\Gateways\PayPal\Location\Country::class )->get_list();
$default_country_code  = \TEC\Tickets\Commerce\Gateways\PayPal\Location\Country::DEFAULT_COUNTRY_CODE;
$selected_country_code = $country_code;
if ( empty( $selected_country_code ) ) {
	$selected_country_code = $default_country_code;
}
?>
<div
	class="tec-tickets__admin-settings-tickets-commerce-gateway-signup-settings"
>
	<p
		class="tec-tickets__admin-settings-tickets-commerce-gateway-merchant-country-container"
	>
		<select
			name='tec-tickets-commerce-gateway-paypal-merchant-country'
			class="tribe-dropdown"
			data-prevent-clear
			data-dropdown-css-width="false"
			style="width: 100%; max-width: 340px;"
			data-placeholder="<?php esc_attr_e( 'Select your country of operation', 'event-tickets' ); ?>"
		>
			<?php foreach ( $countries as $code => $label ) : ?>
				<option
					value="<?php echo esc_attr( $code ); ?>"
					<?php selected( $code === $selected_country_code ); ?>
				>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</p>

	<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connect-button">
		<?php if ( empty( $url ) ) : ?>
			<div class="event-tickets">
				<div class="tribe-tickets__notice tribe-tickets__notice--error tec-tickets__admin-settings-tickets-commerce-gateway-modal-notice-error">
					<div class="tribe-common-b2 tribe-tickets-notice__content">
						<h4 class="tribe-tickets-notice__title">
							<?php esc_html_e( 'PayPal connection unavailable', 'event-tickets' ); ?>
						</h4>
						<div class="tribe-tickets-notice__message">
							<?php esc_html_e( 'We could not get a connection link from PayPal. Reload this page to try again. If this keeps happening, make sure your site can reach whodat.theeventscalendar.com.', 'event-tickets' ); ?>
						</div>
					</div>
				</div>
			</div>
		<?php else : ?>
			<a
				target="_blank"
				data-paypal-onboard-complete="tecTicketsCommerceGatewayPayPalSignupCallback"
				href="<?php echo esc_url( add_query_arg( 'displayMode', 'minibrowser', $url ) ); ?>"
				data-paypal-button="true"
				id="connect_to_paypal"
				class="tec-tickets__admin-settings-tickets-commerce-gateway-connect-button-link"
			>
				<?php echo wp_kses( __( 'Connect Automatically with <i>PayPal</i>', 'event-tickets' ), 'post' ); ?>
			</a>
		<?php endif; ?>
	</div>
</div>
<?php
