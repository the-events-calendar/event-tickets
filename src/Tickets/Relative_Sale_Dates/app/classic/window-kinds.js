/**
 * The window kinds as the classic ticket form uses them: the shared kinds, with the ids of their fields.
 *
 * @since TBD
 */

/**
 * Internal dependencies
 */
import { isSalePriceKept } from '../php-compare';
import { SALE_PRICE_WINDOW, SALES_WINDOW } from '../window-kinds';

/** @typedef {import( '../window-kinds' ).WindowKind} WindowKind */

/**
 * @typedef {Object} DateFields
 *
 * @property {string}      date The id of the field of the boundary's date.
 * @property {string|null} time The id of the field of the boundary's time, or `null` for a kind kept by the day.
 */

/**
 * @typedef {Object} ClassicWindowKindFields
 *
 * @property {string}                               fieldPrefix    The prefix of the ids of the kind's fields.
 * @property {string}                               ruleFieldId    The id of the hidden field that carries the kind's
 *                                                                 rule, as JSON, to the server.
 * @property {{start: DateFields, end: DateFields}} dateFields     The fields of each boundary's date.
 * @property {string|null}                          lengthId       The id of the element that says how long the window
 *                                                                 lasts, or `null` for a kind that shows none.
 * @property {string}                               errorId        The id of the element that shows the window's error.
 * @property {string|null}                          enabledFieldId The id of the field that adds the window to the
 *                                                                 ticket, or `null` for a window every ticket has.
 * @property {function( Document ): boolean}        isEnabled      Whether a save of the form keeps the window, so the
 *                                                                 server judges it.
 * @property {ClassicWindowKind|null}               parent         The window this one must start inside, as the form
 *                                                                 holds it, or `null`.
 */

/** @typedef {WindowKind & ClassicWindowKindFields} ClassicWindowKind */

/**
 * The ids of the fields the sale price is kept by.
 *
 * @since TBD
 *
 * @type {{enabled: string, price: string, regularPrice: string}}
 */
const SALE_PRICE_FIELDS = Object.freeze( {
	enabled: 'ticket_add_sale_price',
	price: 'ticket_sale_price',
	regularPrice: 'ticket_price',
} );

/**
 * Returns whether a save of the form keeps the sale price, as the server judges it.
 *
 * @since TBD
 *
 * @param {Document} root The document holding the form.
 *
 * @return {boolean} Whether the sale price is kept.
 */
function isSalePriceSaved( root ) {
	const value = ( id ) => root.getElementById( id )?.value ?? '';

	return isSalePriceKept(
		Boolean( root.getElementById( SALE_PRICE_FIELDS.enabled )?.checked ),
		value( SALE_PRICE_FIELDS.price ),
		value( SALE_PRICE_FIELDS.regularPrice )
	);
}

/**
 * The ticket's sales window, as the classic form holds it.
 *
 * @since TBD
 *
 * @type {ClassicWindowKind}
 */
export const CLASSIC_SALES_WINDOW = Object.freeze( {
	...SALES_WINDOW,
	fieldPrefix: 'ticket_sales',
	ruleFieldId: 'ticket_relative_sale_dates',
	dateFields: Object.freeze( {
		start: Object.freeze( { date: 'ticket_start_date', time: 'ticket_start_time' } ),
		end: Object.freeze( { date: 'ticket_end_date', time: 'ticket_end_time' } ),
	} ),
	lengthId: null,
	errorId: 'ticket_sales_window_error',
	enabledFieldId: null,
	isEnabled: () => true,
} );

/**
 * The ticket's sale price window, as the classic form holds it.
 *
 * @since TBD
 *
 * @type {ClassicWindowKind}
 */
export const CLASSIC_SALE_PRICE_WINDOW = Object.freeze( {
	...SALE_PRICE_WINDOW,
	parent: CLASSIC_SALES_WINDOW,
	fieldPrefix: 'ticket_sale',
	ruleFieldId: 'ticket_sale_price_relative',
	dateFields: Object.freeze( {
		start: Object.freeze( { date: 'ticket_sale_start_date', time: null } ),
		end: Object.freeze( { date: 'ticket_sale_end_date', time: null } ),
	} ),
	lengthId: 'ticket_sale_price_length',
	errorId: 'ticket_sale_price_error',
	enabledFieldId: SALE_PRICE_FIELDS.enabled,
	isEnabled: isSalePriceSaved,
} );

/**
 * Every kind, the sales window first, in the order the server judges them.
 *
 * @since TBD
 *
 * @type {ClassicWindowKind[]}
 */
export const CLASSIC_WINDOW_KINDS = Object.freeze( [ CLASSIC_SALES_WINDOW, CLASSIC_SALE_PRICE_WINDOW ] );

/**
 * Gets the selector of every field a window of a kind is read from: the mode and relative fields of each boundary, the
 * fields of its specific date, and the field that adds the window to the ticket.
 *
 * @since TBD
 *
 * @param {ClassicWindowKind} kind The kind of the window.
 *
 * @return {string} The selector.
 */
export function getFieldSelector( kind ) {
	const ruleFields = kind.takesAnchor ? [ 'mode', 'value', 'unit', 'anchor' ] : [ 'mode', 'value', 'unit' ];

	return [ 'start', 'end' ]
		.flatMap( ( key ) => [
			...ruleFields.map( ( field ) => `${ kind.fieldPrefix }_${ key }_${ field }` ),
			kind.dateFields[ key ].date,
			kind.dateFields[ key ].time,
		] )
		.concat( kind.enabledFieldId )
		.filter( Boolean )
		.map( ( id ) => `#${ id }` )
		.join( ', ' );
}
