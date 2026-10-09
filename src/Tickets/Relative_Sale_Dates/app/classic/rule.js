/**
 * Writes the rule of each window of the classic ticket form into the field the form submits.
 *
 * @since TBD
 */

/**
 * Internal dependencies
 */
import { MODE_RELATIVE } from '../rule-constants';
import { SALES_WINDOW } from '../window-kinds';

/** @typedef {import( '../sale-window' ).SaleWindowEnd} SaleWindowEnd */
/** @typedef {import( '../sale-window' ).SaleWindowRule} SaleWindowRule */
/** @typedef {import( '../window-kinds' ).WindowKind} WindowKind */

/**
 * Reads one end of a window from its fields.
 *
 * The fields hold strings; the server only accepts a relative `value` and `unit` that are integers. A kind that takes no
 * anchor counts every relative end from the event start, so its end carries none.
 *
 * @since TBD
 *
 * @param {Document}   root The document holding the form.
 * @param {WindowKind} kind The kind of the window.
 * @param {string}     key  The end of the window, `start` or `end`.
 *
 * @return {SaleWindowEnd} The end of the window.
 */
function readEnd( root, kind, key ) {
	const field = ( name ) => root.getElementById( `${ kind.fieldPrefix }_${ key }_${ name }` ).value;
	const mode = field( 'mode' );

	if ( MODE_RELATIVE !== mode ) {
		return { mode };
	}

	const end = {
		mode,
		value: parseInt( field( 'value' ), 10 ),
		unit: parseInt( field( 'unit' ), 10 ),
	};

	if ( kind.takesAnchor ) {
		end.anchor = field( 'anchor' );
	}

	return end;
}

/**
 * Reads the rule the fields of a window express.
 *
 * @since TBD
 *
 * @param {Document}   root                The document holding the form.
 * @param {WindowKind} [kind=SALES_WINDOW] The kind of the window.
 *
 * @return {SaleWindowRule} The rule.
 */
export function readRule( root, kind = SALES_WINDOW ) {
	return {
		start: readEnd( root, kind, 'start' ),
		end: readEnd( root, kind, 'end' ),
	};
}

/**
 * Writes the rule the fields of a window express into the hidden field the form submits.
 *
 * Does nothing when the form has no fields for the window, as on a ticket they do not apply to.
 *
 * @since TBD
 *
 * @param {Document}   root                The document holding the form.
 * @param {WindowKind} [kind=SALES_WINDOW] The kind of the window.
 *
 * @return {void}
 */
export function writeRule( root, kind = SALES_WINDOW ) {
	const ruleField = root.getElementById( kind.ruleFieldId );

	if ( ! ruleField ) {
		return;
	}

	ruleField.value = JSON.stringify( readRule( root, kind ) );
}
