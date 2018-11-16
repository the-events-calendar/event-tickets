<?php
/**
 * This template renders the summary tickets
 *
 * @version TBD
 *
 */
?>
<div class="tribe-block__tickets__registration__tickets__item">

	<?php $this->template( 'editor/blocks/tickets/registration/summary/ticket-icon', array( 'ticket' => $ticket, 'key' => $key ) ); ?>

	<?php $this->template( 'editor/blocks/tickets/registration/summary/ticket-quantity', array( 'ticket' => $ticket, 'key' => $key ) ); ?>

	<?php $this->template( 'editor/blocks/tickets/registration/summary/ticket-title', array( 'ticket' => $ticket, 'key' => $key ) ); ?>

	<?php $this->template( 'editor/blocks/tickets/registration/summary/ticket-price', array( 'ticket' => $ticket, 'key' => $key ) ); ?>

</div>