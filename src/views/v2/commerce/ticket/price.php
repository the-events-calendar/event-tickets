<?php
/**
 * Tickets Commerce: Ticket Price
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/ticket/price.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since   5.2.3
 *
 * @version 5.2.3
 *
 * @var \TEC\Tickets\Commerce\Utils\Value $price The Value instance of the ticket price.
 */
?>

<span class="tribe-tickets-price-amount amount">
			<?php echo esc_html( $price->get_currency() ); ?>
</span>
