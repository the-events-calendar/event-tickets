import React from 'react';
import { BaseControl } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import { __, _x } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { Button } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { SETTINGS_STORE_KEY } from '../../../data';
import { API_ENDPOINT } from '../../../data/settings/constants';
import NextButton from '../../buttons/next';
import SkipButton from '../../buttons/skip';
import GatewayConnectionButton from '../../buttons/gateway-connection';
import TicketIcon from './img/ticket';
import StripeLogo from '../payments/img/stripe';
import SquareLogo from '../payments/img/square';
import PayPalLogo from '../payments/img/paypal';
import CheckIcon from '../payments/img/check';
import ErrorIcon from '../payments/img/error';

const SettingsContent = ( { moveToNextTab, skipToNextTab, addTab, updateTab, reorderTabs } ) => {
	const [ connectionStatus, setConnectionStatus ] = useState( 'disconnected' );
	const getSettings = useSelect( ( select ) => select( SETTINGS_STORE_KEY ).getSettings );
	const countryCurrency = useSelect( ( select ) => select( SETTINGS_STORE_KEY ).getCountryCurrency() );
	const countryCode = useSelect( ( select ) => select( SETTINGS_STORE_KEY ).getSetting( 'country' ) );
	const wpNonce = useSelect( ( select ) => select( SETTINGS_STORE_KEY ).getSetting( '_wpnonce' ), [] );
	const actionNonce = useSelect( ( select ) => select( SETTINGS_STORE_KEY ).getSetting( 'action_nonce' ), [] );
	const updateSettings = useDispatch( SETTINGS_STORE_KEY ).updateSettings;

	// Get existing tabs to check if payments tab already exists
	const existingTabs = useSelect((select) => {
		const store = select(SETTINGS_STORE_KEY);
		return store.getVisibleTabs ? store.getVisibleTabs() : [];
	}, []);

	const paymentsTabExists = Array.isArray(existingTabs) && existingTabs.some(tab => tab && tab.id === 'payments');

	console.log( 'countryCode', countryCode );

	const {
		currency,
		currencies,
		countries,
		paymentOption: storedPaymentOption,
	}: {
		currency: string;
		currencies: Record<string, { symbol: string; name: string; code: string }>;
		countries: Record<string, {
			currency: string;
			has_paypal?: boolean;
			has_square?: boolean;
			has_stripe?: boolean;
			name?: string;
		}>;
		paymentOption: string;
	} = useSelect( ( select ) => {
		const store = select( SETTINGS_STORE_KEY );
		return {
			currency: store.getSetting( 'currency' ),
			currencies: store.getSetting( 'currencies' ),
			countries: store.getSetting( 'countries' ),
			paymentOption: store.getSetting( 'paymentOption' ),
		};
	}, [] );

	console.log( 'countries', countries );
	console.log( 'currencies', currencies );

	// Get ALL countries that use a given currency
	const getCountriesByCurrency = ( currencyValue: string ) => {
		if (!currencyValue) {
			return [];
		}

		// Find all countries that use this currency
		return Object.entries(countries)
			.filter(([_, country]) => country.currency && country.currency === currencyValue)
			.map(([_, country]) => country);
	};

	// Check if country only supports one payment gateway
	const checkCountrySingleGateway = (country: string) => {
		if (!country || !countries[country]) {
			return null;
		}

		const countryData = countries[country];
		const availableGateways = {
			stripe: countryData.has_stripe !== false,
			square: countryData.has_square !== false,
			paypal: countryData.has_paypal !== false,
		};

		console.log('Country data for', country, countryData);
		console.log('Available gateways', availableGateways);

		// Count how many gateways are available
		const enabledGateways = Object.entries(availableGateways)
			.filter(([_, isEnabled]) => isEnabled)
			.map(([gateway]) => gateway);

		console.log('Enabled gateways', enabledGateways);

		// If only one gateway is available, return it
		if (enabledGateways.length === 1) {
			return enabledGateways[0];
		}

		return null;
	};

	// Determine payment gateway availability based on currency
	const determineGatewayAvailability = (currencyValue: string) => {
		// Get all countries that use this currency
		const matchingCountries = getCountriesByCurrency(currencyValue);

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

	// Check if the selected country has only one payment gateway option
	const singleGateway = countryCode ? checkCountrySingleGateway(countryCode) : null;

	console.log('Single gateway detected:', singleGateway);

	// Initialize currency based on country if possible
	const initialCurrency = currency ||
		(countryCode && countries[countryCode]?.currency) ||
		countryCurrency ||
		'USD';

	const initialGateways = determineGatewayAvailability(initialCurrency);

	// Set initial payment option based on country compatibility
	const initialPaymentOption = storedPaymentOption ||
		singleGateway ||
		(initialGateways.stripe ? 'stripe' : (initialGateways.square ? 'square' : 'paypal'));

	const [ currencyCode, setCurrency ] = useState(initialCurrency);
	const [ paymentOption, setPaymentOption ] = useState(initialPaymentOption);

	// Payment gateway availability based on selected currency
	const [ paymentGateways, setPaymentGateways ] = useState(initialGateways);

	// Track if we've already added the payments tab
	const [ paymentsTabAdded, setPaymentsTabAdded ] = useState(false);

	// Determine if we should skip the payments tab completely
	const [ skipPaymentsTab, setSkipPaymentsTab ] = useState(!!singleGateway);

	useEffect(() => {
		// Update UI state when country changes
		if (countryCode) {
			const gateway = checkCountrySingleGateway(countryCode);
			setSkipPaymentsTab(!!gateway);

			// If there's only one gateway for this country, set it
			if (gateway) {
				setPaymentOption(gateway);
			}

			// Set currency for this country if available
			if (countries[countryCode]?.currency) {
				setCurrency(countries[countryCode].currency);
			}
		}
	}, [countryCode, countries]);

	useEffect(() => {
		if (!paymentOption) {
			const gatewayPriority = ['stripe', 'square', 'paypal'];
			const firstAvailableGateway = gatewayPriority.find(gateway => paymentGateways[gateway]);
			if (firstAvailableGateway) {
				setPaymentOption(firstAvailableGateway);
			}
		}
	}, [paymentOption, paymentGateways]);

	useEffect(() => {
		updateSettings({ paymentOption });
	}, [paymentOption, updateSettings]);

	// Handle currency changes
	const handleCurrencyChange = (e) => {
		const newCurrency = e.target.value;
		setCurrency(newCurrency);

		// Determine gateway availability based on the new currency
		const newGateways = determineGatewayAvailability(newCurrency);

		setPaymentGateways(newGateways);

		// Reset payment option if current one is unavailable
		const gatewayPriority = ['stripe', 'square', 'paypal'];

		if (!paymentOption || (paymentOption && !newGateways[paymentOption])) {
			const newPaymentOption = gatewayPriority.find(gateway => newGateways[gateway]) || '';
			setPaymentOption(newPaymentOption);
		}
	};

	// Handle gateway connection
	const handleConnect = async (gateway: string) => {
		setConnectionStatus('connecting');

		const connectSettings = {
			gateway: gateway,
			currency: currencyCode,
			action_nonce: actionNonce,
		};

		updateSettings(connectSettings);

		apiFetch.use(apiFetch.createNonceMiddleware(wpNonce));

		try {
			const result = await apiFetch({
				method: 'POST',
				data: {
					...getSettings(),
					gateway: gateway,
					action: 'connect',
				},
				path: API_ENDPOINT,
			});

			if (result && result.signup_url) {
				// Before redirecting, save that we've initiated connection
				updateSettings({
					connecting: true,
					currentTab: 1
				});
				window.location.href = result.signup_url;
			} else {
				setConnectionStatus('failed');
			}
		} catch (error) {
			console.error('Connection error:', error);
			setConnectionStatus('failed');
		}
	};

	// Check for connection on first load
	useEffect(() => {
		const settings = getSettings();

		// If connected via Stripe
		if (settings.stripeConnected) {
			setConnectionStatus('connected');
		}

		// If connected via Square
		if (settings.squareConnected) {
			setConnectionStatus('connected');
		}

		// If we were in the process of connecting and we're back (likely completed)
		if (settings.connecting && singleGateway) {
			setConnectionStatus('connected');

			// Reset the connecting flag
			updateSettings({ connecting: false });

			// Wait a moment to show the connected state before moving on
			const timer = setTimeout(() => {
				moveToNextTab();
			}, 1500);

			return () => clearTimeout(timer);
		}
	}, []);

	// Handle moving to next tab, potentially adding the Payments tab
	const handleNextTab = () => {
		// Save currency setting
		updateSettings({ currency: currencyCode });

		// If we should skip the payments tab due to having only one gateway option
		if (skipPaymentsTab) {
			moveToNextTab();
			return;
		}

		// If Stripe or Square is selected and we need a payment processor
		if (['stripe', 'square', 'paypal'].includes(paymentOption)) {
			// Only add the tab if it doesn't already exist
			if (!paymentsTabExists && !paymentsTabAdded) {
				// Import the payments tab dynamically
				import('../payments/tab').then((module) => {
					const PaymentsContent = module.default;

					// Add the Payments tab after the current tab (Settings)
					addTab({
						id: 'payments',
						title: __('Payments', 'event-tickets'),
						content: PaymentsContent,
						ref: React.createRef(),
						priority: 25, // Between Settings (20) and Communication (30)
						isVisible: true,
					});

					// Mark that we've added the tab
					setPaymentsTabAdded(true);

					// Reorder tabs based on priority
					reorderTabs();

					// Now move to next tab, which should be the newly added Payments tab
					moveToNextTab();
				});
			} else {
				// Tab already exists, just move to next tab
				moveToNextTab();
			}
		} else {
			// No payment gateway selected, just move to next tab
			moveToNextTab();
		}
	};

	const tabSettings = {
		currency: currencyCode,
		paymentOption,
		currentTab: 1,
	};

	// Custom radio component implementation
	const PaymentRadioOptions = () => {
		const handleChange = (value) => {
			setPaymentOption(value);
		};

		return (
			<fieldset className="components-radio-control tec-tickets-onboarding__payment-radios">
				<legend className="screen-reader-text">{ __( 'Ticket Payments', 'event-tickets' ) }</legend>

				<div className={`components-radio-control__option tec-tickets-onboarding__payment-option ${!paymentGateways.stripe ? 'disabled' : ''}`}>
					<label htmlFor="tec-tickets-payment-stripe">
						<input
							id="tec-tickets-payment-stripe"
							className="components-radio-control__input"
							type="radio"
							name="payment-option"
							value="stripe"
							checked={paymentOption === 'stripe'}
							onChange={() => handleChange('stripe')}
							disabled={!paymentGateways.stripe}
						/>
						<span className="tec-tickets-onboarding__payment-option-content">
							<span className="tec-tickets-onboarding__payment-option-label">
								{ __( 'Online', 'event-tickets' ) }
							</span>
							<span className="tec-tickets-onboarding__payment-option-provider">
								{ __( '(Powered by Stripe)', 'event-tickets' ) }
							</span>
						</span>
					</label>
				</div>

				<div className={`components-radio-control__option tec-tickets-onboarding__payment-option ${!paymentGateways.square ? 'disabled' : ''}`}>
					<label htmlFor="tec-tickets-payment-square">
						<input
							id="tec-tickets-payment-square"
							className="components-radio-control__input"
							type="radio"
							name="payment-option"
							value="square"
							checked={paymentOption === 'square'}
							onChange={() => handleChange('square')}
							disabled={!paymentGateways.square}
						/>
						<span className="tec-tickets-onboarding__payment-option-content">
							<span className="tec-tickets-onboarding__payment-option-label">
								{ __( 'Online and in-person', 'event-tickets' ) }
							</span>
							<span className="tec-tickets-onboarding__payment-option-provider">
								{ __( '(Powered by Square)', 'event-tickets' ) }
							</span>
						</span>
					</label>
				</div>
			</fieldset>
		);
	};

	// Display inline gateway setup if single gateway for country
	const SingleGatewayContent = () => {
		if (!singleGateway) {
			return null;
		}

		// Gateway-specific content configuration
		const gatewayContent = {
			stripe: {
				title: __('Stripe Payment Setup', 'event-tickets'),
				description: __('Based on your country selection, Stripe is the recommended payment processor for online payments.', 'event-tickets'),
				logo: <StripeLogo />,
				connectText: __('Connect to Stripe', 'event-tickets'),
			},
			square: {
				title: __('Square Payment Setup', 'event-tickets'),
				description: __('Based on your country selection, Square is the recommended payment processor for your region.', 'event-tickets'),
				logo: <SquareLogo />,
				connectText: __('Connect to Square', 'event-tickets'),
			},
			paypal: {
				title: __('PayPal Payment Setup', 'event-tickets'),
				description: __('Based on your country selection, PayPal is the recommended payment processor for your region.', 'event-tickets'),
				logo: <PayPalLogo />,
				connectText: __('Connect to PayPal', 'event-tickets'),
			},
		};

		const content = gatewayContent[singleGateway];
		const isConnected = connectionStatus === 'connected';

		return (
			<div className="tec-tickets-onboarding__single-gateway">
				<div className="tec-tickets-onboarding__payment-gateway">
					<div className="tec-tickets-onboarding__gateway-header">
						{content.logo && (
							<div className="tec-tickets-onboarding__gateway-logo">
								{content.logo}
							</div>
						)}
					</div>
					<p className="tec-tickets-onboarding__gateway-description">
						{content.description}
					</p>
				</div>
			</div>
		);
	};

	// Check if we have country with only one gateway to handle display logic
	const hasCountryWithSingleGateway = !!singleGateway;

	return (
		<>
			<TicketIcon />
			<div className="tec-tickets-onboarding__tab-header">
				<h1 className="tec-tickets-onboarding__tab-heading">{ __( 'Selling Tickets', 'event-tickets' ) }</h1>
				<p className="tec-tickets-onboarding__tab-subheader">
					{ __( 'Nail down the basics of your ticket setup.', 'event-tickets' ) }
				</p>
			</div>
			<div className="tec-tickets-onboarding__tab-content">
				<div className="tec-tickets-onboarding__form-wrapper">
					{hasCountryWithSingleGateway && (
						<SingleGatewayContent />
					)}

					<BaseControl
						__nextHasNoMarginBottom
						id="currency-code"
						label={ __( 'Currency', 'event-tickets' ) }
						className="tec-tickets-onboarding__form-field"
					>
						<select
							onChange={handleCurrencyChange}
							value={currencyCode}
							required
						>
							{ Object.entries( currencies ).map( ( [ key, data ] ) => (
								<option key={ key } value={ data.code }>
									{ data.name } ({ data.code })
								</option>
							) ) }
						</select>
						{hasCountryWithSingleGateway && (
							<p className="tec-tickets-onboarding__currency-notice">
								{__('Currency selected based on your country.', 'event-tickets')}
							</p>
						)}
						<span className="tec-tickets-onboarding__required-label">
							{ __( 'Currency is required.', 'event-tickets' ) }
						</span>
						<span className="tec-tickets-onboarding__invalid-label">
							{ __( 'Currency is invalid.', 'event-tickets' ) }
						</span>
					</BaseControl>

					{ ! hasCountryWithSingleGateway &&
						<BaseControl
							__nextHasNoMarginBottom
							id="payment-options"
							label={ __( 'Ticket Payments', 'event-tickets' ) }
							className="tec-tickets-onboarding__form-field tec-tickets-onboarding__payment-options"
						>
							<p className="tec-tickets-onboarding__subtitle">
								{ __( 'Choose how you\'d like to accept payments:', 'event-tickets' ) }
							</p>

							<PaymentRadioOptions />

							<p className="tec-tickets-onboarding__free-options-note">
								{ __( 'Free tickets and RSVP options are always available.', 'event-tickets' ) }
							</p>

							{!paymentGateways.square && (
								<div className="tec-tickets-onboarding__warning-notice">
									<p>
										{ __( 'Your selected currency does not support in-person payments with Square.', 'event-tickets' ) }
									</p>
								</div>
							)}
						</BaseControl>
					}
				</div>

				{/* Display buttons based on context */}
				<div className="tec-tickets-onboarding__tab-actions">
					{/* Show the appropriate navigation button based on context */}
					{!hasCountryWithSingleGateway ? (
						/* Standard next button when there's no single gateway */
						<NextButton
							moveToNextTab={handleNextTab}
							tabSettings={tabSettings}
							disabled={!paymentOption}
							onSuccess={() => {}}
						/>
					) : (
						/* Connection options for single gateway */
						<>
							{singleGateway && (
								<GatewayConnectionButton
									connectionStatus={connectionStatus}
									gatewayType={singleGateway}
									connectText={countries[countryCode] && singleGateway ?
										__(`Connect to ${singleGateway.charAt(0).toUpperCase() + singleGateway.slice(1)}`, 'event-tickets') :
										__('Connect', 'event-tickets')}
									onConnect={() => handleConnect(singleGateway)}
									onContinue={handleNextTab}
								/>
							)}

						</>
					)}
					{/* Should always show skip button */}
					<SkipButton skipToNextTab={skipToNextTab} currentTab={1} />
				</div>
			</div>
		</>
	);
};

export default SettingsContent;
