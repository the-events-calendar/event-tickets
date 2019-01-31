<?php
/**
 * This template renders the summary tickets
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/registration/summary/tickets.php
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
