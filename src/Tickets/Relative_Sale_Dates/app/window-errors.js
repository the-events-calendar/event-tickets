/**
 * The errors a window of any kind can have, as message keys each editor maps to its own translated text.
 *
 * @since TBD
 */

/**
 * Internal dependencies
 */
import { SALES_END_BEFORE_START } from './validation';

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
export const ENDS_BEFORE_START = SALES_END_BEFORE_START;

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
