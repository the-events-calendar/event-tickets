/**
 * The kinds of window a ticket's relative sale dates describe, as the server's `Window_Kind` defines them.
 *
 * @since TBD
 */

/**
 * External dependencies
 */
import { _n, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import {
	ANCHOR_END,
	ANCHOR_START,
	MAX_VALUE,
	MODE_DEFAULT,
	MODE_NOW,
	MODE_RELATIVE,
	MODE_SPECIFIC,
	UNIT_DAYS,
	UNIT_HOURS,
	UNIT_MINUTES,
	UNIT_WEEKS,
} from './rule-constants';
import { isAtLeast } from './php-compare';

/**
 * @typedef {Object} DateFields
 *
 * @property {string}      date The id of the classic form field of the boundary's date.
 * @property {string|null} time The id of the classic form field of the boundary's time, or `null` for a kind kept by
 *                              the day.
 */

/** @typedef {function( Object ): (Object|null|undefined)} StoredRuleReader */

/**
 * @typedef {Object} LengthText
 *
 * @property {function( number ): string} weeks The text for a window that lasts the given whole number of weeks.
 * @property {function( number ): string} days  The text for a window that lasts the given number of days.
 */

/**
 * @typedef {Object} WindowKind
 *
 * @property {string}                               id                The kind's ID, as the server names it.
 * @property {string}                               fieldPrefix       The prefix of the ids of the kind's fields in the
 *                                                                    classic ticket form.
 * @property {string}                               ruleFieldId       The id of the hidden field that carries the kind's
 *                                                                    rule, as JSON, to the server.
 * @property {string}                               openStartMode     The start mode that opens the window at once.
 * @property {string|null}                          openStartValue    What the server stores an open start as, or `null`
 *                                                                    when it leaves the ticket's own start, the date
 *                                                                    the form sends.
 * @property {number}                               maxValue          The highest number a relative boundary accepts.
 * @property {number[]}                             units             The units a relative boundary accepts, in seconds.
 * @property {string[]}                             anchors           The event dates a relative boundary may be counted
 *                                                                    from, the one a kind that takes no anchor implies
 *                                                                    first.
 * @property {boolean}                              takesAnchor       Whether a relative boundary names the event date
 *                                                                    it is counted from.
 * @property {string|null}                          precision         `day` for a kind the server keeps as whole days,
 *                                                                    or `null` for one it keeps as instants.
 * @property {{start: DateFields, end: DateFields}} dateFields        The classic form fields of each boundary's date.
 * @property {string|null}                          lengthId          The id of the element that says how long the
 *                                                                    window lasts, or `null` for a kind that shows
 *                                                                    none.
 * @property {LengthText|null}                      lengthText        The text that says how long the window lasts, or
 *                                                                    `null` for a kind that shows none.
 * @property {{start: string[], end: string[]}}     modes             The modes each boundary accepts.
 * @property {boolean}                              specificNeedsDate Whether a specific boundary sent without a date is
 *                                                                    rejected, rather than left without one.
 * @property {string}                               errorId           The id of the element that shows the window's
 *                                                                    error in the classic ticket form.
 * @property {WindowKind|null}                      parent            The window this one must start inside, or `null`.
 * @property {string|null}                          enabledFieldId    The id of the field that adds the window to the
 *                                                                    ticket, or `null` for a window every ticket has.
 * @property {function( Document ): boolean}        isEnabled         Whether a save of the form keeps the window, so
 *                                                                    the server judges it.
 * @property {string}                               defaultsKey       The key of the boundaries the Ticket block script
 *                                                                    is localized with for a ticket without a rule.
 * @property {{start: string, end: string}|null}    newModes          The modes a new rule opens on in the Ticket block,
 *                                                                    or `null` to keep the localized defaults' own.
 * @property {string}                               requestKey        The field of the Ticket block request that carries
 *                                                                    the rule, as JSON.
 * @property {string|undefined}                     emptyValue        What the Ticket block request carries for a draft
 *                                                                    without a rule, or `undefined` to carry nothing.
 * @property {StoredRuleReader}                     readStored        The rule a ticket from the block editor tickets
 *                                                                    REST API was stored with: `null` for none, and
 *                                                                    `undefined` for a window the ticket does not have.
 * @property {function( Object ): boolean}          isAnswered        Whether a ticket from that REST API says what was
 *                                                                    stored for the window.
 */

/**
 * The ids of the classic form fields the sale price is kept by.
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
 * Returns whether a save of the form keeps the sale price, as the server's `Window_Kind::is_saved_with()` judges it:
 * added, and lower than the price.
 *
 * @since TBD
 *
 * @param {Document} root The document holding the form.
 *
 * @return {boolean} Whether the sale price is kept.
 */
function isSalePriceKept( root ) {
	const value = ( id ) => root.getElementById( id )?.value ?? '';

	return (
		Boolean( root.getElementById( SALE_PRICE_FIELDS.enabled )?.checked ) &&
		! isAtLeast( value( SALE_PRICE_FIELDS.price ), value( SALE_PRICE_FIELDS.regularPrice ) )
	);
}

/**
 * The ticket's sales window.
 *
 * @since TBD
 *
 * @type {WindowKind}
 */
export const SALES_WINDOW = Object.freeze( {
	id: 'sales',
	fieldPrefix: 'ticket_sales',
	ruleFieldId: 'ticket_relative_sale_dates',
	openStartMode: MODE_DEFAULT,
	openStartValue: null,
	maxValue: MAX_VALUE,
	units: Object.freeze( [ UNIT_MINUTES, UNIT_HOURS, UNIT_DAYS, UNIT_WEEKS ] ),
	anchors: Object.freeze( [ ANCHOR_START, ANCHOR_END ] ),
	takesAnchor: true,
	precision: null,
	dateFields: Object.freeze( {
		start: Object.freeze( { date: 'ticket_start_date', time: 'ticket_start_time' } ),
		end: Object.freeze( { date: 'ticket_end_date', time: 'ticket_end_time' } ),
	} ),
	lengthId: null,
	lengthText: null,
	modes: Object.freeze( {
		start: Object.freeze( [ MODE_DEFAULT, MODE_RELATIVE, MODE_SPECIFIC ] ),
		end: Object.freeze( [ MODE_DEFAULT, MODE_RELATIVE, MODE_SPECIFIC ] ),
	} ),
	specificNeedsDate: true,
	errorId: 'ticket_sales_window_error',
	parent: null,
	enabledFieldId: null,
	isEnabled: () => true,
	defaultsKey: 'defaults',
	// A new ticket's sales open now and close when the event starts; the defaults hold only the relative values offered.
	newModes: Object.freeze( { start: MODE_DEFAULT, end: MODE_DEFAULT } ),
	requestKey: 'ticket[relative_sale_dates]',
	// An empty rule removes the stored one.
	emptyValue: '',
	readStored: ( ticket ) => ticket.relative_sale_dates ?? null,
	isAnswered: ( ticket ) => undefined !== ticket.relative_sale_dates,
} );

/**
 * The ticket's sale price window, whose relative boundaries are always counted from the event start.
 *
 * Its relative boundaries take 1 to 30 days or weeks, and the server stores an open start empty, as a sale price that
 * has started. It must start inside the sales window, and is judged only when the ticket keeps a sale price.
 *
 * @since TBD
 *
 * @type {WindowKind}
 */
export const SALE_PRICE_WINDOW = Object.freeze( {
	id: 'sale_price',
	fieldPrefix: 'ticket_sale',
	ruleFieldId: 'ticket_sale_price_relative',
	openStartMode: MODE_NOW,
	openStartValue: '',
	maxValue: 30,
	units: Object.freeze( [ UNIT_DAYS, UNIT_WEEKS ] ),
	anchors: Object.freeze( [ ANCHOR_START ] ),
	takesAnchor: false,
	precision: 'day',
	dateFields: Object.freeze( {
		start: Object.freeze( { date: 'ticket_sale_start_date', time: null } ),
		end: Object.freeze( { date: 'ticket_sale_end_date', time: null } ),
	} ),
	lengthId: 'ticket_sale_price_length',
	lengthText: Object.freeze( {
		weeks: ( weeks ) =>
			sprintf(
				/* translators: %d: The number of weeks the ticket sale price lasts. */
				_n( 'Tickets on sale for %d week', 'Tickets on sale for %d weeks', weeks, 'event-tickets' ),
				weeks
			),
		days: ( days ) =>
			sprintf(
				/* translators: %d: The number of days the ticket sale price lasts. */
				_n( 'Tickets on sale for %d day', 'Tickets on sale for %d days', days, 'event-tickets' ),
				days
			),
	} ),
	modes: Object.freeze( {
		start: Object.freeze( [ MODE_NOW, MODE_RELATIVE, MODE_SPECIFIC ] ),
		end: Object.freeze( [ MODE_RELATIVE, MODE_SPECIFIC ] ),
	} ),
	specificNeedsDate: false,
	errorId: 'ticket_sale_price_error',
	parent: SALES_WINDOW,
	enabledFieldId: SALE_PRICE_FIELDS.enabled,
	isEnabled: isSalePriceKept,
	defaultsKey: 'salePriceDefaults',
	newModes: null,
	requestKey: 'ticket[sale_price][relative]',
	// The server keeps the stored rule, or the dates of a sale price stored without one.
	emptyValue: undefined,
	readStored: ( ticket ) => ( ticket.sale_price_data?.enabled ? ticket.sale_price_data.relative ?? null : undefined ),
	isAnswered: ( ticket ) => undefined !== ticket.sale_price_data,
} );

/**
 * Every kind, the sales window first.
 *
 * @since TBD
 *
 * @type {WindowKind[]}
 */
export const WINDOW_KINDS = Object.freeze( [ SALES_WINDOW, SALE_PRICE_WINDOW ] );
