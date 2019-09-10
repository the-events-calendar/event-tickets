<?php
/**
 * This template renders the registration/purchase attendee fields
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/registration-js/attendees/content.php
 *
 * @since 4.9
 * @since 4.10.1 Update template paths to add the "registration/" prefix
 * @version 4.10.1
 *
 */
if (
	! class_exists( 'Tribe__Tickets_Plus__Meta' )
	|| ! class_exists( 'Tribe__Tickets_Plus__Meta__Storage' )
) {
	return;
}

$storage = new Tribe__Tickets_Plus__Meta__Storage();
$meta    = tribe( 'tickets-plus.main' )->meta();
?>

<?php foreach ( $tickets as $ticket ) : ?>
		<?php
		// Only include those who have meta
		$has_meta = get_post_meta( $ticket['id'], '_tribe_tickets_meta_enabled', true );

		if ( empty( $has_meta ) || ! tribe_is_truthy( $has_meta ) ) {
			continue;
		}
		?>
		<script type="text/html" id="tmpl-tribe-registration--<?php echo esc_attr($ticket['id']); ?>">
			<?php

			$ticket_qty = $ticket['qty'];
			$post           = get_post( $ticket['id'] );
			?>
			<?php // go through each attendee ?>
			<?php while ( 0 < $ticket_qty ) : ?>
				<?php
					/**
					* @var Tribe__Tickets_Plus__Meta $meta
					*/
					$fields     = $meta->get_meta_fields_by_ticket( $post->ID );
					$saved_meta = $storage->get_meta_data_for( $post->ID );

					$args = array(
						'event_id'   => $event_id,
						'ticket'     => $post,
						'fields'     => $fields,
						'saved_meta' => $saved_meta,
					);


					$this->template( 'registration-js/attendees/fields', $args );
					$ticket_qty--;
				?>
			<?php endwhile; ?>
		</script>
<?php
endforeach;
