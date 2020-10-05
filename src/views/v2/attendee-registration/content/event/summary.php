<?php
/**
 * Attendee registration
 * Content > Event > Summary
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/attendee-registration/content/event/summary.php
 *
 * See more documentation about our views templating system.
 *
 * @link http://m.tri.be/1amp
 *
 * @since TBD
 * @version TBD
 */

?>
<div class="tribe-tickets__registration__summary">
	<?php $this->template( 'v2/attendee-registration/content/event/summary/description', [ 'post_id' => $post_id ] ); ?>
	<?php $this->template( 'v2/attendee-registration/content/event/summary/title', [ 'post_id' => $post_id ] ); ?>
</div>
