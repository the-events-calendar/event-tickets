<?php return '
<h2 class="tec-tickets__admin-settings-tickets-commerce-paypal-title">
	Accept online payments with PayPal!</h2>

<div class="tec-tickets__admin-settings-tickets-commerce-paypal-description">
	<p>
		Start selling tickets to your events today with PayPal. Attendees can purchase tickets directly on your site using debit or credit cards with no additional fees.	</p>

	<script>
	function onboardedCallback( authCode, sharedId ) {
		fetch( \'https://wordpress.test/index.php?rest_route=/tribe/tickets/v1/commerce/paypal/on-boarding\', {
			method: \'POST\',
			headers: {
				\'content-type\': \'application/json\',
			},
			body: JSON.stringify( {
				auth_code: authCode,
				shared_id: sharedId,
				nonce: \'THE_PAYPAL_NONCE\',
			} ),
		} );
	}
</script>

<div class="tec-tickets__admin-settings-tickets-commerce-paypal-connect-button">
	<a
		target="_blank"
		data-paypal-onboard-complete="onboardedCallback"
		href="http://thepaypalsandboxlink.tec.com/hash&displayMode=minibrowser"
		data-paypal-button="true"
		id="connect_to_paypal"
		class="tec-tickets__admin-settings-tickets-commerce-paypal-connect-button-link"
	>
		Connect Automatically with <i>PayPal</i>	</a>
</div>
</div>
';
