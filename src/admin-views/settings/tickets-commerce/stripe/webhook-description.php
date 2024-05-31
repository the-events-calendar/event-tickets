<?php
/**
 * Template to display the Webhook's Setting Description.
 *
 * @since TBD
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
		$url = add_query_arg(
			[
				'action'   => Webhooks::NONCE_KEY_SETUP,
				'tc_nonce' => wp_create_nonce( Webhooks::NONCE_KEY_SETUP ),
			],
			admin_url( '/admin-ajax.php' )
		);
		printf(
			// Translators: %1$s A link to the automatic webhook setup endpoint. %2$s closing `</a>` link.
			esc_html__( 'We can set up your Webhook automatically! Save your unsaved changes and then just click %1$shere%2$s!', 'event-tickets' ),
			'<a id="tec-tickets__admin-settings-webhook-set-up" data-loading-text="' . esc_attr__( 'Setting up your webhook!', 'event-tickets' ) . '" rel="noopener noreferrer" href="' . esc_url( $url ) . '">',
			'</a>'
		);
		?>
	</p>
<?php endif; ?>
<div class="clear"></div>
<?php
