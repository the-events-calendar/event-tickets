/**
 * Recurring event tickets in the block editor's Tickets block.
 *
 * On a recurring event the Tickets block shows its dashboard and "Add a Ticket": the server stores a ticket saved
 * on a recurring event as a recurring event ticket. The RSVP block is not filtered.
 *
 * @since TBD
 */
import { addFilter } from '@wordpress/hooks';
import { __, _x, sprintf } from '@wordpress/i18n';
import { TICKET_LABELS } from '../../../../modules/data/blocks/ticket/constants';

/**
 * The Tickets block's empty state on a recurring event, by the Series Passes notice it shows under the description.
 *
 * Built once per notice and post type label, as connect() remounts a component whose type changes identity.
 *
 * @since TBD
 *
 * @type {Map<Function|null, Map<string, Function>>}
 */
const recurringEmptyStates = new Map();

/**
 * Lets the Tickets block render its dashboard on a recurring event.
 *
 * @since TBD
 *
 * @param {Object} mappedProps The props of the Tickets block.
 *
 * @return {Object} The props.
 */
export function allowTicketsOnRecurringEvents( mappedProps ) {
	return { ...mappedProps, noTicketsOnRecurring: false };
}

/**
 * Returns the Tickets block's empty state on a recurring event: what its tickets are for, above the notice that says
 * where the Series Passes are sold.
 *
 * @since TBD
 *
 * @param {Function|null} SeriesPassesNotice The Series Passes notice Flexible Tickets shows there, if any.
 * @param {string}        postTypeLabel      The lowercase singular label of the post type.
 *
 * @return {Function} The component.
 */
function recurringEmptyState( SeriesPassesNotice, postTypeLabel ) {
	if ( ! recurringEmptyStates.has( SeriesPassesNotice ) ) {
		recurringEmptyStates.set( SeriesPassesNotice, new Map() );
	}

	const byLabel = recurringEmptyStates.get( SeriesPassesNotice );

	if ( ! byLabel.has( postTypeLabel ) ) {
		const RecurringEmptyState = () => (
			<>
				<div className="tickets-row-line">
					{ sprintf(
						// Translators: %1$s is the plural, lowercase label for a ticket; %2$s the post type name in human readable form.
						_x(
							'Create %1$s for every date in this recurring %2$s.',
							'The message displayed on a recurring event when it has no tickets.',
							'event-tickets'
						),
						TICKET_LABELS?.ticket?.pluralLowercase ?? __( 'tickets', 'event-tickets' ),
						postTypeLabel
					) }
				</div>
				{ SeriesPassesNotice && <SeriesPassesNotice /> }
			</>
		);

		byLabel.set( postTypeLabel, RecurringEmptyState );
	}

	return byLabel.get( postTypeLabel );
}

/**
 * Says, on a recurring event with no tickets yet, what its tickets are for, where a single event says it sells
 * standard tickets. Keeps the Series Passes notice under it.
 *
 * @since TBD
 *
 * @param {Object} mappedProps The props of the Tickets block's empty state.
 *
 * @return {Object} The props.
 */
export function describeRecurringTickets( mappedProps ) {
	if ( ! mappedProps.hasRecurrenceRules || mappedProps.hasCreatedTickets || ! mappedProps.canCreateTickets ) {
		return mappedProps;
	}

	return {
		...mappedProps,
		showWarning: true,
		Warning: recurringEmptyState( mappedProps.showWarning ? mappedProps.Warning : null, mappedProps.postTypeLabel ),
	};
}

/**
 * Offers "Add a Ticket" on a recurring event, without the message that tickets are not supported there.
 *
 * Runs after Flexible Tickets, which hides the button on a recurring event.
 *
 * @since TBD
 *
 * @param {Object}  mappedProps         The props of the dashboard's actions.
 * @param {Object}  context             The context of the filter.
 * @param {boolean} context.isRecurring Whether the event recurs.
 *
 * @return {Object} The props.
 */
export function offerTicketsOnRecurringEvents( mappedProps, { isRecurring } ) {
	if ( ! isRecurring ) {
		return mappedProps;
	}

	return { ...mappedProps, showConfirm: true, showNotSupportedMessage: false, showWarning: false };
}

/**
 * Adds, where the recurrence rules of an event with standard tickets or RSVPs would be, how to sell tickets for every
 * date instead: an existing ticket never becomes a recurring event ticket.
 *
 * @since TBD
 *
 * @param {string} message The message saying the event can't be made recurring.
 *
 * @return {string} The message, with the way to sell tickets for every date.
 */
export function explainRecurrenceNotSupported( message ) {
	return `${ message } ${ __(
		'To sell tickets for every date, create a new recurring event and add recurring event tickets there.',
		'event-tickets'
	) }`.trim();
}

addFilter(
	'tec.tickets.blocks.Tickets.Inactive.mappedProps',
	'tec.tickets.recurringTickets',
	describeRecurringTickets,
	20
);
addFilter(
	'tec.tickets.blocks.Tickets.mappedProps',
	'tec.tickets.recurringTickets',
	allowTicketsOnRecurringEvents,
	20
);
addFilter(
	'tec.tickets.blocks.Tickets.TicketsDashboardAction.mappedProps',
	'tec.tickets.recurringTickets',
	offerTicketsOnRecurringEvents,
	20
);
addFilter(
	'tec.eventsPro.recurrence.notSupportedWithTicketsMessage',
	'tec.tickets.recurringTickets',
	explainRecurrenceNotSupported
);
