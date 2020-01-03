<?php
/**
 * This template renders the registration/purchase attendee fields
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/registration/attendees/content.php
 *
 * @since 4.9
 * @since 4.10.1 Update template paths to add the "registration/" prefix
 * @version 4.11.0
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

	$attendee_count = 0;
	$post           = get_post( $ticket['id'] );
	?>
	<h3 class="tribe-common-h5 tribe-common-h5--min-medium tribe-common-h--alt tribe-ticket__heading"><?php echo get_the_title( $post->ID ); ?></h3>
	<?php // go through each attendee ?>
	<?php while ( $attendee_count < $ticket['qty'] ) : ?>
		<?php
 			/**
 			 * @var Tribe__Tickets_Plus__Meta $meta
 			 */
			$fields     = $meta->get_meta_fields_by_ticket( $post->ID );
			$saved_meta = $storage->get_meta_data_for( $post->ID );

			$args = array(
				'event_id'   => $event_id,
				'ticket'     => $post,
				'key'        => $attendee_count,
				'fields'     => $fields,
				'saved_meta' => $saved_meta,
			);


			$this->template( 'registration/attendees/fields', $args );
			$attendee_count++;
		?>
	<?php endwhile; ?>
<?php endforeach;
