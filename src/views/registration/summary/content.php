<?php
/**
 * This template renders the event content
 *
 * @version TBD
 *
 */
?>
<div class="tribe-block__tickets__registration__summary">

	<div class="toggle-handler">
		<i class="dashicons dashicons-arrow-up-alt2"></i>
	</div>
	<div class="registration-status">
		<i class="dashicons dashicons-no-alt"></i>
	</div>

	<?php $this->template( 'summary/title', array( 'event_id' => $event_id ) ); ?>

	<?php $this->template( 'summary/description', array( 'event_id' => $event_id ) ); ?>

	<?php $this->template( 'summary/tickets', array( 'tickets' => $tickets ) ); ?>

</div>