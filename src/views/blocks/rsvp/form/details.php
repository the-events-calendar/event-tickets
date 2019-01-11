<?php
/**
 * Block: RSVP
 * Form Details
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/rsvp/form/submit.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @version 4.9
 *
 */

$this->template( 'blocks/rsvp/form/name', array( 'ticket' => $ticket ) );
$this->template( 'blocks/rsvp/form/email', array( 'ticket' => $ticket ) );
$this->template( 'blocks/rsvp/form/opt-out', array( 'ticket' => $ticket ) );
