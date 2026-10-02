/**
 * Reads and writes the legacy ticket store the Ticket block runs on.
 *
 * The actions and selectors come from the Ticket block's own script at runtime, so this bundle does not carry a second
 * copy of the legacy ticket code. The store is common's shared one.
 *
 * @since TBD
 */

const TICKETS_COMMERCE = 'TEC\\Tickets\\Commerce\\Module';

/**
 * Returns the legacy ticket actions and selectors.
 *
 * @since TBD
 *
 * @return {{actions: Object, selectors: Object}} The actions and selectors.
 */
function getTicketData() {
	return window.tribe.tickets.data.blocks;
}

/**
 * Returns whether the tickets block sells its tickets through Tickets Commerce, the only provider the rule applies to.
 *
 * The admin can change the provider while editing, so it is read from the store rather than from localized data.
 *
 * @since TBD
 *
 * @return {boolean} Whether the provider is Tickets Commerce.
 */
export function isTicketsCommerce() {
	return (
		TICKETS_COMMERCE === getTicketData().selectors.getTicketsProvider( window.__tribe_common_store__.getState() )
	);
}

/**
 * Marks a ticket as changed, which enables its Create or Update button.
 *
 * @since TBD
 *
 * @param {string} clientId The client ID of the ticket block.
 *
 * @return {void}
 */
export function markTicketChanged( clientId ) {
	window.__tribe_common_store__.dispatch( getTicketData().actions.setTicketHasChanges( clientId, true ) );
}

/**
 * Clears the error the legacy ticket code keeps for a sales duration whose specific dates do not start before they end.
 *
 * @since TBD
 *
 * @param {string} clientId The client ID of the ticket block.
 *
 * @return {void}
 */
export function clearTicketDurationError( clientId ) {
	window.__tribe_common_store__.dispatch( getTicketData().actions.setTicketHasDurationError( clientId, false ) );
}

/**
 * Returns whether the legacy checks would let a ticket be created or updated, leaving aside its sales duration error.
 *
 * @since TBD
 *
 * @param {Object} state    The legacy ticket state.
 * @param {string} clientId The client ID of the ticket block.
 *
 * @return {boolean} Whether the ticket passes every other legacy check.
 */
export function isTicketReadyBesidesDuration( state, clientId ) {
	const { selectors } = getTicketData();
	const props = { clientId };

	return (
		! selectors.isTicketDisabled( state, props ) &&
		Boolean( selectors.getTicketHasChanges( state, props ) ) &&
		selectors.isTicketValid( state, props )
	);
}

/**
 * Subscribes to the common store, which holds the event dates The Events Calendar edits.
 *
 * @since TBD
 *
 * @param {Function} listener Called after every change to the store.
 *
 * @return {Function} The function that unsubscribes the listener.
 */
export function subscribeToCommonStore( listener ) {
	return window.__tribe_common_store__.subscribe( listener );
}

/**
 * Reads the event dates as The Events Calendar's block editor holds them.
 *
 * @since TBD
 *
 * @return {{start: string, end: string, allDay: boolean, timeZone: string}|null} The start and end,
 *                                                                              `YYYY-MM-DD HH:mm:ss`, whether the
 *                                                                              event lasts all day and its timezone,
 *                                                                              or `null` without The Events
 *                                                                              Calendar's datetime store.
 */
export function getEventDateFields() {
	const selectors = window.tec?.events?.app?.main?.data?.blocks?.datetime?.selectors;

	if ( ! selectors ) {
		return null;
	}

	const state = window.__tribe_common_store__.getState();

	return {
		start: selectors.getStart( state ),
		end: selectors.getEnd( state ),
		allDay: Boolean( selectors.getAllDay( state ) ),
		timeZone: selectors.getTimeZone( state ),
	};
}

/**
 * Reads the sale start and end a ticket's form sends, from the legacy ticket state.
 *
 * @since TBD
 *
 * @param {Object} state    The legacy ticket state.
 * @param {string} clientId The client ID of the ticket block.
 *
 * @return {{start: string|null, end: string|null}} The start and end, `YYYY-MM-DD HH:mm:ss` in the event timezone, or
 *                                                 `null` for one the form sends no date for.
 */
export function getTicketFormDates( state, clientId ) {
	const { selectors } = getTicketData();
	const props = { clientId };
	const join = ( date, time ) => ( date && time ? `${ date } ${ time }` : null );

	return {
		start: join(
			selectors.getTicketTempStartDate( state, props ),
			selectors.getTicketTempStartTime( state, props )
		),
		end: join( selectors.getTicketTempEndDate( state, props ), selectors.getTicketTempEndTime( state, props ) ),
	};
}

/**
 * Reads the sale start and end a ticket's form sends, from the common store.
 *
 * @since TBD
 *
 * @param {string} clientId The client ID of the ticket block.
 *
 * @return {{start: string|null, end: string|null}} The start and end, as `getTicketFormDates()` reads them.
 */
export function readTicketFormDates( clientId ) {
	return getTicketFormDates( window.__tribe_common_store__.getState(), clientId );
}
