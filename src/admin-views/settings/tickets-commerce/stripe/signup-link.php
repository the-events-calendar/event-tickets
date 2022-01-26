<div
	class="tec-tickets__admin-settings-tickets-commerce-stripe-signup-settings"
>
	<div class="tec-tickets__admin-settings-tickets-commerce-stripe-connect-button">
		<a
			target="_blank"
			data-stripe-onboard-complete="tecTicketsCommerceGatewayStripeSignupCallback"
			href="<?php echo esc_url( $url ) ?>"
			data-stripe-button="true"
			id="connect_to_stripe"
			class="tec-tickets__admin-settings-tickets-commerce-stripe-connect-button-link"
		>
			<?php echo wp_kses( __( 'Connect with <i>Stripe</i>', 'event-tickets' ), 'post' ); ?>
		</a>
	</div>

	<div class="tec-tickets__admin-settings-tickets-commerce-stripe-connect-button">
		<a
				target="_blank"
				data-stripe-onboard-complete="tecTicketsCommerceGatewayStripeDisconnectCallback"
				href="<?php echo esc_url( $disconnect_url ) ?>"
				data-stripe-button="true"
				id="disconnect_from_stripe"
				class="tec-tickets__admin-settings-tickets-commerce-stripe-disconnect-button-link"
		>
			<?php echo wp_kses( __( 'Disconnect from <i>Stripe</i>', 'event-tickets' ), 'post' ); ?>
		</a>
	</div>
</div>