<?php
/**
 * Block: Tickets
 * Content
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/tickets/item/content.php
 *
 * See more documentation about our views templating system.
 *
 * @link http://m.tri.be/1amp
 *
 * @since TBD
 * @version TBD
 */

$context = [
	'ticket' => $this->get( 'ticket' ),
	'key'    => $this->get( 'key' ),
];

$this->template( 'v2/tickets/item/content/title', $context );

$this->template( 'v2/tickets/item/content/description', $context );

$this->template( 'v2/tickets/item/extra', $context );
