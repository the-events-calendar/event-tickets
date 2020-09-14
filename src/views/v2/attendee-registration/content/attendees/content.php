<?php
/**
 * This template renders the registration/purchase attendee fields
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/attendee-registration/content/attendees/content.php
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var int                             $post_id  The event/post ID.
 * @var Tribe__Tickets__Ticket_Object[] $tickets  List of tickets for the particular event.
 * @var Tribe__Tickets__Tickets         $provider The tickets provider class.
 */

// Bail if ET+ is not active.
if (
	! class_exists( 'Tribe__Tickets_Plus__Meta' )
	|| ! class_exists( 'Tribe__Tickets_Plus__Meta__Storage' )
) {
	return;
}

// Bail if there are no tickets.
if ( empty( $tickets ) ) {
	return;
}

/**
* @var Tribe__Tickets_Plus__Meta $meta
*/
$meta    = tribe( 'tickets-plus.main' )->meta();
$storage = new Tribe__Tickets_Plus__Meta__Storage();
?>
<?php foreach ( $tickets as $ticket ) : ?>
	<?php
	// Sometimes we get an array - let's handle that.
	if ( is_array( $ticket ) ) {
		$ticket = $provider->get_ticket( $post_id, $ticket['id'] );
	}

	// Only include tickets with meta.
	if ( ! $ticket->has_meta_enabled() ) {
		continue;
	}
	?>
	<script
		type="text/html"
		class="registration-js-attendees-content"
		id="tmpl-tribe-registration--<?php echo esc_attr( $ticket->ID ); ?>"
	>
		<?php
		$ticket_qty = 1;
		$post       = get_post( $ticket->ID );
		$fields     = $meta->get_meta_fields_by_ticket( $post->ID );
		$saved_meta = $storage->get_meta_data_for( $post->ID );
		?>
		<?php // go through each attendee. ?>
		<?php while ( 0 < $ticket_qty ) : ?>
			<?php

				$args = [
					'post_id'    => $post_id,
					'ticket'     => $post,
					'fields'     => $fields,
					'saved_meta' => $saved_meta,
				];

				$this->template( 'v2/attendee-registration/content/attendees/fields', $args );
				$ticket_qty--;
			?>
		<?php endwhile; ?>
	</script>
	<?php
endforeach;
