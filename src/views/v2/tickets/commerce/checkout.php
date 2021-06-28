<?php
echo 'Checkout page is here';
?>
<script src="https://www.paypal.com/sdk/js?client-id=sb&locale=en_US&components=buttons" data-partner-attribution-id="TheEventsCalendar_SP_PPCP"></script>

<div id="paypal-field-container"></div>
<div id="paypal-button-container"></div>
<div id="card-number"></div>
<div id="cvv"></div>
<div id="expiration-date"></div>

<script>
	const button = {
		style          : {
			layout : 'vertical', color : 'blue', shape : 'rect', label : 'paypal'
		}, createOrder : function ( data, actions ) {
			// Set up the transaction
			return actions.order.create( {
											 purchase_units : [
												 {
													 amount : {
														 value : '0.01'
													 }
												 }
											 ]
										 } );
		}
	};

	paypal.Buttons( button ).render( '#paypal-button-container' );
</script>
