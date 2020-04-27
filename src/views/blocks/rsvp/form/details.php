<?php
/**
 * Block: RSVP
 * Form Details
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/rsvp/form/details.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link    {INSERT_ARTICLE_LINK_HERE}
 *
 * @since   4.9
 * @since   4.11.5 Corrected the template override instructions in template comments.
 * @since   4.12.0 Add $post_id to filter for hiding opt-outs.
 *
 * @version 4.12.0
 */

$this->template( 'blocks/rsvp/form/name', [ 'ticket' => $ticket ] );
$this->template( 'blocks/rsvp/form/email', [ 'ticket' => $ticket ] );
$this->template( 'blocks/rsvp/form/opt-out', [ 'ticket' => $ticket, 'post_id' => $post_id ] );
