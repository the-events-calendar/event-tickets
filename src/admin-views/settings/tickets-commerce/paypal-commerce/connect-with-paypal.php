<?php
/**
 * Connect with PayPal section for the Tickets Commerce > PayPal Commerce gateway settings.
 *
 * @since 5.1.6
 *
 * @var bool   $account_is_connected [Global] Whether the account is connected.
 * @var string $merchant_id          [Global] The merchant ID (if there is any).
 * @var string $formatted_errors     [Global] The formatted account errors.
 * @var string $guidance_html        [Global] The guidance HTML used when showing errors.
 */

// @todo Replace font awesome icon usages.

?>

<script type="text/javascript">
	function tribeTicketsAdminCommerceSettingsOnBoardCallback( authCode, sharedId ) {
		tribe.tickets.admin.commerceSettings.onBoardCallback( authCode, sharedId );
	}
</script>
<div id="give-paypal-commerce-account-manager-field-wrap" class="tribe-common">
	<div class="connect-button-wrap">
		<div class="button-wrap connection-setting <?php echo $account_is_connected ? 'tribe-common-a11y-hidden' : ''; ?>">
			<div>
				<button class="button button-primary button-large" id="js-give-paypal-on-boarding-handler">
					<i class="fab fa-paypal"></i>&nbsp;&nbsp;
					<?php esc_html_e( 'Connect with PayPal', 'event-tickets' ); ?>
				</button>
				<a class="tribe-common-a11y-hidden" target="_blank"
					data-paypal-onboard-complete="tribeTicketsAdminCommerceSettingsOnBoardCallback" href="#"
					data-paypal-button="true">
					<?php esc_html_e( 'Sign up for PayPal', 'event-tickets' ); ?>
				</a>
				<span class="tooltip">
					<span class="left-arrow"></span>
					<?php esc_html_e( 'Click to get started!', 'event-tickets' ); ?>
				</span>
			</div>
			<span class="give-field-description">
				<i class="fa fa-exclamation"></i>
				<?php esc_html_e( 'PayPal is currently NOT connected.', 'event-tickets' ); ?>
			</span>
		</div>

		<div class="button-wrap disconnection-setting <?php echo ! $account_is_connected ? 'tribe-common-a11y-hidden' : ''; ?>">
			<div>
				<button class="button button-large disabled" disabled="disabled">
					<i class="fab fa-paypal"></i>&nbsp;&nbsp;<?php esc_html_e( 'Connected', 'event-tickets' ); ?>
				</button>
			</div>
			<div>
				<span class="give-field-description">
					<i class="fa fa-check"></i>
					<?php esc_html_e( 'Connected for payments as', 'event-tickets' ); ?>
					<span class="paypal-account-email">
						<?php echo esc_html( $merchant_id ); ?>
					</span>
				</span>
				<span class="actions">
					<a href="#" id="js-give-paypal-disconnect-paypal-account">
						<?php esc_html_e( 'Disconnect', 'event-tickets' ); ?>
					</a>
				</span>
			</div>

			<div class="api-access-feature-list-wrap">
				<p><?php esc_html_e( 'APIs Connected:', 'event-tickets' ); ?></p>
				<ul>
					<li><?php esc_html_e( 'Payments', 'event-tickets' ); ?></li>
					<li><?php esc_html_e( 'Refunds', 'event-tickets' ); ?></li>
				</ul>
			</div>
		</div>

		<?php $this->template( 'settings/tickets-commerce/paypal-commerce/connect-with-paypal/errors' ); ?>
	</div>
</div>
