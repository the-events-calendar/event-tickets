/**
 * The rule the Ticket block's window options show, the relative number they keep and the rule its requests carry.
 *
 * @since TBD
 */

/**
 * Internal dependencies
 */
import { MIN_VALUE, MODE_RELATIVE, MODE_SPECIFIC } from '../rule-constants';
import { SALES_WINDOW } from '../window-kinds';
import { getLocalizedData } from './localized-data';

/** @typedef {import( '../sale-window' ).SaleWindowEnd} SaleWindowEnd */
/** @typedef {import( '../sale-window' ).SaleWindowRule} SaleWindowRule */
/** @typedef {import( '../window-kinds' ).WindowKind} WindowKind */

/**
 * Builds the rule the options show for a ticket, with relative values on every end so a switch to a relative mode
 * finds them.
 *
 * @since TBD
 *
 * @param {SaleWindowRule|null|undefined} rule The ticket's draft rule: `undefined` for a new ticket or a window it does
 *                                             not have yet, `null` for one saved without a rule.
 * @param {WindowKind}                    kind The window kind.
 *
 * @return {SaleWindowRule} The rule the options show.
 */
export function getFormRule( rule, kind = SALES_WINDOW ) {
	const defaults = getLocalizedData()[ kind.defaultsKey ];
	const withMode = ( key, mode ) => ( { ...defaults[ key ], mode } );

	/*
	 * A new rule opens on the kind's modes, or on the defaults' own; a window saved without a rule keeps the dates it
	 * was saved with, as a specific window.
	 */
	if ( undefined === rule ) {
		const newMode = ( key ) => ( kind.newModes ? kind.newModes[ key ] : defaults[ key ].mode );

		return { start: withMode( 'start', newMode( 'start' ) ), end: withMode( 'end', newMode( 'end' ) ) };
	}

	if ( null === rule ) {
		return { start: withMode( 'start', MODE_SPECIFIC ), end: withMode( 'end', MODE_SPECIFIC ) };
	}

	// A stored end that is not relative has only its mode; a draft one keeps the values the admin left.
	const withRelativeValues = ( key ) => ( { ...defaults[ key ], ...rule[ key ] } );

	return { start: withRelativeValues( 'start' ), end: withRelativeValues( 'end' ) };
}

/**
 * Returns whether both ends of a rule are specific dates, the only window the Ticket block's date and time range picker
 * holds whole.
 *
 * @since TBD
 *
 * @param {SaleWindowRule} rule The rule the options show.
 *
 * @return {boolean} Whether both ends are specific dates.
 */
export function isSpecificWindow( rule ) {
	return MODE_SPECIFIC === rule.start.mode && MODE_SPECIFIC === rule.end.mode;
}

/**
 * Reads the relative number the admin typed as an integer from `MIN_VALUE` to the largest the window accepts.
 *
 * A cleared field stays empty, so the admin can type a new number; the server rejects a rule saved with it.
 *
 * @since TBD
 *
 * @param {string}     typed The number as the field reports it.
 * @param {WindowKind} kind  The window kind.
 *
 * @return {number|string} The number, from `MIN_VALUE` to the kind's `maxValue`, or an empty string for a cleared
 *                         field.
 */
export function toRelativeValue( typed, kind = SALES_WINDOW ) {
	const value = parseInt( typed, 10 );

	if ( Number.isNaN( value ) ) {
		return '';
	}

	return Math.min( kind.maxValue, Math.max( MIN_VALUE, value ) );
}

/**
 * Builds the form of one end of the window the server accepts.
 *
 * The server only accepts a relative `value` and `unit` that are integers, and form controls hold strings.
 *
 * @since TBD
 *
 * @param {SaleWindowEnd} end  The end of the window.
 * @param {WindowKind}    kind The window kind.
 *
 * @return {SaleWindowEnd} The end of the window, with only the keys its mode uses.
 */
function toRequestEnd( { mode, value, unit, anchor }, kind ) {
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

/**
 * Builds the rule a ticket request carries, as the server accepts it.
 *
 * @since TBD
 *
 * @param {SaleWindowRule} rule The ticket's draft rule.
 * @param {WindowKind}     kind The window kind.
 *
 * @return {SaleWindowRule} The rule, with only the keys each end's mode uses.
 */
export function toRequestRule( rule, kind = SALES_WINDOW ) {
	return { start: toRequestEnd( rule.start, kind ), end: toRequestEnd( rule.end, kind ) };
}
