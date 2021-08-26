<?php
/**
 * Tickets Commerce: Checkout Page Header
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/checkout/header.php
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
 * @var array[]          $sections              [Global] Which events we have tickets for.
 */

// @todo @bordoni @juanfra: Maybe move the modify attendees link to ET+.
?>
<header class="tribe-tickets__commerce-checkout-header">
	<h3 class="tribe-common-h2 tribe-tickets__commerce-checkout-header-title">
		<?php esc_html_e( 'Purchase Tickets', 'event-tickets' ); ?>
	</h3>

	<div class="tribe-common-b2 tribe-tickets__commerce-checkout-header-links">
		<a
			class="tribe-common-anchor-alt tribe-tickets__commerce-checkout-header-link-modify-attendees"
			href="#"
		><?php esc_html_e( 'modify attendees', 'event-tickets' ); ?></a>
		<a
			class="tribe-common-anchor-alt tribe-tickets__commerce-checkout-header-link-back-to-event"
			href="<?php the_permalink( $sections[ key( $sections ) ] ); ?>"
		><?php esc_html_e( 'back to event', 'event-tickets' ); ?></a>
	</div>
</header>
