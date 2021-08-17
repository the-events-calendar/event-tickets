<?php
use \TEC\Tickets\Commerce\Gateways\PayPal\REST\Order_Endpoint;
?>

<div id="paypal-button-container"></div>

<script>
	const button = {
		style: {
			layout: 'vertical',
			color: 'blue',
			shape: 'rect',
			label: 'paypal'
		},
		createOrder: function ( data, actions ) {
			return fetch(
				'<?php echo tribe( Order_Endpoint::class )->get_route_url(); ?>',
				{
					method: 'POST'
				}
			).then(
				function ( res ) {
					return res.json();
				}
			).then(
				function ( data ) {
					return data.id;
				}
			);
		},
		onApprove: function ( data, actions ) {
			return fetch(
				'<?php echo tribe( Order_Endpoint::class )->get_route_url(); ?>/' + data.orderID,
				{
					method: 'POST'
				}
			).then(
				function ( res ) {
					if ( ! res.ok ) {
						alert('Something went wrong');
					}
				}
			);
		}
	};

	paypal.Buttons(button).render('#paypal-button-container');
</script>



