<?php
/**
 * Block: Tickets
 * Quantity Remove
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/quantity-remove.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9.3
 * @version TBD
 *
 */

$ticket = $this->get( 'ticket' );
$button_title = sprintf(
	_x('Decrease ticket quantity for %s', '%s: ticket name.', 'event-tickets'),
	$ticket->name
);
?>
<button
	class="tribe-tickets__item__quantity__remove"
	title="<?php echo esc_attr( $button_title ); ?>"
>
	<span class="screen-reader-text"><?php echo esc_html( $button_title ); ?></span>
	<?php echo esc_html_x( '-', 'A minus sign, remove ticket.', 'event-tickets' ); ?>
</button>
