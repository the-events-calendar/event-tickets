<?php
/**
 * Block: Tickets
 * Content Description Toggle.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/tickets/item/content/description-toggle.php
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

if (
	empty( $is_modal )
	|| ! $ticket->show_description()
	|| empty( $ticket->description )
) {
	return;
}

$ticket_details_id  = 'tribe__details__content' . ( empty( $is_modal ) ? '' : '__modal' );
$ticket_details_id .= '--' . $ticket->ID;
?>
<div class="tribe-tickets__tickets-item-details-summary">
	<button
		type="button"
		class="tribe-common-b3 tribe-tickets__tickets-item-details-summary-button--more"
		aria-controls="<?php echo esc_attr( $ticket_details_id ); ?>"
		tabindex="0"
	>
		<span class="screen-reader-text tribe-common-a11y-visual-hide"><?php esc_html_e( 'Open the ticket description.', 'event-tickets' ); ?></span>
		<?php echo esc_html_x( 'More', 'Opens the ticket description', 'event-tickets' ); ?>
	</button>
	<button
		type="button"
		class="tribe-common-b3 tribe-tickets__tickets-item-details-summary-button--less"
		aria-controls="<?php echo esc_attr( $ticket_details_id ); ?>"
		tabindex="0"
	>
		<span class="screen-reader-text tribe-common-a11y-visual-hide"><?php esc_html_e( 'Close the ticket description.', 'event-tickets' ); ?></span>
		<?php echo esc_html_x( 'Less', 'Closes the ticket description', 'event-tickets' ); ?>
	</button>
</div>
