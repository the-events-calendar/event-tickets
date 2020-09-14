<?php
/**
 * Attendee registration
 * Title
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/attendee-registration/content/title.php
 *
 * See more documentation about our views templating system.
 *
 * @link http://m.tri.be/1amp
 *
 * @since TBD
 *
 * @version TBD
 */

?>
<h1 class="tribe-common-h2 tribe-common-h1--min-medium tribe-common-h--alt tribe-tickets__registration__page-title">
	<?php echo esc_html( tribe( 'tickets.attendee_registration.template' )->get_page_title() ); ?>
</h1>
