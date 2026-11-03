/**
 * Compares the values an editor sends as the server's PHP compares them.
 *
 * @since 5.31.0
 */

/**
 * A numeric string as PHP's `is_numeric()` reads one: decimal only, with an optional sign, fraction and exponent, and
 * whitespace around it. `Number()` also reads hexadecimal, binary and octal literals, which PHP compares as strings.
 *
 * @since 5.31.0
 *
 * @type {RegExp}
 */
const NUMERIC = /^\s*[+-]?(\d+(\.\d*)?|\.\d+)([eE][+-]?\d+)?\s*$/;

/**
 * Returns whether one value an editor sends is at least another, as PHP compares the two strings: as numbers when both
 * are numeric, and as strings otherwise.
 *
 * @since 5.31.0
 *
 * @param {string} value The value to compare.
 * @param {string} other The value to compare it with.
 *
 * @return {boolean} Whether `value` is at least `other`.
 */
export function isAtLeast( value, other ) {
	return NUMERIC.test( value ) && NUMERIC.test( other ) ? Number( value ) >= Number( other ) : value >= other;
}

/**
 * Returns whether a save keeps the sale price, as the server's `Window_Kind::is_saved_with()` judges it, the way
 * `Ticket::keeps_sale_price()` keeps it: added, lower than the price, and not negative.
 *
 * @since 5.31.0
 *
 * @param {boolean} isAdded      Whether the form adds a sale price to the ticket.
 * @param {string}  salePrice    The sale price the form sends.
 * @param {string}  regularPrice The ticket price the form sends.
 *
 * @return {boolean} Whether the sale price is kept.
 */
export function isSalePriceKept( isAdded, salePrice, regularPrice ) {
	// The server reads a sign only on a numeric sale price; it strips one from any other value.
	const isNegative = NUMERIC.test( salePrice ) && Number( salePrice ) < 0;

	return isAdded && ! isAtLeast( salePrice, regularPrice ) && ! isNegative;
}
