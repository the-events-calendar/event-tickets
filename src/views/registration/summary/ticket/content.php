<?php
/**
 * This template renders the summary tickets
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/registration/summary/ticket/content.php
 *
 * @version 4.9
 *
 */
?>
<div class="tribe-block__tickets__registration__tickets__item">

	<?php $this->template( 'registration/summary/ticket/icon', array( 'ticket' => $ticket, 'key' => $key ) ); ?>

	<?php $this->template( 'registration/summary/ticket/quantity', array( 'ticket' => $ticket, 'key' => $key ) ); ?>

	<?php $this->template( 'registration/summary/ticket/title', array( 'ticket' => $ticket, 'key' => $key ) ); ?>

	<?php $this->template( 'registration/summary/ticket/price', array( 'ticket' => $ticket, 'key' => $key ) ); ?>

</div>
