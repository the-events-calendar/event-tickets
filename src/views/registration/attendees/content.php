<?php
/**
 * This template renders the registration/purchase attendee fields
 *
 * @version TBD
 *
 */

$storage = new Tribe__Tickets_Plus__Meta__Storage();
$meta    = tribe( 'tickets-plus.main' )->meta();
?>

<?php get_header(); ?>

<div class="tribe-block__tickets__item__attendee__fields">
    <form method="post" class="tribe-block__tickets__item__attendee__fields__form">
		<?php foreach ( $tickets as $key => $ticket ) : ?>
			<?php
			/**
			 * @var Tribe__Tickets_Plus__Meta $meta
			 */
			$fields     = $meta->get_meta_fields_by_ticket( $ticket->ID );
			$saved_meta = $storage->get_meta_data_for( $ticket->ID );

			$this->template( 'fields', array( 'ticket' => $ticket, 'key' => $key + 1, 'fields' => $fields, 'saved_meta' => $saved_meta ) );
			?>
		<?php endforeach; ?>
        <button type="submit"><?php _e( 'Save Attendee Info', 'tribe' ); ?></button>
    </form>
</div>

<?php get_footer(); ?>

