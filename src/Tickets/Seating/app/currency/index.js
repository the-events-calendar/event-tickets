const {
	decimalSeparator,
	decimalNumbers,
	thousandSeparator,
	position,
	symbol,
} = window.tec.tickets.seating.currency;

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
	formatWithCurrency,
};
