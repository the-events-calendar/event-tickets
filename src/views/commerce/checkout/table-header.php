<?php
/**
 * Tickets Commerce: Checkout Table Header
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

?>
<thead>
<tr>
	<th class="" colspan="4">
		<h3><?php echo get_the_title( $post ); ?></h3>
	</th>
</tr>
</thead>