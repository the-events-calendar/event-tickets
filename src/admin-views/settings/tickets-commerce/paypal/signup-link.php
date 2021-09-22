<script>
	function onboardedCallback( authCode, sharedId ) {
		fetch( '<?php echo tribe( \TEC\Tickets\Commerce\Gateways\PayPal\REST\On_Boarding_Endpoint::class )->get_route_url() ?>', {
			method: 'POST',
			headers: {
				'content-type': 'application/json',
			},
			body: JSON.stringify( {
				auth_code: authCode,
				shared_id: sharedId,
				nonce: '<?php echo wp_create_nonce( 'tec-tc-on-boarded' ); ?>',
			} ),
		} );
	}
</script>

<div class="tec-tickets__admin-settings-tickets-commerce-paypal-connect-button">
	<a
		target="_blank"
		data-paypal-onboard-complete="onboardedCallback"
		href="<?php echo esc_url( $url ) ?>&displayMode=minibrowser"
		data-paypal-button="true"
		id="connect_to_paypal"
		class="tec-tickets__admin-settings-tickets-commerce-paypal-connect-button-link"
	>
		<?php echo wp_kses( __( 'Connect Automatically with <i>PayPal</i>', 'event-tickets' ), 'post' ); ?>
	</a>
</div>
