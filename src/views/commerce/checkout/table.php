<?php
/**
 * Tickets Commerce: Checkout Table
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
 * @var int              $section               Which Section that we are going to render for this table.
 */
use \TEC\Tickets\Commerce\Module;

$post = get_post( $section );
?>

<table>
	<?php $this->template( 'checkout/table-header', [ 'section' => $section, 'post' => $post ] ); ?>

	<tbody>
	<?php foreach ( $items as $item ) : ?>
		<?php
		if ( $item['obj']->get_event_id() !== $section ) {
			continue;
		}
		?>
		<?php $this->template( 'checkout/table-row', [ 'section' => $section, 'post' => $post, 'item' => $item ] ); ?>
	<?php endforeach; ?>
	</tbody>

	<?php $this->template( 'checkout/table-footer', [ 'section' => $section, 'post' => $post ] ); ?>
</table>

