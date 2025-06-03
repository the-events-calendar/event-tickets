import getCountriesByCurrency from './get-countries-by-currency';

/**
 * Interface for gateway availability result.
 */
interface GatewayAvailability {
	stripe: boolean;
	square: boolean;
	paypal: boolean;
}

/**
 * Determine payment gateway availability based on currency.
 *
 * @since TBD
 *
 * @param {string} currencyValue      The currency code to check.
 * @param {Record<string, object>} countries  The countries data object.
 *
 * @return {GatewayAvailability} Object with availability flags for each gateway.
 */
const determineGatewayAvailability = (
	currencyValue: string,
	countries: Record<string, {
		currency: string;
		has_paypal?: boolean;
		has_square?: boolean;
		has_stripe?: boolean;
		name?: string;
	}>
): GatewayAvailability => {
	// Get all countries that use this currency
	const matchingCountries = getCountriesByCurrency(currencyValue, countries);

	if (matchingCountries.length === 0) {
		// If no countries found, enable all by default
		return {
			stripe: true,
			square: true,
			paypal: true
		};
	}

	// Check if ANY country with this currency has each payment method enabled
	return {
		stripe: matchingCountries.some(country => country.has_stripe !== false),
		square: matchingCountries.some(country => country.has_square !== false),
		paypal: matchingCountries.some(country => country.has_paypal !== false)
	};
};

export default determineGatewayAvailability;
