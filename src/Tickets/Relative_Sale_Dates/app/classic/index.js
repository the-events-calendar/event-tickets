/**
 * The sales window and sale price options of the classic ticket form: binds each part of the script to the form and
 * the editor events it follows.
 *
 * @since TBD
 */

/**
 * External dependencies
 */
import { addAction } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import '../zone-data';
import { updateDateLimit } from './date-limits';
import {
	LENGTH_KINDS,
	updateHelperText,
	updatePriceColumn,
	updateTicketsList,
	updateUnitNames,
	updateWindowLength,
	updateWindowLengths,
} from './helper-updates';
import { writeRule } from './rule';
import { checkWindow, checkWindows, endsEdit, showServerError, typing, validateWindow } from './validation';
import { CLASSIC_SALES_WINDOW, CLASSIC_WINDOW_KINDS, getFieldSelector } from './window-kinds';

/**
 * The TEC event fields the event dates are read from.
 *
 * @since TBD
 *
 * @type {string}
 */
const EVENT_FIELDS = '#EventStartDate, #EventStartTime, #EventEndDate, #EventEndTime, #allDayCheckbox, #event-timezone';

/**
 * The number fields of the relative boundaries of every window, whose unit names follow the number typed.
 *
 * @since TBD
 *
 * @type {string}
 */
const RELATIVE_VALUE_FIELDS = CLASSIC_WINDOW_KINDS.flatMap( ( kind ) =>
	[ 'start', 'end' ].map( ( key ) => `#${ kind.fieldPrefix }_${ key }_value` )
).join( ', ' );

/**
 * Updates everything that follows the event dates once an event field changes.
 *
 * @since TBD
 *
 * @return {void}
 */
function onEventChange() {
	CLASSIC_WINDOW_KINDS.forEach( checkWindow );
	updateHelperText();
	updateWindowLengths();
	updateTicketsList();
	updatePriceColumn();
}

/**
 * Updates everything that follows the event dates once the editor replaces the panels.
 *
 * @since TBD
 *
 * @return {void}
 */
function onPanelsRefreshed() {
	// The timepicker set up fires `change` before the dependency script disables the date a Now start hides.
	CLASSIC_WINDOW_KINDS.forEach( checkWindow );
	updateUnitNames();
	updateHelperText();
	updateWindowLengths();
	updateTicketsList();
	updatePriceColumn();
}

/*
 * `tickets.js` fires `pre-save-ticket.tribe` and `additionalValidation.tribe` on `#tribetickets` right before it
 * saves the ticket. The handlers are bound on that element rather than delegated from the document: Event Tickets Plus
 * stops the save event from bubbling, and the element itself is not replaced when the panels are.
 */
jQuery( () => {
	const tickets = jQuery( '#tribetickets' );

	tickets.on( 'pre-save-ticket.tribe', () =>
		CLASSIC_WINDOW_KINDS.forEach( ( kind ) => writeRule( document, kind ) )
	);
	CLASSIC_WINDOW_KINDS.forEach( ( kind ) =>
		tickets.on( 'additionalValidation.tribe', ( event, valid ) => validateWindow( kind, event, valid ) )
	);
	tickets.on( 'change.tribe', 'select', ( event ) => updateDateLimit( document, event.target.id ) );
	onPanelsRefreshed();
} );

jQuery( document ).on( 'change', EVENT_FIELDS, onEventChange );
CLASSIC_WINDOW_KINDS.forEach( ( kind ) => {
	jQuery( document ).on( 'input', getFieldSelector( kind ), () => {
		typing.type();
		checkWindows( kind );
	} );
	jQuery( document ).on( 'change', getFieldSelector( kind ), ( event ) => {
		if ( endsEdit( event ) ) {
			typing.settle();
		}

		checkWindows( kind );
	} );
} );
jQuery( document ).on( 'change input', getFieldSelector( CLASSIC_SALES_WINDOW ), updateHelperText );
LENGTH_KINDS.forEach( ( kind ) =>
	jQuery( document ).on( 'change input', getFieldSelector( kind ), () => updateWindowLength( kind ) )
);
jQuery( document ).on( 'change input', RELATIVE_VALUE_FIELDS, updateUnitNames );

addAction( 'tec.tickets.admin.panels.refreshed', 'tec.tickets.relativeSaleDates', onPanelsRefreshed );
addAction( 'tec.tickets.admin.ticketSaveFailed', 'tec.tickets.relativeSaleDates', showServerError );
// A date picked from the calendar ends the edit of its field.
addAction( 'tec.tickets.admin.ticketDatePicked', 'tec.tickets.relativeSaleDates', ( input ) => {
	if ( input?.matches?.( getFieldSelector( CLASSIC_SALES_WINDOW ) ) ) {
		updateHelperText();
	}

	CLASSIC_WINDOW_KINDS.filter( ( kind ) => input?.matches?.( getFieldSelector( kind ) ) ).forEach( ( kind ) => {
		typing.settle();
		checkWindows( kind );
	} );
	LENGTH_KINDS.filter( ( kind ) => input?.matches?.( getFieldSelector( kind ) ) ).forEach( updateWindowLength );
} );
