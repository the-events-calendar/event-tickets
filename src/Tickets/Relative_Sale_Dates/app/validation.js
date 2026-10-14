/**
 * Checks a sales window before the ticket is saved.
 *
 * @since TBD
 */

/** @typedef {import( 'moment' ).Moment} Moment */

/**
 * The sales end is not after the sales start.
 *
 * @since TBD
 *
 * @type {string}
 */
export const SALES_END_BEFORE_START = 'sales_end_before_start';

/**
 * Returns the error for a sales window, or `null` when there is none.
 *
 * Returns a message key rather than text: each editor maps it to its own translated string. Without both dates the
 * window cannot be judged, so it is reported valid.
 *
 * @since TBD
 *
 * @param {Moment|null} start The sales start, or `null` when there is no date for it.
 * @param {Moment|null} end   The sales end, or `null` when there is no date for it.
 *
 * @return {string|null} The message key, or `null` when the window is valid.
 */
export function getSaleWindowError( start, end ) {
	if ( ! start || ! end || start.isBefore( end ) ) {
		return null;
	}

	return SALES_END_BEFORE_START;
}
