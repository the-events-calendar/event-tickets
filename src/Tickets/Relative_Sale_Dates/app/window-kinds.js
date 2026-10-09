/**
 * The kinds of window a ticket's relative sale dates describe, as the server's `Window_Kind` defines them.
 *
 * @since TBD
 */

/**
 * Internal dependencies
 */
import { UNIT_DAYS, UNIT_HOURS, UNIT_MINUTES, UNIT_WEEKS } from './rule-constants';

/**
 * @typedef {Object} WindowKind
 *
 * @property {string}   id          The kind's ID, as the server names it.
 * @property {string}   fieldPrefix The prefix of the ids of the kind's fields in the classic ticket form.
 * @property {string}   ruleFieldId The id of the hidden field that carries the kind's rule, as JSON, to the server.
 * @property {boolean}  takesAnchor Whether a relative boundary names the event date it is counted from.
 * @property {number[]} units       The units a relative boundary accepts, in seconds.
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
	takesAnchor: true,
	units: Object.freeze( [ UNIT_MINUTES, UNIT_HOURS, UNIT_DAYS, UNIT_WEEKS ] ),
} );

/**
 * The ticket's sale price window, whose relative boundaries are always counted from the event start.
 *
 * @since TBD
 *
 * @type {WindowKind}
 */
export const SALE_PRICE_WINDOW = Object.freeze( {
	id: 'sale_price',
	fieldPrefix: 'ticket_sale',
	ruleFieldId: 'ticket_sale_price_relative',
	takesAnchor: false,
	units: Object.freeze( [ UNIT_DAYS, UNIT_WEEKS ] ),
} );

/**
 * Every kind, the sales window first.
 *
 * @since TBD
 *
 * @type {WindowKind[]}
 */
export const WINDOW_KINDS = Object.freeze( [ SALES_WINDOW, SALE_PRICE_WINDOW ] );
