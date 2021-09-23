<?php
/**
 * Tickets Commerce: Checkout Cart Item Details
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/checkout/cart/item/details.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since   5.1.9
 *
 * @version 5.1.9
 *
 * @var \Tribe__Template $this                  [Global] Template object.
 * @var Module           $provider              [Global] The tickets provider instance.
 * @var string           $provider_id           [Global] The tickets provider class name.
 * @var array[]          $items                 [Global] List of Items on the cart to be checked out.
 * @var bool             $must_login            [Global] Whether login is required to buy tickets or not.
 * @var string           $login_url             [Global] The site's login URL.
 * @var string           $registration_url      [Global] The site's registration URL.
 * @var bool             $is_tec_active         [Global] Whether `The Events Calendar` is active or not.
 * @var array[]          $gateways              [Global] An array with the gateways.
 * @var int              $gateways_active       [Global] The number of active gateways.
 * @var array            $item                  Which item this row will be for.
 */

?>
<div class="tribe-tickets__commerce-checkout-cart-item-details">

	<?php $this->template( 'checkout/cart/item/details/title', [ 'item' => $item ] ); ?>

	<?php $this->template( 'checkout/cart/item/details/toggle', [ 'item' => $item ] ); ?>

	<?php $this->template( 'checkout/cart/item/details/description', [ 'item' => $item ] ); ?>

</div>
