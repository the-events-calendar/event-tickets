/**
 * Writes the rule of each window of the classic ticket form into the field the form submits.
 *
 * @since TBD
 */

/**
 * Internal dependencies
 */
import { MODE_SPECIFIC } from '../rule-constants';
import { toRequestEnd } from '../rule-end';
import { CLASSIC_SALE_PRICE_WINDOW, CLASSIC_SALES_WINDOW } from './window-kinds';

/** @typedef {import( '../sale-window' ).SaleWindowEnd} SaleWindowEnd */
/** @typedef {import( '../sale-window' ).SaleWindowRule} SaleWindowRule */
/** @typedef {import( './window-kinds' ).ClassicWindowKind} ClassicWindowKind */

/**
 * Reads one end of a window from its fields.
 *
 * @since TBD
 *
 * @param {Document}          root The document holding the form.
 * @param {ClassicWindowKind} kind The kind of the window.
 * @param {string}            key  The end of the window, `start` or `end`.
 *
 * @return {SaleWindowEnd} The end of the window.
 */
function readEnd( root, kind, key ) {
	const field = ( name ) => root.getElementById( `${ kind.fieldPrefix }_${ key }_${ name }` )?.value;

	return toRequestEnd(
		{ mode: field( 'mode' ), value: field( 'value' ), unit: field( 'unit' ), anchor: field( 'anchor' ) },
		kind
	);
}

/**
 * Reads the rule the fields of a window express.
 *
 * @since TBD
 *
 * @param {Document}          root                        The document holding the form.
 * @param {ClassicWindowKind} [kind=CLASSIC_SALES_WINDOW] The kind of the window.
 *
 * @return {SaleWindowRule} The rule.
 */
export function readRule( root, kind = CLASSIC_SALES_WINDOW ) {
	return {
		start: readEnd( root, kind, 'start' ),
		end: readEnd( root, kind, 'end' ),
	};
}

/**
 * Returns whether the sale price window still shows the dates of a sale price saved before relative dates.
 *
 * Such a sale price opens without a rule on its two specific dates. While both ends stay specific it is saved as it
 * was, without a rule, so its dates are judged as they were then, a sale of a single day included. The box is checked
 * as the form renders only for a ticket saved with a sale price. Whether a rule is stored is read from the rule field's
 * marker: its value, and on a hidden field its default value too, changes once a save writes the rule in it.
 *
 * @since TBD
 *
 * @param {Document}          root The document holding the form.
 * @param {ClassicWindowKind} kind The kind of the window.
 *
 * @return {boolean} Whether the window keeps the sale price's own dates, without a rule.
 */
export function keepsSalePriceWithoutRule( root, kind ) {
	const ruleField = root.getElementById( kind.ruleFieldId );

	if (
		CLASSIC_SALE_PRICE_WINDOW.id !== kind.id ||
		! ruleField ||
		'0' !== ruleField.dataset.storedRule ||
		! root.getElementById( kind.enabledFieldId )?.defaultChecked
	) {
		return false;
	}

	const { start, end } = readRule( root, kind );

	return MODE_SPECIFIC === start.mode && MODE_SPECIFIC === end.mode;
}

/**
 * Writes the rule the fields of a window express into the hidden field the form submits.
 *
 * Does nothing when the form has no fields for the window, as on a ticket they do not apply to. A sale price saved
 * before relative dates, still on its own dates, is written without a rule.
 *
 * @since TBD
 *
 * @param {Document}          root                        The document holding the form.
 * @param {ClassicWindowKind} [kind=CLASSIC_SALES_WINDOW] The kind of the window.
 *
 * @return {void}
 */
export function writeRule( root, kind = CLASSIC_SALES_WINDOW ) {
	const ruleField = root.getElementById( kind.ruleFieldId );

	if ( ! ruleField ) {
		return;
	}

	ruleField.value = keepsSalePriceWithoutRule( root, kind ) ? '' : JSON.stringify( readRule( root, kind ) );
}
