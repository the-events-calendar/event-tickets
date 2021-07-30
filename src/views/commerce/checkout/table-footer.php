<?php
/**
 * Tickets Commerce: Checkout Table Footer
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/commerce/checkout/table-footer.php
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
<tfoot>
<tr>
	<td colspan="3">
		<?php esc_html_e( 'Quantity: ', 'event-tickets' ); ?>
		<?php echo array_sum( wp_list_pluck( $items, 'quantity' ) ); ?>
	</td>
	<td>
		<?php esc_html_e( 'Total: ', 'event-tickets' ); ?>
		<?php echo esc_html( $total_value ); ?>
	</td>
</tr>
</tfoot>
