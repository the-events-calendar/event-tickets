<?php
/**
 * Block: Attendees List
 * Gravatar
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/attendees/gravatar.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link    https://m.tri.be/1amp Help article for RSVP & Ticket template files.
 *
 * @since   4.9
 * @version 4.9.4
 *
 * @var Tribe__Tickets__Editor__Template $this       Template object.
 * @var int                              $post_id    [Global] The current Post ID to which tickets are attached.
 * @var array                            $attributes [Global] Attendee block's attributes (such as Title above block).
 * @var array                            $attendees  [Global] List of attendees with attendee data.
 * @var array                            $attendee   A single attendee's data.
 */

echo get_avatar( $attendee['purchaser_email'], 60, '', $attendee['purchaser_name'] );
