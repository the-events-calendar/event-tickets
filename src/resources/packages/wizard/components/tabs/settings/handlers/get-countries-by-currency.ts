/**
 * Retrieves all countries that use a specific currency.
 *
 * @since TBD
 *
 * @param {string} currencyValue     The currency code to filter by.
 * @param {Record<string, object>} countries The countries data object to search in.
 *
 * @return {Array<object>} Array of country objects that use the specified currency.
 */
const getCountriesByCurrency = (
	currencyValue: string,
	countries: Record<string, {
		currency: string;
		has_paypal?: boolean;
		has_square?: boolean;
		has_stripe?: boolean;
		name?: string;
	}>
): Array<{
	currency: string;
	has_paypal?: boolean;
	has_square?: boolean;
	has_stripe?: boolean;
	name?: string;
}> => {
	if (!currencyValue) {
		return [];
	}

	// Find all countries that use this currency
	return Object.entries(countries)
		.filter(([_, country]) => country.currency && country.currency === currencyValue)
		.map(([_, country]) => country);
};

export default getCountriesByCurrency;
