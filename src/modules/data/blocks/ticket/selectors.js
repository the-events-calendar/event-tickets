/**
 * External dependencies
 */
import { createSelector } from 'reselect';
import { find, trim } from 'lodash';
import moment from 'moment';

/**
 * Internal dependencies
 */
import * as constants from './constants';
import { CAPACITY_TYPE_OPTIONS } from './options';
import { globals } from '@moderntribe/common/utils';

const {
	UNLIMITED,
	INDEPENDENT,
	SHARED,
	TICKET_TYPES,
} = constants;
const { tickets: ticketsConfig } = globals;

export const getState = ( state ) => state;
export const getBlock = ( state ) => state.tickets.blocks.ticket;

//
// ─── BLOCK SELECTORS ────────────────────────────────────────────────────────────
//

export const getTicketsIsSelected = createSelector(
	[ getBlock ],
	( block ) => block.isSelected,
);

export const getTicketsIsSettingsOpen = createSelector(
	[ getBlock ],
	( block ) => block.isSettingsOpen,
);

export const getTicketsIsSettingsLoading = createSelector(
	[ getBlock ],
	( block ) => block.isSettingsLoading,
);

export const getTicketsProvider = createSelector(
	[ getBlock ],
	( block ) => block.provider,
);

export const getTicketsSharedCapacity = createSelector(
	[ getBlock ],
	( block ) => block.sharedCapacity,
);

export const getTicketsSharedCapacityInt = createSelector(
	[ getTicketsSharedCapacity ],
	( capacity ) => parseInt( capacity, 10 ) || 0,
);

export const getTicketsTempSharedCapacity = createSelector(
	[ getBlock ],
	( block ) => block.tempSharedCapacity,
);

export const getTicketsTempSharedCapacityInt = createSelector(
	[ getTicketsTempSharedCapacity ],
	( capacity ) => parseInt( capacity, 10 ) || 0,
);

//
// ─── HEADER IMAGE SELECTORS ─────────────────────────────────────────────────────
//

export const getTicketsHeaderImage = createSelector(
	[ getBlock ],
	( block ) => block.headerImage,
);

export const getTicketsHeaderImageId = createSelector(
	[ getTicketsHeaderImage ],
	( headerImage ) => headerImage.id,
);

export const getTicketsHeaderImageSrc = createSelector(
	[ getTicketsHeaderImage ],
	( headerImage ) => headerImage.src,
);

export const getTicketsHeaderImageAlt = createSelector(
	[ getTicketsHeaderImage ],
	( headerImage ) => headerImage.alt,
);

//
// ─── TICKETS SELECTORS ──────────────────────────────────────────────────────────
//

export const getTickets = createSelector(
	[ getBlock ],
	( block ) => block.tickets,
);

export const getTicketsAllClientIds = createSelector(
	[ getTickets ],
	( tickets ) => tickets.allClientIds,
);

export const getTicketsByClientId = createSelector(
	[ getTickets ],
	( tickets ) => tickets.byClientId,
);

export const getTicketsArray = createSelector(
	[ getTicketsAllClientIds, getTicketsByClientId ],
	( ids, tickets ) => ids.map( ( id ) => tickets[ id ] ),
);

export const getTicketsCount = createSelector(
	[ getTicketsAllClientIds ],
	( allClientIds ) => allClientIds.length,
);

export const hasTickets = createSelector(
	[ getTicketsCount ],
	( count ) => count > 0,
);

export const hasCreatedTickets = createSelector(
	[ getTicketsArray ],
	( tickets ) => tickets.reduce( ( hasCreated, ticket ) => (
		hasCreated || ticket.hasBeenCreated
	), false ),
)

export const getIndependentTickets = createSelector(
	[ getTicketsArray ],
	( tickets ) => (
		tickets.filter( ( ticket ) => ticket.details.capacityType === TICKET_TYPES[ INDEPENDENT ] )
	),
);

export const getSharedTickets = createSelector(
	[ getTicketsArray ],
	( tickets ) => (
		tickets.filter( ( ticket ) => ticket.details.capacityType === TICKET_TYPES[ SHARED ] )
	),
);

export const getSharedTicketsCount = createSelector(
	[ getSharedTickets ],
	tickets => tickets.length,
);

export const getUnlimitedTickets = createSelector(
	[ getTicketsArray ],
	( tickets ) => (
		tickets.filter( ( ticket ) => ticket.details.capacityType === TICKET_TYPES[ UNLIMITED ] )
	),
);

export const hasATicketSelected = createSelector(
	[ getTicketsArray ],
	( tickets ) => tickets.reduce( ( selected, ticket ) => (
		selected || ticket.isSelected
	), false),
);

export const getTicketsIdsInBlocks = createSelector(
	[ getTicketsArray ],
	( tickets ) => tickets.reduce( ( accumulator, ticket ) => {
		if ( ticket.ticketId !== 0 ) {
			accumulator.push( ticket.ticketId );
		}
		return accumulator;
	}, [] ),
);

//
// ─── TICKET SELECTORS ───────────────────────────────────────────────────────────
//

export const getTicketClientId = ( state, ownProps ) => ownProps.clientId;

export const getTicket = createSelector(
	[ getTicketsByClientId, getTicketClientId ],
	( tickets, clientId ) => tickets[ clientId ] || {},
);

export const getTicketSold = createSelector(
	[ getTicket ],
	( ticket ) => ticket.sold,
);

export const getTicketAvailable = createSelector(
	[ getTicket ],
	( ticket ) => ticket.available,
);

export const getTicketId = createSelector(
	[ getTicket ],
	( ticket ) => ticket.ticketId,
);

export const getTicketCurrencySymbol = createSelector(
	[ getTicket ],
	( ticket ) => ticket.currencySymbol,
);

export const getTicketCurrencyPosition = createSelector(
	[ getTicket ],
	( ticket ) => ticket.currencyPosition,
);

export const getTicketProvider = createSelector(
	[ getTicket ],
	( ticket ) => ticket.provider,
);

export const getTicketHasAttendeeInfoFields = createSelector(
	[ getTicket ],
	( ticket ) => ticket.hasAttendeeInfoFields,
);

export const getTicketIsLoading = createSelector(
	[ getTicket ],
	( ticket ) => ticket.isLoading,
);

export const getTicketIsModalOpen = createSelector(
	[ getTicket ],
	( ticket ) => ticket.isModalOpen,
);

export const getTicketHasBeenCreated = createSelector(
	[ getTicket ],
	( ticket ) => ticket.hasBeenCreated,
);

export const getTicketHasChanges = createSelector(
	[ getTicket ],
	( ticket ) => ticket.hasChanges,
);

export const getTicketHasDurationError = createSelector(
	[ getTicket ],
	( ticket ) => ticket.hasDurationError,
);

export const getTicketIsSelected = createSelector(
	[ getTicket ],
	( ticket ) => ticket.isSelected,
);

export const isTicketDisabled = createSelector(
	[
		hasATicketSelected,
		getTicketIsSelected,
		getTicketIsLoading,
		getTicketsIsSettingsOpen,
	],
	( hasSelected, isSelected, isLoading, isSettingsOpen ) => (
		( hasSelected && ! isSelected ) || isLoading || isSettingsOpen
	),
);

//
// ─── TICKET DETAILS SELECTORS ───────────────────────────────────────────────────
//

export const getTicketDetails = createSelector(
	[ getTicket ],
	( ticket ) => ticket.details || {},
);

export const getTicketTitle = createSelector(
	[ getTicketDetails ],
	( details ) => details.title,
);

export const getTicketDescription = createSelector(
	[ getTicketDetails ],
	( details ) => details.description,
);

export const getTicketPrice = createSelector(
	[ getTicketDetails ],
	( details ) => details.price,
);

export const getTicketSku = createSelector(
	[ getTicketDetails ],
	( details ) => details.sku,
);

export const getTicketStartDate = createSelector(
	[ getTicketDetails ],
	( details ) => details.startDate,
);

export const getTicketStartDateInput = createSelector(
	[ getTicketDetails ],
	( details ) => details.startDateInput,
);

export const getTicketStartDateMoment = createSelector(
	[ getTicketDetails ],
	( details ) => details.startDateMoment,
);

export const getTicketEndDate = createSelector(
	[ getTicketDetails ],
	( details ) => details.endDate,
);

export const getTicketEndDateInput = createSelector(
	[ getTicketDetails ],
	( details ) => details.endDateInput,
);

export const getTicketEndDateMoment = createSelector(
	[ getTicketDetails ],
	( details ) => details.endDateMoment,
);

export const getTicketStartTime = createSelector(
	[ getTicketDetails ],
	( details ) => details.startTime || '',
);

export const getTicketStartTimeNoSeconds = createSelector(
	[ getTicketStartTime ],
	( startTime ) => startTime.slice( 0, -3 ),
);

export const getTicketEndTime = createSelector(
	[ getTicketDetails ],
	( details ) => details.endTime || '',
);

export const getTicketEndTimeNoSeconds = createSelector(
	[ getTicketEndTime ],
	( endTime ) => endTime.slice( 0, -3 ),
);

export const getTicketStartTimeInput = createSelector(
	[ getTicketDetails ],
	( details ) => details.startTimeInput,
);

export const getTicketEndTimeInput = createSelector(
	[ getTicketDetails ],
	( details ) => details.endTimeInput,
);

export const getTicketCapacityType = createSelector(
	[ getTicketDetails ],
	( details ) => details.capacityType,
);

export const getTicketCapacity = createSelector(
	[ getTicketDetails ],
	( details ) => details.capacity,
);

export const getTicketCapacityInt = createSelector(
	[ getTicketCapacity ],
	( capacity ) => parseInt( capacity, 10 ) || 0,
);

export const isUnlimitedTicket = createSelector(
	[ getTicketDetails ],
	( details ) => details.capacityType === TICKET_TYPES[ UNLIMITED ],
);

export const isSharedTicket = createSelector(
	[ getTicketDetails ],
	( details ) => details.capacityType === TICKET_TYPES[ SHARED ],
);

export const isIndependentTicket = createSelector(
	[ getTicketDetails ],
	( details ) => details.capacityType === TICKET_TYPES[ INDEPENDENT ],
);

export const isTicketPast = createSelector(
	[ getTicketEndDateMoment ],
	( endDate ) => moment().isAfter( endDate ),
);

export const isTicketFuture = createSelector(
	[ getTicketStartDateMoment ],
	( startDate ) => moment().isBefore( startDate ),
);

export const isTicketOnSale = createSelector(
	[ getTicketHasBeenCreated, isTicketPast, isTicketFuture ],
	( hasBeenCreated, isPast, isFuture ) => (
		hasBeenCreated && ! isPast && ! isFuture
	),
);

export const hasTicketOnSale = createSelector(
	[ getTicketsAllClientIds, getState ],
	( allClientIds, state ) => allClientIds.reduce( ( onSale, clientId ) => (
		onSale || isTicketOnSale( state, { clientId } )
	), false ),
);

export const allTicketsPast = createSelector(
	[ getTicketsAllClientIds, getState ],
	( allClientIds, state ) => allClientIds.reduce( ( isPast, clientId ) => {
		const props = { clientId };
		return (
			getTicketHasBeenCreated( state, props )
				? isPast && isTicketPast( state, props )
				: isPast
		);
	}, true ),
);

export const allTicketsFuture = createSelector(
	[ getTicketsAllClientIds, getState ],
	( allClientIds, state ) => allClientIds.reduce( ( isFuture, clientId ) => {
		const props = { clientId };
		return (
			getTicketHasBeenCreated( state, props )
				? isFuture && isTicketFuture( state, props )
				: isFuture
		);
	}, true ),
);

//
// ─── TICKET TEMP DETAILS SELECTORS ──────────────────────────────────────────────
//

export const getTicketTempDetails = createSelector(
	[ getTicket ],
	( ticket ) => ticket.tempDetails || {},
);

export const getTicketTempTitle = createSelector(
	[ getTicketTempDetails ],
	( tempDetails ) => tempDetails.title,
);

export const getTicketTempDescription = createSelector(
	[ getTicketTempDetails ],
	( tempDetails ) => tempDetails.description,
);

export const getTicketTempPrice = createSelector(
	[ getTicketTempDetails ],
	( tempDetails ) => tempDetails.price,
);

export const getTicketTempSku = createSelector(
	[ getTicketTempDetails ],
	( tempDetails ) => tempDetails.sku,
);

export const getTicketTempStartDate = createSelector(
	[ getTicketTempDetails ],
	( tempDetails ) => tempDetails.startDate,
);

export const getTicketTempStartDateInput = createSelector(
	[ getTicketTempDetails ],
	( tempDetails ) => tempDetails.startDateInput,
);

export const getTicketTempStartDateMoment = createSelector(
	[ getTicketTempDetails ],
	( tempDetails ) => tempDetails.startDateMoment,
);

export const getTicketTempEndDate = createSelector(
	[ getTicketTempDetails ],
	( tempDetails ) => tempDetails.endDate,
);

export const getTicketTempEndDateInput = createSelector(
	[ getTicketTempDetails ],
	( tempDetails ) => tempDetails.endDateInput,
);

export const getTicketTempEndDateMoment = createSelector(
	[ getTicketTempDetails ],
	( tempDetails ) => tempDetails.endDateMoment,
);

export const getTicketTempStartTime = createSelector(
	[ getTicketTempDetails ],
	( tempDetails ) => tempDetails.startTime || '',
);

export const getTicketTempStartTimeNoSeconds = createSelector(
	[ getTicketTempStartTime ],
	( startTime ) => startTime.slice( 0, -3 ),
);

export const getTicketTempEndTime = createSelector(
	[ getTicketTempDetails ],
	( tempDetails ) => tempDetails.endTime || '',
);

export const getTicketTempEndTimeNoSeconds = createSelector(
	[ getTicketTempEndTime ],
	( endTime ) => endTime.slice( 0, -3 ),
);

export const getTicketTempStartTimeInput = createSelector(
	[ getTicketTempDetails ],
	( tempDetails ) => tempDetails.startTimeInput,
);

export const getTicketTempEndTimeInput = createSelector(
	[ getTicketTempDetails ],
	( tempDetails ) => tempDetails.endTimeInput,
);

export const getTicketTempCapacityType = createSelector(
	[ getTicketTempDetails ],
	( tempDetails ) => tempDetails.capacityType,
);

export const getTicketTempCapacity = createSelector(
	[ getTicketTempDetails ],
	( tempDetails ) => tempDetails.capacity,
);

export const getTicketTempCapacityInt = createSelector(
	[ getTicketTempCapacity ],
	( capacity ) => parseInt( capacity, 10 ) || 0,
);

export const getTicketTempCapacityTypeOption = createSelector(
	[ getTicketTempCapacityType ],
	( capacityType ) => find( CAPACITY_TYPE_OPTIONS, { value: capacityType } ) || {},
);

export const isTempTitleValid = createSelector(
	[ getTicketTempTitle ],
	( title ) => trim( title ) !== '',
);

export const isTempCapacityValid = createSelector(
	[ getTicketTempCapacity ],
	( capacity ) => trim( capacity ) !== '' && ! isNaN( capacity ),
);

export const isTempSharedCapacityValid = createSelector(
	[ getTicketsTempSharedCapacity ],
	( capacity ) => trim( capacity ) !== '' && ! isNaN( capacity ),
);

export const isZeroPriceValid = createSelector(
	[ getTicketTempPrice, getTicketsProvider ],
	( price, provider ) => 0 < parseInt( price, 10 ) || provider !== constants.TC_CLASS,
);

export const isTicketValid = createSelector(
	[
		getTicketTempCapacityType,
		isTempTitleValid,
		isTempCapacityValid,
		isTempSharedCapacityValid,
		isZeroPriceValid,
	],
	( capacityType, titleValid, capacityValid, sharedCapacityValid, zeroPriceValid ) => {
		if ( capacityType === TICKET_TYPES[ UNLIMITED ] ) {
			return titleValid && zeroPriceValid;
		} else if (	capacityType === TICKET_TYPES[ SHARED ] ) {
			return titleValid && sharedCapacityValid && zeroPriceValid;
		}
		return titleValid && capacityValid && zeroPriceValid;
	},
);

//
// ─── AMOUNT REDUCERS ────────────────────────────────────────────────────────────
//

export const _getTotalCapacity = ( tickets ) => tickets.reduce( ( total, ticket ) => {
	const capacity = parseInt( ticket.details.capacity, 10 ) || 0;
	return total + capacity;
}, 0 );

export const _getTotalTempCapacity = ( tickets ) => tickets.reduce( ( total, ticket ) => {
	const tempCapacity = parseInt( ticket.tempDetails.capacity, 10 ) || 0;
	return total + tempCapacity;
}, 0 );

export const _getTotalSold = ( tickets ) => tickets.reduce( ( total, ticket ) => {
	const sold = parseInt( ticket.sold, 10 ) || 0;
	return total + sold;
}, 0 );

export const _getTotalAvailable = ( tickets ) => tickets.reduce( ( total, ticket ) => {
	const available = parseInt( ticket.available, 10 ) || 0;
	return total + available;
}, 0 );

export const getIndependentTicketsCapacity = createSelector( getIndependentTickets, _getTotalCapacity );
export const getIndependentTicketsTempCapacity = createSelector( getIndependentTickets, _getTotalTempCapacity );
export const getIndependentTicketsSold = createSelector( getIndependentTickets, _getTotalSold );
export const getIndependentTicketsAvailable = createSelector( getIndependentTickets, _getTotalAvailable );

export const getSharedTicketsSold = createSelector( getSharedTickets, _getTotalSold );
export const getSharedTicketsAvailable = createSelector(
	[ getTicketsSharedCapacityInt, getSharedTicketsSold ],
	( sharedCapacity, sharedSold ) => Math.max( sharedCapacity - sharedSold, 0 ),
);

export const getIndependentAndSharedTicketsCapacity = createSelector(
	[ getIndependentTicketsCapacity, getTicketsSharedCapacityInt ],
	( independentCapacity, sharedCapacity ) => independentCapacity + sharedCapacity,
);
export const getIndependentAndSharedTicketsTempCapacity = createSelector(
	[ getIndependentTicketsTempCapacity, getTicketsTempSharedCapacityInt ],
	( independentTempCapacity, tempSharedCapacity ) => independentTempCapacity + tempSharedCapacity,
);
export const getIndependentAndSharedTicketsSold = createSelector(
	[ getIndependentTicketsSold, getSharedTicketsSold ],
	( independentSold, sharedSold ) => independentSold + sharedSold,
);
export const getIndependentAndSharedTicketsAvailable = createSelector(
	[ getIndependentTicketsAvailable, getSharedTicketsAvailable ],
	( independentAvailable, sharedAvailable ) => independentAvailable + sharedAvailable,
);

//
// ─── MISC SELECTORS ─────────────────────────────────────────────────────────────
//

export const getTicketProviders = () => {
	const tickets = ticketsConfig();
	return tickets.providers || [];
};

export const getDefaultTicketProvider = () => {
	const tickets = ticketsConfig();
	return tickets.default_provider || '';
};

export const hasValidTicketProvider = () => {
	const provider = getDefaultTicketProvider();
	return provider !== '' && provider !== constants.RSVP_CLASS;
};

export const hasMultipleTicketProviders = createSelector(
	[ getTicketProviders ],
	( providers ) => providers.length > 1,
);

export const hasTicketProviders = createSelector(
	[ getTicketProviders ],
	( providers ) => providers.length > 0,
);

export const canCreateTickets = createSelector(
	[ hasTicketProviders, hasValidTicketProvider ],
	( providers, validDefaultProvider ) => providers && validDefaultProvider
);
