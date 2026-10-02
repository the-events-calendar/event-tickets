/**
 * Writes the sale price rule of the classic ticket form into the field the form submits.
 *
 * @since TBD
 */

/**
 * Internal dependencies
 */
import { MODE_RELATIVE } from '../rule-constants';

/**
 * @typedef {Object} SalePriceBoundary
 *
 * @property {string} mode    One of `now`, `relative` or `specific`.
 * @property {number} [value] The number of units before the event starts, for a relative boundary.
 * @property {number} [unit]  The unit, in seconds: 86400 or 604800, for a relative boundary.
 */

/**
 * @typedef {Object} SalePriceRule
 *
 * @property {SalePriceBoundary} start The start of the sale price window.
 * @property {SalePriceBoundary} end   The end of the sale price window.
 */

/**
 * The id of the hidden field that carries the sale price rule, as JSON, to the server.
 *
 * @since TBD
 *
 * @type {string}
 */
const RULE_FIELD_ID = 'ticket_sale_price_relative';

/**
 * Reads one boundary of the sale price window from its fields.
 *
 * The fields hold strings; the server only accepts a relative `value` and `unit` that are integers. A relative
 * boundary is always counted from the event start, so it carries no anchor.
 *
 * @since TBD
 *
 * @param {Document} root The document holding the form.
 * @param {string}   key  The boundary, `start` or `end`.
 *
 * @return {SalePriceBoundary} The boundary.
 */
function readBoundary( root, key ) {
	const field = ( name ) => root.getElementById( `ticket_sale_${ key }_${ name }` ).value;
	const mode = field( 'mode' );

	if ( MODE_RELATIVE !== mode ) {
		return { mode };
	}

	return {
		mode,
		value: parseInt( field( 'value' ), 10 ),
		unit: parseInt( field( 'unit' ), 10 ),
	};
}

/**
 * Writes the sale price rule the sale price fields express into the hidden field the form submits.
 *
 * Does nothing when the form has no sale price options, as on a ticket they do not apply to.
 *
 * @since TBD
 *
 * @param {Document} root The document holding the form.
 *
 * @return {void}
 */
export function writeSalePriceRule( root ) {
	const ruleField = root.getElementById( RULE_FIELD_ID );

	if ( ! ruleField ) {
		return;
	}

	/** @type {SalePriceRule} */
	const rule = {
		start: readBoundary( root, 'start' ),
		end: readBoundary( root, 'end' ),
	};

	ruleField.value = JSON.stringify( rule );
}
