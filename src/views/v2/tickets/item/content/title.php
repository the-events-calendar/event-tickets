<?php
/**
 * Block: Tickets
 * Content Title
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/tickets/content/title.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://m.tri.be/1amp Help article for RSVP & Ticket template files.
 *
 * @since   TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Editor__Template   $this                The template instance.
 * @var Tribe__Tickets__Ticket_Object      $ticket              Ticket Object.
 * @var int                                $key                 Ticket Item index.
 * @var string                             $content             Message.
 * @var Tribe__Tickets__Commerce__Currency $currency            The Currency Object.
 * @var string                             $currency_symbol     The currency symbol, e.g. '$'.
 * @var int                                $key                 Ticket Item index.
 * @var WP_Post|int                        $post_id             The post object or ID.
 * @var Tribe__Tickets__Tickets            $provider            The tickets provider class.
 * @var string                             $provider_id         The tickets provider class string.
 * @var bool                               $is_mini             True if it's in mini cart context.
 * @var string                             $data_available      Boolean string.
 * @var bool                               $has_shared_cap      True if ticket has shared capacity.
 * @var string                             $data_has_shared_cap True text if ticket has shared capacity.
 * @var int                                $threshold           The threshold value to show or hide quantity available.
 * @var int                                $available_count     The quantity of Available tickets based on the Attendees number.
 * @var bool                               $show_unlimited      Whether to allow showing of "unlimited".
 * @var bool                               $is_unlimited        Whether the ticket has unlimited quantity.
 */

$no_description = ! $ticket->show_description() || empty( $ticket->description ) || $is_mini;

$title_classes = [
	'tribe-common-h7',
	'tribe-common-h6--min-medium',
	'tribe-tickets__tickets-item-content-title',
	'tribe-tickets--no-description' => $no_description,
];

$event_title_classes = [
	'tribe-common-b3',
	'tribe-tickets__tickets-item-content-subtitle',
];

?>
<div <?php tribe_classes( $title_classes ); ?> >
	<?php if ( $is_mini ) : ?>
		<div <?php tribe_classes( $event_title_classes ); ?> >
			<?php echo get_the_title( $post_id ); ?>
		</div>
	<?php endif; ?>
	<?php echo esc_html( $ticket->name ); ?>
</div>
