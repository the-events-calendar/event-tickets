<?php
/**
 * Account errors list for the Tickets Commerce > PayPal Commerce gateway settings.
 *
 * @since 5.1.6
 *
 * @var bool   $account_is_connected [Global] Whether the account is connected.
 * @var string $merchant_id          [Global] The merchant ID (if there is any).
 * @var string $formatted_errors     [Global] The formatted account errors.
 * @var string $guidance_html        [Global] The guidance HTML used when showing errors.
 */

// Only output if we have account errors to show.
if ( empty( $formatted_errors ) ) {
	return;
}

?>
<div>
	<p class="error-message"><?php esc_html_e( 'Warning, your account is not ready to accept payments.', 'event-tickets' ); ?></p>
	<p>
		<?php esc_html_e( 'There is an issue with your PayPal account that is preventing you from being able to accept payments.', 'event-tickets' ); ?>

		<?php
		// phpcs:ignore
		echo $guidance_html;
		?>
	</p>
	<div class="paypal-message-template">
		<p><?php esc_html_e( 'Greetings!', 'event-tickets' ); ?></p>
		<p><?php esc_html_e( "I am trying to connect my PayPal account to the Event Tickets plugin for WordPress. I have gone through the onboarding process to connect my account, but when I finish I'm given the following message from Event Tickets:", 'event-tickets' ); ?></p>

		<?php
		// phpcs:ignore
		echo $formatted_errors;
		?>

		<p><?php esc_html_e( 'Please help me resolve these account errors so I can begin accepting payments via PayPal on Event Tickets.', 'event-tickets' ); ?></p>
	</div>

	<?php if ( $account_is_connected ) : ?>
		<p>
			<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=tribe_events&page=tribe-common&tab=event-tickets&paypalStatusCheck' ) ); ?>">
				<?php esc_html_e( 'Re-Check Account Status', 'event-tickets' ); ?>
			</a>
		</p>
	<?php endif; ?>
</div>
