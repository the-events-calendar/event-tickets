/**
 * The errors a window of any kind can have, as message keys each editor maps to the text the server localizes.
 *
 * @since TBD
 */

/**
 * The window does not end after it starts, at the precision its kind is kept at, or its rule is not one the server
 * takes.
 *
 * It is the key a sales window that ends before it starts has always had, so the editors that map that key keep it.
 *
 * @since TBD
 *
 * @type {string}
 */
export const ENDS_BEFORE_START = 'sales_end_before_start';

/**
 * A relative boundary's number is out of the range its kind takes.
 *
 * @since TBD
 *
 * @type {string}
 */
export const RELATIVE_VALUE_OUT_OF_RANGE = 'relative_value_out_of_range';

/**
 * The window starts outside its parent window.
 *
 * @since TBD
 *
 * @type {string}
 */
export const OUTSIDE_PARENT = 'outside_parent';

/**
 * A specific boundary is sent with a date that cannot be read.
 *
 * @since TBD
 *
 * @type {string}
 */
export const INVALID_DATE = 'invalid_date';

/**
 * The key of each error's text among the texts the server localizes for a kind: the server's own key for an error it
 * rejects a save with.
 *
 * @since TBD
 *
 * @type {Object<string, string>}
 */
export const ERROR_TEXT_KEYS = Object.freeze( {
	[ ENDS_BEFORE_START ]: 'endsBeforeStart',
	[ OUTSIDE_PARENT ]: 'outsideParent',
	[ RELATIVE_VALUE_OUT_OF_RANGE ]: 'valueOutOfRange',
	[ INVALID_DATE ]: 'invalidDate',
} );
