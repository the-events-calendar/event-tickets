<?php
/**
 * Block: Tickets
 * Extra column, available Quantity
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/extra-available-quantity.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9.3
 * @version 4.11
 *
 */


$ticket = $this->get( 'ticket' );
if ( empty( $ticket->available() ) ) {
	return;
}
?>
<span class="tribe-tickets__item__extra__available__quantity"><?php echo esc_html( $ticket->available() ); ?></span>
<?php esc_html_e( 'available', 'event-tickets' );
