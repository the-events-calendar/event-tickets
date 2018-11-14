<?php
/**
 * This template renders the summary tickets
 *
 * @version TBD
 *
 */
?>
<div class="tribe-block__tickets__registration__tickets">

	<?php $this->template( 'summary/tickets-header' ); ?>

	<?php foreach ( $tickets as $key => $ticket ) : ?>

		<?php $this->template( 'summary/ticket/content', array( 'ticket' => $ticket, 'key' => $key ) ); ?>

	<?php endforeach; ?>

</div>