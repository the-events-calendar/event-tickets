<?php
/**
 * Block: Tickets
 * Title
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/tickets/title.php
 *
 * See more documentation about our views templating system.
 *
 * @link http://m.tri.be/1amp
 *
 * @since TBD
 * @version TBD
 */

?>
<h2 class="tribe-common-h4 tribe-common-h--alt tribe-tickets__title">
	<?php echo esc_html( tribe_get_ticket_label_plural( 'event-tickets' ) ); ?>
</h2>
