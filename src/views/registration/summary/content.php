<?php
/**
 * This template renders the event content
 *
 * @version TBD
 *
 */
?>
<div class="tribe-block__tickets__registration__summary">

	<?php $this->template( 'summary/toggle-handler' ); ?>

	<?php $this->template( 'summary/registration-status' ); ?>

	<?php $this->template( 'summary/title', array( 'event_id' => $event_id ) ); ?>

	<?php $this->template( 'summary/description', array( 'event_id' => $event_id ) ); ?>

	<?php $this->template( 'summary/tickets', array( 'tickets' => $tickets ) ); ?>

</div>