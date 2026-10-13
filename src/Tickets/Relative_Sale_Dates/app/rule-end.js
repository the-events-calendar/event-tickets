/**
 * Shapes the ends of a rule the way the server takes them.
 *
 * @since TBD
 */

/**
 * Internal dependencies
 */
import { MODE_RELATIVE } from './rule-constants';

/** @typedef {import( './sale-window' ).SaleWindowEnd} SaleWindowEnd */
/** @typedef {import( './window-kinds' ).WindowKind} WindowKind */

/**
 * Builds one end of a rule the server takes, from the values an editor's controls hold.
 *
 * The controls hold strings, and the server only takes a relative `value` and `unit` that are integers. A kind that
 * takes no anchor counts every relative end from the event start, so its end carries none.
 *
 * @since TBD
 *
 * @param {{mode: string, value?: (string|number), unit?: (string|number), anchor?: string}} end  The values of the end.
 * @param {WindowKind}                                                                       kind The kind of the window.
 *
 * @return {SaleWindowEnd} The end, with only the keys its mode uses.
 */
export function toRequestEnd( { mode, value, unit, anchor }, kind ) {
	if ( MODE_RELATIVE !== mode ) {
		return { mode };
	}

	return {
		mode,
		value: parseInt( value, 10 ),
		unit: parseInt( unit, 10 ),
		...( kind.takesAnchor && { anchor } ),
	};
}
