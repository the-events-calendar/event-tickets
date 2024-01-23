<?php
/**
 * @var Tribe__Tickets__Ticket_Object[]    $tickets_on_sale  The Tickets on sale.
 * @var Tribe__Tickets__Tickets_Handler    $handler          The Tickets handler.
 * @var Tribe__Tickets__Commerce__Currency $currency         The currency handler.
 * @var Tribe__Tickets__Editor__Template   $tickets_template Event Tickets Editor template.
 * @var string                             $series_permalink The permalink to the series page.
 */

use TEC\Tickets\Flexible_Tickets\Series_Passes\Editor;
use TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes;

if ( empty( $tickets_on_sale ) ) {
	return;
}

// Sort the tickets by type.
$ticket_types = [
	'rsvp'                     => [
		'header'  => esc_html_x( 'RSVP', 'RSVP ticket type header in frontend ticket form', 'event-tickets' ),
		'tickets' => [],
	],
	'default'                  => [
		'header'  => sprintf(
		// Translators: %1$s is the event label singular, %2$s is the ticket label plural; i.e. "Event Tickets".
			esc_html_x(
				'%1$s %2$s',
				'Default ticket type header in frontend ticket form',
				'event-tickets'
			),
			tribe_get_event_label_singular(),
			tribe_get_ticket_label_plural( 'frontend_tickets_list_header' )
		),
		'tickets' => [],
	],
	Series_Passes::TICKET_TYPE => [
        'header'           => tec_tickets_get_series_pass_plural_uppercase('frontend_tickets_list_header'),
        'tickets'          => [],
        'header_link'      => $series_permalink,
        'header_link_text' => tribe(Editor::class)->get_header_link_text(),
	],
];

foreach ( $tickets_on_sale as $ticket ) {
	$type = $ticket->type() ?? 'default';
	if ( ! isset( $ticket_types[ $type ]['tickets'] ) ) {
		$ticket_types[ $type ]['tickets'] = [];
	}
	$ticket_types[ $type ]['tickets'][] = $ticket;
}

foreach ( $ticket_types as $ticket_type => $data ) {
	if ( ! count( $data['tickets'] ) ) {
		continue;
	}


	$ticket_type_header = $data['header'];
	$header_link        = $data['header_link'] ?? '';
	$header_link_text   = $data['header_link_text'] ?? '';

	?>
	<div class="tribe-tickets__ticket-type-header__wrapper">
		<h3 class="tribe-common-h5 tribe-common-h--alt tribe-tickets__ticket-type-title">
			<?php echo esc_html( $ticket_type_header ); ?>
		</h3>

		<?php if ( ! empty( $header_link ) ): ?>
			<span class="tribe-tickets__ticket-type-title__link">
				<a href="<?php echo esc_url( $header_link ); ?>" target="_blank">
					<?php echo esc_html( $header_link_text ); ?>
				</a>
			</span>
		<?php endif; ?>
	</div>

	<?php
	foreach ( $data['tickets'] as $key => $ticket ) {
		$available_count = $ticket->available();

		/**
		 * Allows hiding of "unlimited" to be toggled on/off conditionally.
		 *
		 * @since 4.11.1
		 * @since 5.0.3 Added $ticket parameter.
		 *
		 * @var bool                          $show_unlimited  Whether to show the "unlimited" text.
		 * @var int                           $available_count The quantity of Available tickets based on the Attendees number.
		 * @var Tribe__Tickets__Ticket_Object $ticket          The ticket object.
		 */
		$show_unlimited = apply_filters( 'tribe_tickets_block_show_unlimited_availability', true, $available_count, $ticket );

		$has_shared_cap = $handler->has_shared_capacity( $ticket );

		$tickets_template->template(
			'v2/tickets/item',
			[
				'ticket'              => $ticket,
				'key'                 => $key,
				'data_available'      => 0 === $handler->get_ticket_max_purchase( $ticket->ID ) ? 'false' : 'true',
				'has_shared_cap'      => $has_shared_cap,
				'data_has_shared_cap' => $has_shared_cap ? 'true' : 'false',
				'currency_symbol'     => $currency->get_currency_symbol( $ticket->ID, true ),
				'show_unlimited'      => (bool) $show_unlimited,
				'available_count'     => $available_count,
				'is_unlimited'        => - 1 === $available_count,
				'max_at_a_time'       => $handler->get_ticket_max_purchase( $ticket->ID ),
			]
		);
	}
}
