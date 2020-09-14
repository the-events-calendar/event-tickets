<?php
/**
 * Block: Tickets
 * Quantity Remove
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/tickets/item/quantity/remove.php
 *
 * See more documentation about our views templating system.
 *
 * @link http://m.tri.be/1amp
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Editor__Template $this
 * @var Tribe__Tickets__Ticket_Object    $ticket
 */

$button_title = sprintf(
	// Translators: %s: ticket name.
	_x( 'Decrease ticket quantity for %s', 'Decrease ticket quantity button title', 'event-tickets' ),
	$ticket->name
);
?>
<button
	class="tribe-tickets__item__quantity__remove"
	title="<?php echo esc_attr( $button_title ); ?>"
	type="button"
>
	<span class="screen-reader-text tribe-common-a11y-visual-hide"><?php echo esc_html( $button_title ); ?></span>
	<?php echo esc_html_x( '-', 'A minus sign, remove ticket.', 'event-tickets' ); ?>
</button>
