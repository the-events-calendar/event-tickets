<?php
/**
 * Block: Attendees List
 * Description
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/attendees/description.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link    https://m.tri.be/1amp Help article for RSVP & Ticket template files.
 *
 * @since   4.9.2
 * @since   4.11.3
 *
 * @version TBD Updated docblock vars and removed unnecessary second retrieving of Post ID.
 *
 * @var Tribe__Tickets__Editor__Template $this       Template object.
 * @var int                              $post_id    [Global] The current Post ID to which tickets are attached.
 * @var array                            $attributes [Global] Attendee block's attributes (such as Title above block).
 * @var array                            $attendees  [Global] List of attendees with attendee data.
 * @var array                            $attendees  List of attendees and their data.
 */

$display_subtitle = $this->attr( 'displaySubtitle' );

if ( is_bool( $display_subtitle ) && ! $display_subtitle ) {
	return;
}

$attendees_total = count( $attendees );

$message = _n( 'One person is attending %2$s', '%d people are attending %s', $attendees_total, 'event-tickets' );
?>
<p>
	<?php echo esc_html(
		sprintf(
			$message,
			$attendees_total,
			get_the_title( $post_id )
		)
	); ?>
</p>
