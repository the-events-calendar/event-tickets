/**
 * Compares the values an editor sends as the server's PHP compares them.
 *
 * @since TBD
 */

/**
 * Returns whether one value an editor sends is at least another, as PHP compares the two strings: as numbers when both
 * are numeric, and as strings otherwise.
 *
 * @since TBD
 *
 * @param {string} value The value to compare.
 * @param {string} other The value to compare it with.
 *
 * @return {boolean} Whether `value` is at least `other`.
 */
export function isAtLeast( value, other ) {
	const isNumeric = ( text ) => '' !== text.trim() && Number.isFinite( Number( text ) );

	return isNumeric( value ) && isNumeric( other ) ? Number( value ) >= Number( other ) : value >= other;
}
