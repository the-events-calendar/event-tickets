<?php
/**
 * This template renders the registration/purchase attendee fields
 *
 * @version TBD
 *
 */
?>
<div
	class="tribe-block__tickets__item__attendee__fields"
>
	<?php foreach ( $tickets as $key => $ticket ) : ?>
		<?php
        /**
		 * @var Tribe__Tickets_Plus__Meta $meta
		 */
		$meta   = tribe( 'tickets-plus.main' )->meta();
		$fields = $meta->get_meta_fields_by_ticket( $ticket->ID );

		$this->template( 'fields', array( 'ticket' => $ticket, 'key' => $key, 'fields' => $fields ) );
		?>
	<?php endforeach; ?>
</div>

