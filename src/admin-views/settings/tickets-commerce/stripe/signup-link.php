<?php
/**
 * Template to display the Stripe signup link.
 *
 * @since 5.3.0
 *
 * @var Tribe__Tickets__Admin__Views $this     Template object.
 * @var array                        $gateways Array of gateway objects.
 */

?>
<div
	class="tec-tickets__admin-settings-tickets-commerce-gateway-signup-settings"
>
	<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connect-button">
		<a
			data-gateway-onboard-complete="tecTicketsCommerceGatewayStripeSignupCallback"
			href="<?php echo esc_url( $url ); ?>"
			data-gateway-button="true"
			id="connect_to_stripe"
			class="tec-tickets__admin-settings-tickets-commerce-gateway-connect-button-link"
		>
			<?php echo wp_kses( __( 'Get Connected with <i>Stripe</i>', 'event-tickets' ), [ 'i'=> [], 'em' => [], 'strong' => [] ] ); ?>
		</a>
	</div>
</div>
