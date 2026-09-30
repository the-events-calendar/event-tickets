/**
 * Reads the sale price rule of the classic ticket form, and writes it into the field the form submits.
 *
 * @since TBD
 */

/**
 * Internal dependencies
 */
import { MODE_RELATIVE } from '../rule-constants';

/** @typedef {import( '../sale-price-window' ).SalePriceBoundary} SalePriceBoundary */
/** @typedef {import( '../sale-price-window' ).SalePriceRule} SalePriceRule */

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
 * Reads the sale price rule the sale price fields express.
 *
 * @since TBD
 *
 * @param {Document} root The document holding the form.
 *
 * @return {SalePriceRule} The rule.
 */
export function readSalePriceRule( root ) {
	return {
		start: readBoundary( root, 'start' ),
		end: readBoundary( root, 'end' ),
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

	ruleField.value = JSON.stringify( readSalePriceRule( root ) );
}
