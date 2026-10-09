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
	SALE_PRICE_MAX_VALUE,
	UNIT_DAYS,
	UNIT_HOURS,
	UNIT_MINUTES,
	UNIT_WEEKS,
} from './rule-constants';

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
 * @property {string}                            id                The kind's ID, as the server names it.
 * @property {string}                            openStartMode     The start mode that opens the window at once.
 * @property {string|null}                       openStartValue    What the server stores an open start as, or `null`
 *                                                                 when it leaves the ticket's own start, the date
 *                                                                 the form sends.
 * @property {number}                            maxValue          The highest number a relative boundary accepts.
 * @property {number[]}                          units             The units a relative boundary accepts, in seconds.
 * @property {string[]}                          anchors           The event dates a relative boundary may be counted
 *                                                                 from, the one a kind that takes no anchor implies
 *                                                                 first.
 * @property {boolean}                           takesAnchor       Whether a relative boundary names the event date
 *                                                                 it is counted from.
 * @property {string|null}                       precision         `day` for a kind the server keeps as whole days,
 *                                                                 or `null` for one it keeps as instants.
 * @property {LengthText|null}                   lengthText        The text that says how long the window lasts, or
 *                                                                 `null` for a kind that shows none.
 * @property {{start: string[], end: string[]}}  modes             The modes each boundary accepts.
 * @property {boolean}                           specificNeedsDate Whether a specific boundary sent without a date is
 *                                                                 rejected, rather than left without one.
 * @property {WindowKind|null}                   parent            The window this one must start inside, or `null`.
 * @property {string}                            defaultsKey       The key of the boundaries the Ticket block script
 *                                                                 is localized with for a ticket without a rule.
 * @property {{start: string, end: string}|null} newModes          The modes a new rule opens on in the Ticket block,
 *                                                                 or `null` to keep the localized defaults' own.
 * @property {string}                            requestKey        The field of the Ticket block request that carries
 *                                                                 the rule, as JSON.
 * @property {string|undefined}                  emptyValue        What the Ticket block request carries for a draft
 *                                                                 without a rule, or `undefined` to carry nothing.
 * @property {StoredRuleReader}                  readStored        The rule a ticket from the block editor tickets
 *                                                                 REST API was stored with: `null` for none, and
 *                                                                 `undefined` for a window the ticket does not have.
 * @property {function( Object ): boolean}       isAnswered        Whether a ticket from that REST API says what was
 *                                                                 stored for the window.
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
	openStartMode: MODE_DEFAULT,
	openStartValue: null,
	maxValue: MAX_VALUE,
	units: Object.freeze( [ UNIT_MINUTES, UNIT_HOURS, UNIT_DAYS, UNIT_WEEKS ] ),
	anchors: Object.freeze( [ ANCHOR_START, ANCHOR_END ] ),
	takesAnchor: true,
	precision: null,
	lengthText: null,
	modes: Object.freeze( {
		start: Object.freeze( [ MODE_DEFAULT, MODE_RELATIVE, MODE_SPECIFIC ] ),
		end: Object.freeze( [ MODE_DEFAULT, MODE_RELATIVE, MODE_SPECIFIC ] ),
	} ),
	specificNeedsDate: true,
	parent: null,
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
	openStartMode: MODE_NOW,
	openStartValue: '',
	maxValue: SALE_PRICE_MAX_VALUE,
	units: Object.freeze( [ UNIT_DAYS, UNIT_WEEKS ] ),
	anchors: Object.freeze( [ ANCHOR_START ] ),
	takesAnchor: false,
	precision: 'day',
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
	parent: SALES_WINDOW,
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
