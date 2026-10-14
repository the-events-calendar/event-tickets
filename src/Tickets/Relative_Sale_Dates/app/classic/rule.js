/**
 * Writes the sales window rule of the classic ticket form into the field the form submits.
 *
 * @since TBD
 */

/** @typedef {import( '../sale-window' ).SaleWindowEnd} SaleWindowEnd */
/** @typedef {import( '../sale-window' ).SaleWindowRule} SaleWindowRule */

const MODE_RELATIVE = 'relative';

/**
 * The id of the hidden field that carries the rule, as JSON, to the server.
 *
 * @since TBD
 *
 * @type {string}
 */
const RULE_FIELD_ID = 'ticket_relative_sale_dates';

/**
 * Reads one end of the sales window from its fields.
 *
 * The fields hold strings; the server only accepts a relative `value` and `unit` that are integers.
 *
 * @since TBD
 *
 * @param {Document} root The document holding the form.
 * @param {string}   key  The end of the window, `start` or `end`.
 *
 * @return {SaleWindowEnd} The end of the window.
 */
function readEnd( root, key ) {
	const field = ( name ) => root.getElementById( `ticket_sales_${ key }_${ name }` ).value;
	const mode = field( 'mode' );

	if ( MODE_RELATIVE !== mode ) {
		return { mode };
	}

	return {
		mode,
		value: parseInt( field( 'value' ), 10 ),
		unit: parseInt( field( 'unit' ), 10 ),
		anchor: field( 'anchor' ),
	};
}

/**
 * Reads the rule the sales window fields express.
 *
 * @since TBD
 *
 * @param {Document} root The document holding the form.
 *
 * @return {SaleWindowRule} The rule.
 */
export function readRule( root ) {
	return {
		start: readEnd( root, 'start' ),
		end: readEnd( root, 'end' ),
	};
}

/**
 * Writes the rule the sales window fields express into the hidden field the form submits.
 *
 * Does nothing when the form has no sales window fields, as on a ticket they do not apply to.
 *
 * @since TBD
 *
 * @param {Document} root The document holding the form.
 *
 * @return {void}
 */
export function writeRule( root ) {
	const ruleField = root.getElementById( RULE_FIELD_ID );

	if ( ! ruleField ) {
		return;
	}

	ruleField.value = JSON.stringify( readRule( root ) );
}
