import determineGatewayAvailability from './determine-gateway-availability';

/**
 * Interface for HandleCurrencyChangeParams.
 */
interface HandleCurrencyChangeParams {
	e: React.ChangeEvent<HTMLSelectElement>;
	setCurrency: (currency: string) => void;
	setPaymentGateways: (gateways: any) => void;
	paymentOption: string;
	setPaymentOption: (option: string) => void;
	countries: Record<string, {
		currency: string;
		has_paypal?: boolean;
		has_square?: boolean;
		has_stripe?: boolean;
		name?: string;
	}>;
}

/**
 * Handle currency selector changes.
 *
 * @since TBD
 *
 * @param {HandleCurrencyChangeParams} params Parameters.
 */
const handleCurrencyChange = ({
	e,
	setCurrency,
	setPaymentGateways,
	paymentOption,
	setPaymentOption,
	countries,
}: HandleCurrencyChangeParams): void => {
	const newCurrency = e.target.value;
	setCurrency(newCurrency);

	// Determine gateway availability based on the new currency
	const newGateways = determineGatewayAvailability(newCurrency, countries);

	setPaymentGateways(newGateways);

	// Reset payment option if current one is unavailable
	const gatewayPriority = ['stripe', 'square', 'paypal'];

	if (!paymentOption || (paymentOption && !newGateways[paymentOption])) {
		const newPaymentOption = gatewayPriority.find(gateway => newGateways[gateway]) || '';
		setPaymentOption(newPaymentOption);
	}
};

export default handleCurrencyChange;
