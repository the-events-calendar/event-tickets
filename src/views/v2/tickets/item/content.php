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
 * @link    https://m.tri.be/1amp Help article for RSVP & Ticket template files.
 *
 * @since   TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Editor__Template   $this            The template instance.
 * @var Tribe__Tickets__Ticket_Object      $ticket          Ticket Object.
 * @var int                                $key             Ticket Item index.
 * @var string                             $content         Message.
 * @var Tribe__Tickets__Commerce__Currency $currency        The Currency Object.
 * @var string                             $currency_symbol The currency symbol, e.g. '$'.
 * @var int                                $key             Ticket Item index.
 * @var WP_Post|int                        $post_id         The post object or ID.
 * @var Tribe__Tickets__Tickets            $provider        The tickets provider class.
 * @var bool                               $is_mini         True if it's in mini cart context.
 * @var int                                $max_at_a_time   The maximum quantity able to be purchased in a single Add to Cart action.
 */

if ( empty( $ticket ) ) {
	return;
}

$this->template( 'v2/tickets/item/content/title' );

$this->template( 'v2/tickets/item/content/description' );

$this->template( 'v2/tickets/item/extra' );
