<?php
/**
 * Tickets Commerce: Checkout Cart Items
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/checkout/cart/items.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since 5.1.10
 *
 * @version 5.21.0
 *
 * @var Tribe__Template $this             [Global] Template object.
 * @var Module          $provider         [Global] The tickets provider instance.
 * @var string          $provider_id      [Global] The tickets provider class name.
 * @var array[]         $items            [Global] List of Items on the cart to be checked out.
 * @var bool            $must_login       [Global] Whether login is required to buy tickets or not.
 * @var string          $login_url        [Global] The site's login URL.
 * @var string          $registration_url [Global] The site's registration URL.
 * @var bool            $is_tec_active    [Global] Whether `The Events Calendar` is active or not.
 * @var array[]         $gateways         [Global] An array with the gateways.
 * @var int             $section          Which Section that we are going to render for this table.
 * @var WP_Post         $post             Which Section that we are going to render for this table.
 */

use TEC\Tickets\Commerce\Module;

if ( empty( $items ) ) {
	return;
}

?>
<div class="tribe-tickets__commerce-checkout-cart-items">
	<?php
	foreach ( $items as $item ) {
		if ( $item['event_id'] !== $section ) {
			continue;
		}

		// Use ticket as the default item type.
		$item_type = $item['type'] ?? 'ticket';

		// Set the path based on the item type.
		$path = 'ticket' === $item_type
			? 'checkout/cart/ticket'
			: 'checkout/cart/item';

		$this->template(
			$path,
			[
				'section' => $section,
				'post'    => $post,
				'item'    => $item,
			]
		);
	}
	?>
</div>
