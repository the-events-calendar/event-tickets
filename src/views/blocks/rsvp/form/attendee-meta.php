<?php
/**
 * Block: RSVP
 * Attendee Meta
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/rsvp/form/attendee-meta.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9.3
 * @since 4.11.5 Corrected the template override instructions in template comments.
 *
 * @version 4.11.5
 */

$ticket    = $this->get( 'ticket' );
$ticket_id = $this->get( 'ticket_id' );
$has_meta  = get_post_meta( $ticket_id, '_tribe_tickets_meta_enabled', true );
?>

<?php if ( ! empty( $has_meta ) && tribe_is_truthy( $has_meta ) ) : ?>
	<table class="tribe-block__rsvp__form__attendee-meta">
		<?php
			/**
			 * Allows injection of HTML after an RSVP ticket table row
			 *
			 * @var bool|WP_Post
			 * @var Tribe__Tickets__Ticket_Object
			 */
			do_action( 'event_tickets_rsvp_after_ticket_row', tribe_events_get_ticket_event( $ticket_id ), $ticket );
		?>
	</table>
<?php endif;