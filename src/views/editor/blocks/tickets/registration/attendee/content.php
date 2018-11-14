<?php
/**
 * This template renders the registration/purchase attendee fields
 *
 * @version 0.3.0-alpha
 *
 */
?>
<div
	class="tribe-block__tickets__item__attendee__fields"
>
	<?php foreach ( $tickets as $key => $ticket ) : ?>
		<?php $this->template( 'editor/blocks/tickets/registration/attendee/fields', array( 'ticket' => $ticket, 'key' => $key ) ); ?>
	<?php endforeach; ?>
</div>

