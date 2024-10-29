const {
	decimalSeparator,
	decimalNumbers,
	thousandSeparator,
	position,
	symbol,
} = window.tec.tickets.seating.currency;

/**
 * Formats a value to a currency string not including the currency symbol.
 *
 * @since 5.16.0
 *
 * @param {number} value The value to format.
 *
 * @return {string} The formatted value, without the currency symbol.
 */
export function formatValue(value) {
	const [units, decimals] = value.toString().split('.');
	const formattedDecimals = decimals
		? '.' +
		  Number('.' + decimals)
				.toPrecision(decimalNumbers)
				.toString()
				.slice(2)
		: '';
	return (
		units
			.toString()
			// Replace the '.' with the decimal separator.
			.replace(/\./g, decimalSeparator)
			// Add the thousand separator.
			.replace(/\B(?=(\d{3})+(?!\d))/g, thousandSeparator) +
		formattedDecimals
	);
}

/**
 * Formats a number with currency and symbol following the settings in the Tickets Commerce settings.
 *
 * @since 5.16.0
 *
 * @param {number} value The value to format.
 *
 * @return {string} The formatted value including the currency symbol.
 */
export function formatWithCurrency(value) {
	const [units, decimals] = value.toString().split('.');
	const formattedDecimals = decimals
		? '.' +
		  Number('.' + decimals)
				.toPrecision(decimalNumbers)
				.toString()
				.slice(2)
		: '';
	const valueString =
		units
			.toString()
			// Replace the '.' with the decimal separator.
			.replace(/\./g, decimalSeparator)
			// Add the thousand separator.
			.replace(/\B(?=(\d{3})+(?!\d))/g, thousandSeparator) +
		formattedDecimals;

	return position === 'prefix'
		? `${symbol}${valueString}`
		: `${valueString}${symbol}`;
}

window.tec = window.tec || {};
window.tec.tickets.seating = window.tec.tickets.seating || {};
window.tec.tickets.seating.currency = {
	...(window.tec.tickets.seating.currency || {}),
	formatValue,
	formatWithCurrency
};
