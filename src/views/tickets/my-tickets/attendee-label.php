<?php
/**
 * My Tickets: Attendee Label
 *
 * Override this template in your own theme by creating a file at [your-theme]/tribe/tickets/tickets/my-tickets/attendee-label.php
 *
 * @since 5.6.7
 * @since 5.9.1 Corrected template override filepath
 *
 * @version 5.9.1
 *
 * @var  int  $attendee_label  The label for the attendee.
 */

?>
<div class="list-attendee">
	<?php echo esc_html( $attendee_label ); ?>
</div>