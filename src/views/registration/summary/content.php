<?php
/**
 * This template renders the event content
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/registration/summary/content.php
 *
 * @since 4.9
 * @since 4.10.1 Update template paths to add the "registration/" prefix
 * @version TBD
 *
 */
?>
<div class="tribe-tickets__registration__summary">

	<?php $this->template( 'registration/summary/toggle-handler' ); ?>

	<?php $this->template( 'registration/summary/registration-status' ); ?>

	<?php $this->template( 'registration/summary/title', array( 'event_id' => $event_id ) ); ?>

	<?php $this->template( 'registration/summary/description', array( 'event_id' => $event_id ) ); ?>

	<?php $this->template( 'registration/summary/tickets', array( 'tickets' => $tickets ) ); ?>

</div>
