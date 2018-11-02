<?php
/**
 * This template renders the registration/purchase attendee fields
 *
 * @version TBD
 *
 */

if ( ! class_exists( 'Tribe__Tickets_Plus__Meta__Storage' ) ) {
    return;
}

$storage           = new Tribe__Tickets_Plus__Meta__Storage();
$meta              = tribe( 'tickets-plus.main' )->meta();
$current_ticket_id = 0;
$i                 = 0;
?>

<?php foreach ( $tickets as $ticket ) : ?>
	<?php
	$j = 0;
	$post = get_post( $ticket['id'] );
	?>
	<h3 class="tribe-ticket__heading"><?php echo get_the_title( $post->ID ); ?></h3>
	<?php while ( $j < $ticket['qty'] ) : ?>
		<?php
 			/**
 			 * @var Tribe__Tickets_Plus__Meta $meta
 			 */

			$fields     = $meta->get_meta_fields_by_ticket( $post->ID );
			$saved_meta = $storage->get_meta_data_for( $post->ID );

			$this->template( 'attendees/fields', array( 'ticket' => $post, 'key' => $j + 1, 'fields' => $fields, 'saved_meta' => $saved_meta ) );
			$j++;
		?>
	<?php endwhile; ?>
<?php endforeach; ?>