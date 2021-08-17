<?php
var_dump( $tickets );
var_dump( $event );
?>
<script src="<?php echo tribe( \TEC\Tickets\Commerce\Gateways\PayPal\Client::class )->get_js_sdk_url(); ?>" data-partner-attribution-id="<?php echo esc_atrr( $paypal_attribution_id ); ?> "></script>

<div id="paypal-field-container"></div>
<div id="paypal-button-container"></div>
<div id="card-number"></div>
<div id="cvv"></div>
<div id="expiration-date"></div>

<script>
	const button = {
		style: {
			layout: 'vertical',
			color: 'blue',
			shape: 'rect',
			label: 'paypal'
		}, createOrder: function (data, actions) {
			// Set up the transaction
			return actions.order.create({
				purchase_units: [
					{
						amount: {
							value: '0.01'
						}
					}
				]
			});
		}
	};

	paypal.Buttons(button).render('#paypal-button-container');
</script>
