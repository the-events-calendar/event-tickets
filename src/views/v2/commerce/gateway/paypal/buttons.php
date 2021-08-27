<?php
/**
* Tickets Commerce: Checkout Buttons for PayPal
*
* Override this template in your own theme by creating a file at:
* [your-theme]/tribe/tickets/v2/commerce/gateway/paypal/buttons.php
*
* See more documentation about our views templating system.
*
* @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
*
* @since   TBD
*
* @version TBD

* @var bool $must_login [Global] Whether login is required to buy tickets or not.
*/

use \TEC\Tickets\Commerce\Gateways\PayPal\REST\Order_Endpoint;

if ( empty( $must_login ) ) {
	return;
}
?>

<div id="tec-tc-gateway-paypal-checkout-buttons"></div>
