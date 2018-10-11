<?php
/**
 * This template renders the registration/purchase attendee fields
 *
 * @version TBD
 *
 */
?>
<div class="tribe-block__tickets__item__attendee__fields">
    <form method="post" class="tribe-block__tickets__item__attendee__fields__form">
        <?php foreach ( $tickets as $key => $ticket ) : ?>
            <?php
            /**
             * @var Tribe__Tickets_Plus__Meta $meta
             */
            $meta        = tribe( 'tickets-plus.main' )->meta();
            $fields      = $meta->get_meta_fields_by_ticket( $ticket->ID );

            $this->template( 'fields', array( 'ticket' => $ticket, 'key' => $key + 1, 'fields' => $fields ) );
            ?>
        <?php endforeach; ?>
        <button type="submit"><?php _e( 'Save Attendee Info', 'tribe' ); ?></button>
    </form>
</div>

