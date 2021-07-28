<?php
/**
 * Tickets Commerce: Checkout Page
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/commerce/checkout/page.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since   TBD
 *
 * @version TBD
 *
 * @var \Tribe__Template $this                  [Global] Template object.
 * @var Module           $provider              [Global] The tickets provider instance.
 * @var string           $provider_id           [Global] The tickets provider class name.
 * @var array[]          $items                 [Global] List of Items on the cart to be checked out.
 * @var string           $paypal_attribution_id [Global] What is our PayPal Attribution ID.
 */

use \TEC\Tickets\Commerce\Module;
use Tribe__Tickets__Ticket_Object as Ticket;

var_dump( $items );
?>
<script src="https://www.paypal.com/sdk/js?client-id=sb&locale=en_US&components=buttons" data-partner-attribution-id="<?php echo esc_attr( $paypal_attribution_id ); ?> "></script>

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
