<?php
/**
 * Tickets Commerce: Checkout Cart Item Description
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/checkout/cart/item/details/description.php
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
 * @var array            $item                  Which item this row will be for.
 */

$classes = [
	'tribe-common-b2',
	'tribe-common-b3--min-medium',
	'tribe-tickets__commerce-checkout-cart-item-details-description',
	'tribe-common-a11y-hidden',
];

$item_details_id = 'tribe-tickets__commerce-checkout-cart-item-details-description--' . $item['ticket_id'];

?>
<div id="<?php echo esc_attr( $item_details_id ); ?>" <?php tribe_classes( $classes ); ?>>
	<?php echo wp_kses_post( $item['obj']->description ); ?>
</div>
