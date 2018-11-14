<?php
/**
 * This template renders a Single Ticket availability
 *
 * @version 0.3.0-alpha
 *
 */
$ticket    = $this->get( 'ticket' );
?>
<span class="tribe-block__tickets__item__extra__available_quantity"><?php echo esc_html( $ticket->available() ); ?></span>
<?php esc_html_e( 'available', 'events-gutenberg' ); ?>