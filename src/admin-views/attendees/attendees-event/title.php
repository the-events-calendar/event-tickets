<?php
/**
 * Event Attendees Title template.
 *
 * @since 5.5.9
 *
 * @var \Tribe__Template          $this      Current template object.
 * @var int                       $event_id  The event/post/page id.
 * @var Tribe__Tickets__Attendees $attendees The Attendees object.
 */

/**
 * Whether we should display the "Attendees for: %s" title.
 *
 * @since 4.6.2
 * @since 4.12.1 Append the post ID to the Attendees page title and each Ticket's name.
 * @since 5.0.1 Change default to the result of `is_admin()`.
 *
 * @param boolean                   $show_title Whether to show the title.
 * @param Tribe__Tickets__Attendees $attendees  The attendees object.
 */
$show_title = apply_filters( 'tribe_tickets_attendees_show_title', is_admin(), $attendees );

if ( empty( $show_title ) ) {
	return;
}

?>
<h1>
	<?php
	echo esc_html(
		sprintf(
			// Translators: %1$s: the post/event title, %2$d: the post/event ID.
			_x( 'Attendees for: %1$s [#%2$d]', 'attendees report screen heading', 'event-tickets' ),
			get_the_title( $attendees->attendees_table->event ),
			$event_id
		)
	);
	/**
	 * Add an action to render content after text title.
	 *
	 * @since 5.1.0
	 * @since 5.1.7 Added the attendees information.
	 *
	 * @param int $event_id Post ID.
	 * @param Tribe__Tickets__Attendees $attendees The attendees object.
	 */
	do_action( 'tribe_report_page_after_text_label', $event_id, $attendees );

	?>
</h1>
