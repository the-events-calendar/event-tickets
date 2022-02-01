<div
	class="tec-tickets__admin-settings-tickets-commerce-stripe-signup-settings"
>
	<div class="tec-tickets__admin-settings-tickets-commerce-stripe-connect-button">
		<a
			data-stripe-onboard-complete="tecTicketsCommerceGatewayStripeSignupCallback"
			href="<?php echo esc_url( $url ) ?>"
			data-stripe-button="true"
			id="connect_to_stripe"
			class="tec-tickets__admin-settings-tickets-commerce-stripe-connect-button-link"
		>
			<?php echo wp_kses( __( 'Connect with <i>Stripe</i>', 'event-tickets' ), 'post' ); ?>
		</a>
	</div>
</div>