<?php
/**
 * Tickets Commerce: Checkout Table Row
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/commerce/checkout/table-row.php
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
 * @var int              $section               Which Section that we are going to render for this table.
 * @var \WP_Post         $post                  Which Section that we are going to render for this table.
 * @var array            $item                  Which item this row will be for.
 */

use \TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Utils\Price;

?>
<tr>
	<td>
		<?php echo $item['obj']->name; ?>
	</td>
	<td>
		<?php echo tribe( \TEC\Tickets\Commerce\Module::class )->get_price_html( $item['ticket_id'] ); ?>
	</td>
	<td>
		<?php echo $item['quantity']; ?>
	</td>
	<td>
		<?php echo $item['sub_total']; ?>
	</td>
</tr>
<tr>
	<td colspan="4">

	</td>
</tr>
