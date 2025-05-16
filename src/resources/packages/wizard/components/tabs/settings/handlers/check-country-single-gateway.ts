/**
 * Checks if a country only supports one payment gateway.
 *
 * @since TBD
 *
 * @param {string} country            The country code to check.
 * @param {Record<string, object>} countries The countries data object to check in.
 *
 * @return {string|null} The name of the single gateway or null if multiple or none are available.
 */
const checkCountrySingleGateway = (
	country: string,
	countries: Record<string, {
		currency: string;
		has_paypal?: boolean;
		has_square?: boolean;
		has_stripe?: boolean;
		name?: string;
	}>
): string | null => {
	if (!country || !countries[country]) {
		return null;
	}

	const countryData = countries[country];
	const availableGateways = {
		stripe: countryData.has_stripe !== false,
		square: countryData.has_square !== false,
		paypal: countryData.has_paypal !== false,
	};

	// Count how many gateways are available
	const enabledGateways = Object.entries(availableGateways)
		.filter(([_, isEnabled]) => isEnabled)
		.map(([gateway]) => gateway);

	// If only one gateway is available, return it
	if (enabledGateways.length === 1) {
		return enabledGateways[0];
	}

	return null;
};

export default checkCountrySingleGateway;
