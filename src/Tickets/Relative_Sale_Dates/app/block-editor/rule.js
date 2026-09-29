/**
 * The rule the Ticket block's sales window options show, and the relative number they keep.
 *
 * @since TBD
 */

/**
 * Internal dependencies
 */
import { MAX_VALUE, MIN_VALUE, MODE_DEFAULT, MODE_SPECIFIC } from '../rule-constants';
import { getLocalizedData } from './localized-data';

/** @typedef {import( '../sale-window' ).SaleWindowEnd} SaleWindowEnd */
/** @typedef {import( '../sale-window' ).SaleWindowRule} SaleWindowRule */

/**
 * Builds the rule the options show for a ticket, with relative values on every end so a switch to a relative mode
 * finds them.
 *
 * @since TBD
 *
 * @param {SaleWindowRule|null|undefined} rule The ticket's draft rule: `undefined` for a new ticket, `null` for one
 *                                             saved without a rule.
 *
 * @return {SaleWindowRule} The rule the options show.
 */
export function getFormRule( rule ) {
	const { defaults } = getLocalizedData();
	const withMode = ( key, mode ) => ( { ...defaults[ key ], mode } );

	/*
	 * A new ticket opens now and closes when the event starts; a ticket saved without a rule keeps the dates it was
	 * saved with, as a specific window.
	 */
	if ( undefined === rule ) {
		return { start: withMode( 'start', MODE_DEFAULT ), end: withMode( 'end', MODE_DEFAULT ) };
	}

	if ( null === rule ) {
		return { start: withMode( 'start', MODE_SPECIFIC ), end: withMode( 'end', MODE_SPECIFIC ) };
	}

	// A stored end that is not relative has only its mode; a draft one keeps the values the admin left.
	const withRelativeValues = ( key ) => ( { ...defaults[ key ], ...rule[ key ] } );

	return { start: withRelativeValues( 'start' ), end: withRelativeValues( 'end' ) };
}

/**
 * Reads the relative number the admin typed as an integer from 1 to 60.
 *
 * A cleared field stays empty, so the admin can type a new number; the server rejects a rule saved with it.
 *
 * @since TBD
 *
 * @param {string} typed The number as the field reports it.
 *
 * @return {number|string} The number, within 1 to 60, or an empty string for a cleared field.
 */
export function toRelativeValue( typed ) {
	const value = parseInt( typed, 10 );

	if ( Number.isNaN( value ) ) {
		return '';
	}

	return Math.min( MAX_VALUE, Math.max( MIN_VALUE, value ) );
}
