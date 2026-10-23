/**
 * Recurring event tickets in the block editor's Tickets block.
 *
 * On a recurring event the Tickets block shows its dashboard and "Add a Ticket": the server stores a ticket saved
 * on a recurring event as a recurring event ticket. The RSVP block is not filtered.
 *
 * @since TBD
 */
import { select } from '@wordpress/data';
import { addFilter } from '@wordpress/hooks';
import { __, _x, sprintf } from '@wordpress/i18n';
import { hasRecurrenceRules } from '@moderntribe/common/utils/recurrence';
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
 * Names, in the ticket form, the type of a ticket saved on a recurring event, which is never a standard ticket there.
 *
 * Runs after Flexible Tickets, which describes a standard ticket of an event in a Series.
 *
 * @since TBD
 *
 * @param {Object} mappedProps           The props of the ticket form's type.
 * @param {Object} context               The context of the filter.
 * @param {Object} context.state         The state of the Tickets block.
 * @param {Object} context.ticketDetails The ticket being edited.
 *
 * @return {Object} The props.
 */
export function nameRecurringTicketType( mappedProps, { state, ticketDetails } ) {
	if ( 'recurring' !== ticketDetails?.type && ! hasRecurrenceRules( state ) ) {
		return mappedProps;
	}

	const postTypeLabel =
		window.tribe_editor_config?.post?.labels?.singular_name?.toLowerCase() || __( 'event', 'event-tickets' );

	return {
		...mappedProps,
		typeName: sprintf(
			// Translators: %s is the singular label for a ticket.
			_x( 'Recurring Event %s', 'The ticket type name of a recurring event ticket.', 'event-tickets' ),
			TICKET_LABELS?.ticket?.singular ?? __( 'Ticket', 'event-tickets' )
		),
		typeDescription: sprintf(
			// Translators: %s is the post type name in human readable form.
			_x(
				'Sold for each date of this recurring %s.',
				'The ticket type description of a recurring event ticket.',
				'event-tickets'
			),
			postTypeLabel
		),
	};
}

/**
 * Whether the event being edited recurs, as its rules stand in the editor, saved or not.
 *
 * Reads the Tickets block's store from the window, where common keeps the one store every bundle shares: this
 * bundle's copy of the store module would make a second one.
 *
 * @since TBD
 *
 * @return {boolean} Whether the event recurs.
 */
function eventRecurs() {
	const store = window.__tribe_common_store__;

	return Boolean( store && hasRecurrenceRules( store.getState() ) );
}

/**
 * Sends a new ticket on a recurring event as a recurring event ticket, as the classic editor's form does.
 *
 * The server would otherwise decide by the event as it is saved, and a new event's recurrence is only saved with it.
 * A ticket already saved keeps its type.
 *
 * @since TBD
 *
 * @param {FormData} body     The body of the request that saves the ticket.
 * @param {string}   clientId The client ID of the ticket's block.
 *
 * @return {FormData} The body.
 */
export function sendRecurringTicketType( body, clientId ) {
	const isNew = ! select( 'core/block-editor' ).getBlockAttributes( clientId )?.hasBeenCreated;

	if ( isNew && eventRecurs() ) {
		body.append( 'ticket_type', 'recurring' );
	}

	return body;
}

/**
 * Keeps Seating's choice between General Admission and Assigned Seating out of the ticket form on a recurring event,
 * as recurring event tickets cannot be seated.
 *
 * @since TBD
 *
 * @param {boolean} render Whether Seating renders its capacity choice.
 *
 * @return {boolean} Whether it does.
 */
export function keepSeatingOffRecurringEvents( render ) {
	return render && ! eventRecurs();
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
addFilter( 'tec.tickets.blocks.Tickets.Type.mappedProps', 'tec.tickets.recurringTickets', nameRecurringTicketType, 20 );
addFilter(
	'tec.tickets.blocks.Ticket.renderSeatingForm',
	'tec.tickets.recurringTickets',
	keepSeatingOffRecurringEvents,
	20
);
addFilter( 'tec.tickets.blocks.setBodyDetails', 'tec.tickets.recurringTickets', sendRecurringTicketType );
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
