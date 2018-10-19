<?php
/**
 * This template renders the registration/purchase attendee fields
 *
 * @version TBD
 *
 */

$storage           = new Tribe__Tickets_Plus__Meta__Storage();
$meta              = tribe( 'tickets-plus.main' )->meta();
$current_ticket_id = 0;
$i                 = 0;
$redirect          = tribe_get_request_var( 'event_tickets_redirect_to', '' );
?>

<?php get_header(); ?>

<div class="tribe-block__tickets__item__attendee__fields">
    <form method="post" class="tribe-block__tickets__item__attendee__fields__form">
		<?php foreach ( $tickets as $ticket ) : ?>
			<?php
			if ( $current_ticket_id !== $ticket->ID ) {
				$current_ticket_id = $ticket->ID;
				$i                 = 0;
				echo '<h3 class="tribe-ticket__heading">' . $ticket->post_title . '</h3>';
			}

			/**
			 * @var Tribe__Tickets_Plus__Meta $meta
			 */
			$fields     = $meta->get_meta_fields_by_ticket( $ticket->ID );
			$saved_meta = $storage->get_meta_data_for( $ticket->ID );

			$this->template( 'fields', array( 'ticket' => $ticket, 'key' => $i + 1, 'fields' => $fields, 'saved_meta' => $saved_meta ) );
			$i++;
			?>
		<?php endforeach; ?>
        <input type="hidden" name="tribe_tickets_saving_attendees" value="1"/>
        <?php if ( ! empty( $redirect ) ) : ?>
            <input type="hidden" name="event_tickets_redirect_to" value="<?php echo $redirect; ?>" />
        <?php endif; ?>
        <button type="submit"><?php _e( 'Save Attendee Info', 'tribe' ); ?></button>
    </form>
</div>

<?php get_footer(); ?>

