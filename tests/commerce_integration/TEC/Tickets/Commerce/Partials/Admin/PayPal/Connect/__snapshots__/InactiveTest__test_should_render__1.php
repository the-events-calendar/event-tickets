<?php return '
<h2 class="tec-tickets__admin-settings-tickets-commerce-paypal-title">
	Accept online payments with PayPal!</h2>

<div class="tec-tickets__admin-settings-tickets-commerce-paypal-description">
	<p>
		Start selling tickets to your events today with PayPal. Attendees can purchase tickets directly on your site using debt or credit cards with no additional fees.	</p>

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
				nonce: \'a9268542af\',
			} ),
		} );
	}
</script>

<div class="tec-tickets-commerce-connect-paypal-button">
	<a
		target="_blank"
		data-paypal-onboard-complete="onboardedCallback"
		href="https://www.sandbox.paypal.com/us/merchantsignup/partner/onboardingentry?token=YzlmMDdlYjAtODgwZi00N2VmLWE5NzctZDhmMjM4MzViNjk3NUdjWGF5dmI5MHJlNnkxUC9GVDhQSVV5WUxIaUlyYk5jMkhsMVpUU0lUdz12Mg==&displayMode=minibrowser"
		data-paypal-button="true"
		id="connect_to_paypal"
	>
		Connect Automatically with <i>PayPal</i>	</a>
</div></div>
';
