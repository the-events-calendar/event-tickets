<?php
/**
 * Tickets Commerce: Checkout Cart Item Price
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/checkout/cart/item/price.php
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

?>
<div class="tribe-tickets__commerce-checkout-cart-item-price">
	<?php echo $provider->get_price_html( $item['ticket_id'] ); ?>
</div>
