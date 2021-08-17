<?php
/**
 * Tickets Commerce: Checkout Cart Item title
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/checkout/cart/item/details/toggle.php
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

$aria_controls = 'tribe-tickets__commerce-checkout-cart-item-details-description--' . $item['ticket_id'];
?>
<div class="tribe-tickets__commerce-checkout-cart-item-details-toggle">
	<button
		type="button"
		class="tribe-common-b3 tribe-tickets__commerce-checkout-cart-item-details-button--more"
		aria-controls="<?php echo esc_attr( $aria_controls ); ?>"
		tabindex="0"
	>
		<span class="screen-reader-text tribe-common-a11y-visual-hide"><?php esc_html_e( 'Open the ticket description in checkout.', 'event-tickets' ); ?></span>
		<?php echo esc_html_x( 'More info', 'Opens the ticket description', 'event-tickets' ); ?>
	</button>
	<button
		type="button"
		class="tribe-common-b3 tribe-tickets__commerce-checkout-cart-item-details-button--less"
		aria-controls="<?php echo esc_attr( $aria_controls ); ?>"
		tabindex="0"
	>
		<span class="screen-reader-text tribe-common-a11y-visual-hide"><?php esc_html_e( 'Close the ticket description in checkout.', 'event-tickets' ); ?></span>
		<?php echo esc_html_x( 'Less info', 'Closes the ticket description', 'event-tickets' ); ?>
	</button>
</div>
