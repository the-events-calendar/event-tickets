/**
 * The kinds of window a ticket's relative sale dates describe, as the server's `Window_Kind` defines them.
 *
 * @since TBD
 */

/**
 * Internal dependencies
 */
import {
	ANCHOR_END,
	ANCHOR_START,
	MAX_VALUE,
	MODE_DEFAULT,
	MODE_NOW,
	UNIT_DAYS,
	UNIT_HOURS,
	UNIT_MINUTES,
	UNIT_WEEKS,
} from './rule-constants';

/**
 * @typedef {Object} DateFields
 *
 * @property {string}      date The id of the classic form field of the boundary's date.
 * @property {string|null} time The id of the classic form field of the boundary's time, or `null` for a kind kept by
 *                              the day.
 */

/**
 * @typedef {Object} WindowKind
 *
 * @property {string}                               id             The kind's ID, as the server names it.
 * @property {string}                               fieldPrefix    The prefix of the ids of the kind's fields in the
 *                                                                 classic ticket form.
 * @property {string}                               ruleFieldId    The id of the hidden field that carries the kind's
 *                                                                 rule, as JSON, to the server.
 * @property {string}                               openStartMode  The start mode that opens the window at once.
 * @property {string|null}                          openStartValue What the server stores an open start as, or `null`
 *                                                                 when it leaves the ticket's own start, the date the
 *                                                                 form sends.
 * @property {number}                               maxValue       The highest number a relative boundary accepts.
 * @property {number[]}                             units          The units a relative boundary accepts, in seconds.
 * @property {string[]}                             anchors        The event dates a relative boundary may be counted
 *                                                                 from, the one a kind that takes no anchor implies
 *                                                                 first.
 * @property {boolean}                              takesAnchor    Whether a relative boundary names the event date it
 *                                                                 is counted from.
 * @property {string|null}                          precision      `day` for a kind the server keeps as whole days, or
 *                                                                 `null` for one it keeps as instants.
 * @property {{start: DateFields, end: DateFields}} dateFields     The classic form fields of each boundary's date.
 * @property {string|null}                          lengthId       The id of the element that says how long the window
 *                                                                 lasts, or `null` for a kind that shows none.
 */

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
} );

/**
 * The ticket's sale price window, whose relative boundaries are always counted from the event start.
 *
 * Its relative boundaries take 1 to 30 days or weeks, and the server stores an open start empty, as a sale price that
 * has started.
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
} );

/**
 * Every kind, the sales window first.
 *
 * @since TBD
 *
 * @type {WindowKind[]}
 */
export const WINDOW_KINDS = Object.freeze( [ SALES_WINDOW, SALE_PRICE_WINDOW ] );
