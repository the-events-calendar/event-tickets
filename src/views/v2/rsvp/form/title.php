<?php
/**
 * Block: RSVP
 * Form title
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/form/title.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since TBD
 *
 * @version TBD
 */

$going = $this->get( 'going' );

if ( 'going' === $going ) {
	$this->template( 'v2/rsvp/form/going/title', [ 'rsvp' => $rsvp ] );
} else {
	$this->template( 'v2/rsvp/form/not-going/title', [ 'rsvp' => $rsvp ] );
}
