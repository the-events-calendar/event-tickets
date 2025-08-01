import { addAction, addFilter, applyFilters } from '@wordpress/hooks';
import CapacityForm from './capacity-form';
import { storeName } from './store';
import { currentProviderSupportsSeating } from './store/compatibility';
import { select } from '@wordpress/data';
import Seats from './dashboard-actions/seats';
import { filterCapacityTableMappedProps } from './capacity-table';
import {
	disableConfirmInTicketDashboard,
	disableTicketSelection,
	filterButtonIsDisabled,
	filterHeaderDetails,
	filterSeatedTicketsAvailabilityMappedProps,
	filterSetBodyDetails,
	filterSettingsFields,
	filterTicketIsAsc,
	removeAllActionsFromTicket,
	replaceSharedCapacityInput,
	setSeatTypeForTicket,
} from './hook-callbacks';

/**
 * Filters the render function of the Capacity form to add the seating options.
 *
 * @param {function(): void} renderDefaultForm The render function of the Capacity form.k
 * @param {Object} props The props passed to the Capacity form.
 *
 * @return {Function} The render function of the Capacity form with the seating options.
 */
function filterRenderCapacityForm( renderDefaultForm, props ) {
	if ( ! shouldRenderAssignedSeatingForm( props ) ) {
		return renderDefaultForm;
	}

	const { clientId } = props;

	return () => <CapacityForm renderDefaultForm={ renderDefaultForm } clientId={ clientId } />;
}

addFilter( 'tec.tickets.blocks.Ticket.Capacity.renderForm', 'tec.tickets.seating', filterRenderCapacityForm );

addFilter( 'tec.tickets.blocks.setBodyDetails', 'tec.tickets.seating', filterSetBodyDetails );

/**
 * Filters the action items of the dashboard to add the seating actions.
 *
 * @since 5.16.0
 *
 * @param {Array}  actions  The action items of the dashboard.
 * @param {string} clientId The client ID of the ticket block.
 *
 * @return {Array} The action items.
 */
function filterDashboardActions( actions, { clientId } ) {
	const hasSeats = select( storeName ).isUsingAssignedSeating( clientId );
	const layoutLocked = select( storeName ).isLayoutLocked();

	// Only show if there are seats and the post is saved.
	if ( hasSeats && layoutLocked ) {
		actions.push( <Seats /> );
	}

	return actions;
}

addFilter( 'tec.tickets.blocks.Tickets.TicketsDashboardAction.actions', 'tec.tickets.seating', filterDashboardActions );

/**
 * Filters the ticket edit action items to remove the move button for seated tickets.
 *
 * @since 5.16.0
 *
 * @param {Object[]} actions  The action items of the ticket.
 * @param {string}   clientId The client ID of the ticket block.
 *
 * @return {Array} The action items.
 */
function filterMoveButtonAction( actions, clientId ) {
	const hasSeats = select( storeName ).isUsingAssignedSeating( clientId );
	if ( ! hasSeats ) {
		return actions;
	}

	return actions.filter( ( action ) => action.key !== 'move' );
}

addFilter( 'tec.tickets.blocks.Ticket.actionItems', 'tec.tickets.seating', filterMoveButtonAction );

addFilter( 'tec.tickets.blocks.Ticket.header.detailItems', 'tec.tickets.seating', filterHeaderDetails );

addFilter(
	'tec.tickets.blocks.Tickets.CapacityTable.mappedProps',
	'tec.tickets.flexibleTickets',
	filterCapacityTableMappedProps
);

addFilter(
	'tec.tickets.blocks.Tickets.Availability.mappedProps',
	'tec.tickets.seating',
	filterSeatedTicketsAvailabilityMappedProps
);

addFilter( 'tribe.editor.ticket.isAsc', 'tec.tickets.seating', filterTicketIsAsc );

addFilter( 'tec.tickets.blocks.Tickets.Settings.Fields', 'tec.tickets.seating', filterSettingsFields );

addAction( 'tec.tickets.blocks.ticketUpdated', 'tec.tickets.seating', setSeatTypeForTicket );

addAction( 'tec.tickets.blocks.ticketCreated', 'tec.tickets.seating', setSeatTypeForTicket );

addFilter( 'tec.tickets.blocks.confirmButton.isDisabled', 'tec.tickets.seating', filterButtonIsDisabled );

addFilter(
	'tec.tickets.blocks.Tickets.TicketsDashboardAction.mappedProps',
	'tec.tickets.seating',
	disableConfirmInTicketDashboard
);

addFilter( 'tec.tickets.blocks.Ticket.actionItems', 'tec.tickets.seating', removeAllActionsFromTicket );

addFilter( 'tec.tickets.blocks.Ticket.isSelected', 'tec.tickets.seating', disableTicketSelection );

addFilter(
	'tec.tickets.blocks.Tickets.CapacityTable.sharedCapacityInput',
	'tec.tickets.seating',
	replaceSharedCapacityInput
);

/**
 * Checks if the assigned seating form should be rendered.
 *
 * @since 5.24.1
 *
 * @param props {Object} The block props.
 * @return {boolean}
 */
function shouldRenderAssignedSeatingForm( props ) {
	// When the provider does not support seating, we render the default form.
	if ( ! currentProviderSupportsSeating() ) {
		return false;
	}

	// When no license, we DO NOT render the radios General vs Seating.
	if ( 'no-license' === select( storeName ).getServiceStatus() ) {
		return false;
	}

	const { clientId } = props;

	const hasSeats = select( storeName ).isUsingAssignedSeating( clientId );
	const isLayoutLocked = select( storeName ).isLayoutLocked( clientId );
	const currentLayoutId = select( storeName ).getCurrentLayoutId();

	// If not using assigned seating, don't show seating form
	if ( ! hasSeats ) {
		return false;
	}

	// If we have tickets but no seating layout, don't show seating form
	if ( isLayoutLocked && ! currentLayoutId ) {
		return false;
	}

	// In all other seating scenarios, show the seating form
	return applyFilters( 'tec.tickets.blocks.Ticket.renderSeatingForm', true, props );
}
