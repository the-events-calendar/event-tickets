<?php
/**
 * Tickets Commerce: Checkout Cart Item Sub-Total
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/checkout/cart/item/sub-total.php
 *
 * See more documentation about our views templating system.
 *
 * @link     https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since 5.2.3   enforcing proper currency formatting
 * @since 5.1.9
 *
 * @version  5.2.3
 *
 * @var \Tribe__Template $this             [Global] Template object.
 * @var Module           $provider         [Global] The tickets provider instance.
 * @var string           $provider_id      [Global] The tickets provider class name.
 * @var array[]          $items            [Global] List of Items on the cart to be checked out.
 * @var bool             $must_login       [Global] Whether login is required to buy tickets or not.
 * @var string           $login_url        [Global] The site's login URL.
 * @var string           $registration_url [Global] The site's registration URL.
 * @var bool             $is_tec_active    [Global] Whether `The Events Calendar` is active or not.
 * @var array[]          $gateways         [Global] An array with the gateways.
 * @var array            $item             Which item this row will be for.
 */

/** @var \TEC\Tickets\Commerce\Utils\Value */
$sub_total = $item['sub_total'];
?>
<div class="tribe-tickets__commerce-checkout-cart-item-subtotal">
	<?php echo wp_kses_post( $sub_total->get_currency() ); ?>
</div>
