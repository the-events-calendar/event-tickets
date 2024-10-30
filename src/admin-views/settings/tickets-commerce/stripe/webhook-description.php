<?php
/**
 * Template to display the Webhook's Setting Description.
 *
 * @since 5.11.0
 *
 * @var Tribe__Tickets__Admin__Views $this     Template object.
 */

use TEC\Tickets\Commerce\Gateways\Stripe\Webhooks;

$webhooks = tribe( Webhooks::class );
?>

<p class="tec-tickets__admin-settings-tickets-commerce-gateway-group-description-stripe-webhooks contained">
	<?php
	printf(
		// Translators: %1$s A link to the KB article. %2$s closing `</a>` link.
		esc_html__( 'Setting up webhooks will enable you to receive notifications on charge statuses and keep order information up to date for asynchronous payments. %1$sLearn more%2$s', 'event-tickets' ),
		'<a target="_blank" rel="noopener noreferrer" href="https://evnt.is/1b3p">',
		'</a>'
	);
	?>
</p>
<?php if ( ! $webhooks->has_valid_signing_secret() ) : ?>
	<p class="tec-tickets__admin-settings-tickets-commerce-gateway-group-description-stripe-webhooks contained">
		<?php
		if ( tec_tickets_commerce_is_sandbox_mode() ) {
			printf(
				// Translators: %1$s A link to Stripe's API Webhook Documentation, %2$s closing `</a>` link, %3$s Opening <strong> tag, %4$s Closing </strong< tag.
				esc_html__( 'We can set up your %1$sWebhook automatically%2$s %3$sonly on Production mode%4$s! Please switch your Tickets Commerce to Production if you would like us to set up your webhook.', 'event-tickets' ),
				'<a target="_blank" rel="noopener noreferrer" href="https://docs.stripe.com/api/webhook_endpoints">',
				'</a>',
				'<strong>',
				'</strong>'
			);
		} else {
			$url = add_query_arg(
				[
					'action'   => Webhooks::NONCE_KEY_SETUP,
					'tc_nonce' => wp_create_nonce( Webhooks::NONCE_KEY_SETUP ),
				],
				admin_url( '/admin-ajax.php' )
			);
			printf(
				// Translators: %1$s A link to Stripe's API Webhook Documentation, %2$s closing `</a>` link, %3$s A link to the automatic webhook setup endpoint..
				esc_html__( 'We can set up your %1$sWebhook automatically%2$s! Save your unsaved changes and then just click %3$shere%2$s!', 'event-tickets' ),
				'<a target="_blank" rel="noopener noreferrer" href="https://docs.stripe.com/api/webhook_endpoints">',
				'</a>',
				'<a id="tec-tickets__admin-settings-webhook-set-up" data-loading-text="' . esc_attr__( 'Setting up your webhook!', 'event-tickets' ) . '" rel="noopener noreferrer" href="' . esc_url( $url ) . '">'
			);
		}
		?>
	</p>
<?php endif; ?>
<div class="clear"></div>
<?php
