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
 * @since   TBD Corrected the template override instructions in template comments.
 *
 * @version TBD
 */

$this->template( 'blocks/rsvp/form/name', [ 'ticket' => $ticket ] );
$this->template( 'blocks/rsvp/form/email', [ 'ticket' => $ticket ] );
$this->template( 'blocks/rsvp/form/opt-out', [ 'ticket' => $ticket ] );