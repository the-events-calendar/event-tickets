<?php
/**
 * This template renders the summary tickets
 *
 * @version 4.9
 *
 */
?>
<div class="tribe-block__tickets__registration__tickets">

	<?php $this->template( 'registration/summary/tickets-header' ); ?>

	<?php foreach ( $tickets as $key => $ticket ) : ?>

		<?php $this->template( 'registration/summary/ticket/content', array( 'ticket' => $ticket, 'key' => $key ) ); ?>

	<?php endforeach; ?>

</div>
