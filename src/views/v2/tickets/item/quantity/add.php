<?php
/**
 * Block: Tickets
 * Quantity Add
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/tickets/item/quantity/add.php
 *
 * See more documentation about our views templating system.
 *
 * @link http://m.tri.be/1amp
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var	Tribe__Tickets__Editor__Template $this   Template object.
 * @var	Tribe__Tickets__Ticket_Object    $ticket The ticket object.
 */

$button_title = sprintf(
	// translators: %s: ticket name.
	_x( 'Increase ticket quantity for %s', '%s: ticket name.', 'event-tickets' ),
	$ticket->name
);

?>
<button
	class="tribe-tickets__item__quantity__add"
	title="<?php echo esc_attr( $button_title ); ?>"
	type="button"
>
	<span class="screen-reader-text tribe-common-a11y-visual-hide"><?php echo esc_html( $button_title ); ?></span>
	<?php echo esc_html_x( '+', 'A plus sign, add ticket.', 'event-tickets' ); ?>
</button>
