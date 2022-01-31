<div
	class="tec-tickets__admin-settings-tickets-commerce-gateway-signup-settings"
>
	<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connect-button">
		<a
			data-gateway-onboard-complete="tecTicketsCommerceGatewayStripeSignupCallback"
			href="<?php echo esc_url( $url ) ?>"
			data-gateway-button="true"
			id="connect_to_stripe"
			class="tec-tickets__admin-settings-tickets-commerce-gateway-connect-button-link"
		>
			<?php echo wp_kses( __( 'Connect with <i>Stripe</i>', 'event-tickets' ), 'post' ); ?>
		</a>
	</div>
</div>