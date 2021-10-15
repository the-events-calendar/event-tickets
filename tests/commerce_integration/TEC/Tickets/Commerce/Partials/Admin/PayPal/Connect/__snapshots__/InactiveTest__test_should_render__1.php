<?php return '
<h2 class="tec-tickets__admin-settings-tickets-commerce-paypal-title">
	Accept online payments with PayPal!</h2>

<div class="tec-tickets__admin-settings-tickets-commerce-paypal-description">
	<p>
		Start selling tickets to your events today with PayPal. Attendees can purchase tickets directly on your site using debit or credit cards with no additional fees.	</p>

	<div class="tec-tickets__admin-settings-tickets-commerce-paypal-signup-links">
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

<div
	class="tec-tickets__admin-settings-tickets-commerce-paypal-signup-settings"
>
	<div class="tec-tickets__admin-settings-tickets-commerce-paypal-connect-button">
		<a
			target="_blank"
			data-paypal-onboard-complete="onboardedCallback"
			href="&displayMode=minibrowser"
			data-paypal-button="true"
			id="connect_to_paypal"
			class="tec-tickets__admin-settings-tickets-commerce-paypal-connect-button-link"
		>
			Connect Automatically with <i>PayPal</i>		</a>
	</div>
</div>
	</div>

	
<div class="tec-tickets__admin-settings-tickets-commerce-paypal-help-links">

	<div class="tec-tickets__admin-settings-tickets-commerce-paypal-help-link">
	<svg  class="tribe-common-c-svgicon"   width="16" height="23" fill="none" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><path fill="url(#a)" d="M0 .682h16v22H0z"/><defs><pattern id="a" patternContentUnits="objectBoundingBox" width="1" height="1"><use xlink:href="#b" transform="scale(.03125 .02273)"/></pattern><image id="b" width="32" height="44" xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAsCAYAAAAEuLqPAAAC6klEQVRYCdVY7XHbMAz1z/6oKYzgESBNkBG6QbNBu0GyQbxBu4G7QUbwCB7BI7j3KICCKYqEYuV69R3P/AAeQBAAIe52/8vvC/EhEP/oaDh11F866m+mYfweaDjuib9tuqevxE8AN8Ks4KX+JdDwi4gPH1aGiCnQ8GYEXwMxQJ8BjHUFl/FTIH7tiM/3PMOL0rn/AWjMfCXiVyuwBQT+QPx7UmQ4ufkz4ReMWwKX1mEts5GzS4nVDEvSZT7b0HuVPNDwImZ7aOe5EKtEIP6Zr8exEEWvfsTsRfDdbkdjNAEfPpUcONEH6qPTwNPT5MYdySE3RMsMGprB/I/sHrucAZuJRSsge41nz3UnMWB5F/7TUgA8mtTuaAPxEQoUTZNLKoxH5/UpPyarKOuYoIpapdV6RyPnbkcVFnMM50SmsY8LJ006Oioc/A7ySGKibeKR2L95QUBnhN/2xN/X8M7kzSYaaFb4mt0r7EyeCcF5glAu+c+EI7HEWkDqgbeMvDgsKRALjBWOhOv4oIlFAF3pm4hZ6Ccn/EgYIp2q5SS9uhxYbkiE/J9kHhMa5TydKKeOAmE3XsuBW0N+5ri64E1GSk9Lt9uka+pNGbefH5exQnNHGsuolBJ6oyM86mtlPgCKg+AoeAkTNycRT6l0iVDmrfCO+sn5Snwk17I4VlEJeH+JtzSXCZ+bvsw01gYlJWAZgJb48rlMOGpCF1/E8VgiF2jHEqbxzGH2YhVkGUp9owRM18ySFsNkzNW8FifFrTc8lVlv2JozK23134TntUpoFuVzDvdE3eMNT7Wru/HWC5pw7tJtVUJjca0CmqY3q7BVAXye74mfW23zEl8VkCwZP148/c0t0BGfANpqelFtroDXCT/NB/65AriE8PTSap92BB7HszSb+wDCEOfbavo8s6UC8eHJW/vhEQKW2EwB/aCUKhhPdrWWXslmhWcj4y4u4ypWs9ozrvXX1IuLgvMFVDQ4hkZDxeSuHf4CwwK1/5LnQpQAAAAASUVORK5CYII="/></defs></svg>	<a
		href="https://evnt.is/1axt"
		target="_blank"
		rel="noopener noreferrer"
		class="tec-tickets__admin-settings-tickets-commerce-paypal-help-link-url"
	>Learn more about configuring PayPal payments</a>
</div>

	<div class="tec-tickets__admin-settings-tickets-commerce-paypal-help-link">
	<svg  class="tribe-common-c-svgicon"   width="16" height="23" fill="none" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><path fill="url(#a)" d="M0 .682h16v22H0z"/><defs><pattern id="a" patternContentUnits="objectBoundingBox" width="1" height="1"><use xlink:href="#b" transform="scale(.03125 .02273)"/></pattern><image id="b" width="32" height="44" xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAsCAYAAAAEuLqPAAAC6klEQVRYCdVY7XHbMAz1z/6oKYzgESBNkBG6QbNBu0GyQbxBu4G7QUbwCB7BI7j3KICCKYqEYuV69R3P/AAeQBAAIe52/8vvC/EhEP/oaDh11F866m+mYfweaDjuib9tuqevxE8AN8Ks4KX+JdDwi4gPH1aGiCnQ8GYEXwMxQJ8BjHUFl/FTIH7tiM/3PMOL0rn/AWjMfCXiVyuwBQT+QPx7UmQ4ufkz4ReMWwKX1mEts5GzS4nVDEvSZT7b0HuVPNDwImZ7aOe5EKtEIP6Zr8exEEWvfsTsRfDdbkdjNAEfPpUcONEH6qPTwNPT5MYdySE3RMsMGprB/I/sHrucAZuJRSsge41nz3UnMWB5F/7TUgA8mtTuaAPxEQoUTZNLKoxH5/UpPyarKOuYoIpapdV6RyPnbkcVFnMM50SmsY8LJ006Oioc/A7ySGKibeKR2L95QUBnhN/2xN/X8M7kzSYaaFb4mt0r7EyeCcF5glAu+c+EI7HEWkDqgbeMvDgsKRALjBWOhOv4oIlFAF3pm4hZ6Ccn/EgYIp2q5SS9uhxYbkiE/J9kHhMa5TydKKeOAmE3XsuBW0N+5ri64E1GSk9Lt9uka+pNGbefH5exQnNHGsuolBJ6oyM86mtlPgCKg+AoeAkTNycRT6l0iVDmrfCO+sn5Snwk17I4VlEJeH+JtzSXCZ+bvsw01gYlJWAZgJb48rlMOGpCF1/E8VgiF2jHEqbxzGH2YhVkGUp9owRM18ySFsNkzNW8FifFrTc8lVlv2JozK23134TntUpoFuVzDvdE3eMNT7Wru/HWC5pw7tJtVUJjca0CmqY3q7BVAXye74mfW23zEl8VkCwZP148/c0t0BGfANpqelFtroDXCT/NB/65AriE8PTSap92BB7HszSb+wDCEOfbavo8s6UC8eHJW/vhEQKW2EwB/aCUKhhPdrWWXslmhWcj4y4u4ypWs9ozrvXX1IuLgvMFVDQ4hkZDxeSuHf4CwwK1/5LnQpQAAAAASUVORK5CYII="/></defs></svg>	<a
		href="https://evnt.is/1axw"
		target="_blank"
		rel="noopener noreferrer"
		class="tec-tickets__admin-settings-tickets-commerce-paypal-help-link-url"
	>Get troubleshooting help</a>
</div>

</div>
</div>
';
