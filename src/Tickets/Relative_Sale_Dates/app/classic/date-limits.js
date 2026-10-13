/**
 * Keeps the limits `tickets.js` puts on the classic date pickers in step with the modes of each window.
 *
 * @since TBD
 */

/**
 * Internal dependencies
 */
import { MODE_SPECIFIC } from '../rule-constants';
import { CLASSIC_WINDOW_KINDS } from './window-kinds';

/**
 * The pairs of date pickers that limit each other, with the mode select of the window end each picker belongs to.
 *
 * @since TBD
 *
 * @type {Array<{start: {mode: string, date: string}, end: {mode: string, date: string}}>}
 */
const DATE_PAIRS = CLASSIC_WINDOW_KINDS.map( ( kind ) => {
	const getEnd = ( key ) => ( { mode: `${ kind.fieldPrefix }_${ key }_mode`, date: kind.dateFields[ key ].date } );

	return { start: getEnd( 'start' ), end: getEnd( 'end' ) };
} );

/**
 * Sets a limit of a date picker to the date of the other end of its window, or clears it when that end is not specific.
 *
 * @since TBD
 *
 * @param {Document}                     root     The document holding the form.
 * @param {string}                       pickerId The id of the date input whose picker is limited.
 * @param {string}                       option   The picker option that holds the limit, `minDate` or `maxDate`.
 * @param {{mode: string, date: string}} other    The mode select and the date input of the other end.
 *
 * @return {void}
 */
function setLimit( root, pickerId, option, other ) {
	const $picker = jQuery( root.getElementById( pickerId ) );
	const $otherPicker = jQuery( root.getElementById( other.date ) );

	if ( ! $picker.hasClass( 'hasDatepicker' ) || ! $otherPicker.hasClass( 'hasDatepicker' ) ) {
		return;
	}

	const isSpecific = MODE_SPECIFIC === root.getElementById( other.mode ).value;

	$picker.datepicker( 'option', option, isSpecific ? $otherPicker.datepicker( 'getDate' ) : null );
}

/**
 * Updates the limit the other date picker of a pair takes from the end whose mode changed.
 *
 * `tickets.js` limits each picker to the date picked in the other one. An end that leaves the specific mode hides its
 * date, which is then no part of the window, so the limit it put on the other picker goes; it comes back with the mode.
 *
 * @since TBD
 *
 * @param {Document} root   The document holding the form.
 * @param {string}   modeId The id of the mode select that changed.
 *
 * @return {void}
 */
export function updateDateLimit( root, modeId ) {
	DATE_PAIRS.forEach( ( { start, end } ) => {
		if ( modeId === start.mode ) {
			setLimit( root, end.date, 'minDate', start );
		} else if ( modeId === end.mode ) {
			setLimit( root, start.date, 'maxDate', end );
		}
	} );
}
