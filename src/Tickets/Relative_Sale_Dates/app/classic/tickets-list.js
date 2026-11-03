/**
 * Writes the sale dates a ticket row of the classic tickets list shows.
 *
 * @since TBD
 */

/**
 * External dependencies
 */
import moment from 'moment';

/**
 * Internal dependencies
 */
import { MODE_DEFAULT, MODE_RELATIVE } from '../rule-constants';
import { toWallClockDate } from './helper-text';

/** @typedef {import( 'moment' ).Moment} Moment */

/**
 * @typedef {Object} ListSettings
 *
 * @property {string} format       The date and time format of the tickets list, in PHP date format.
 * @property {Object} dateSettings The translated day and month names `DateFormatter` formats with.
 */

/**
 * Formats a sale date in the tickets list format.
 *
 * @since TBD
 *
 * @param {Moment}       date     The date, whose wall-clock time is the one to show.
 * @param {ListSettings} settings The list date format.
 *
 * @return {string} The formatted date.
 */
function formatDate( date, settings ) {
	const formatter = new window.DateFormatter( { dateSettings: settings.dateSettings } );

	return formatter.formatDate( toWallClockDate( date ), settings.format );
}

/**
 * Reads a stored sale date, in UTC so no clock change moves its wall-clock time.
 *
 * @since TBD
 *
 * @param {string} date The date, `YYYY-MM-DD HH:mm:ss`.
 *
 * @return {Moment} The date.
 */
function readStoredDate( date ) {
	return moment.utc( date, 'YYYY-MM-DD HH:mm:ss' );
}

/**
 * Returns the sale dates text of a ticket row, as the server writes it: the start date and time, a dash, and the end
 * date and time.
 *
 * A relative end takes the date it works out to, and a default end the event start; a default start and a specific
 * end keep the date stored for the ticket.
 *
 * @since TBD
 *
 * @param {import( '../sale-window' ).SaleWindowRule}          rule       The ticket's rule.
 * @param {import( '../sale-window' ).ResolvedSaleWindow|null} saleWindow The window the rule resolves to for the
 *                                                                        event dates in the form, or `null` when they
 *                                                                        cannot be read.
 * @param {{start: string, end: string}}                       stored     The stored sale dates, `YYYY-MM-DD HH:mm:ss`;
 *                                                                        the end is empty when the ticket has none.
 * @param {ListSettings}                                       settings   The list date format.
 *
 * @return {string} The sale dates text.
 */
export function getListText( rule, saleWindow, stored, settings ) {
	const followsEvent = ( key, modes ) => saleWindow && saleWindow[ key ] && modes.includes( rule[ key ].mode );
	const start = followsEvent( 'start', [ MODE_RELATIVE ] ) ? saleWindow.start : readStoredDate( stored.start );
	const storedEnd = stored.end ? readStoredDate( stored.end ) : null;
	const end = followsEvent( 'end', [ MODE_RELATIVE, MODE_DEFAULT ] ) ? saleWindow.end : storedEnd;

	return `${ formatDate( start, settings ) } - ${ end ? formatDate( end, settings ) : '' }`;
}
