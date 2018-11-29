<?php
/**
 * This template renders the summary tickets
 *
 * @version 4.9
 *
 */
?>
<div class="tribe-block__tickets__registration__tickets__item">

	<?php $this->template( 'summary/ticket/icon', array( 'ticket' => $ticket, 'key' => $key ) ); ?>

	<?php $this->template( 'summary/ticket/quantity', array( 'ticket' => $ticket, 'key' => $key ) ); ?>

	<?php $this->template( 'summary/ticket/title', array( 'ticket' => $ticket, 'key' => $key ) ); ?>

	<?php $this->template( 'summary/ticket/price', array( 'ticket' => $ticket, 'key' => $key ) ); ?>

</div>