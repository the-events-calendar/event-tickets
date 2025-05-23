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
import SingleGatewayContent from './single-gateway-content';
import PaymentRadioOptions from './payment-radio-options';
import CurrencySelector from './currency-selector';
import PaymentSelector from './payment-selector';
import {
	getCountriesByCurrency,
	checkCountrySingleGateway,
	determineGatewayAvailability,
	handleCurrencyChange,
	handleConnect,
	handleNextTab,
} from './handlers';

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

	// Check if the selected country has only one payment gateway option
	const singleGateway = countryCode ? checkCountrySingleGateway(countryCode, countries) : null;

	// Initialize currency based on country if possible
	const initialCurrency = currency ||
		(countryCode && countries[countryCode]?.currency) ||
		countryCurrency ||
		'USD';

	const initialGateways = determineGatewayAvailability(initialCurrency, countries);

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

	const handlePaymentOptionChanged = ( selected ) => {
		setPaymentOption(selected);
		updateSettings({ paymentOption: selected });
	};

	useEffect(() => {
		// Update UI state when country changes
		if (countryCode) {
			const gateway = checkCountrySingleGateway(countryCode, countries);
			setSkipPaymentsTab(!!gateway);

			// If there's only one gateway for this country, set it
			if (gateway) {
				handlePaymentOptionChanged(gateway);
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
				handlePaymentOptionChanged(firstAvailableGateway);
			}
		}
	}, [paymentOption, paymentGateways]);

	useEffect(() => {
		updateSettings({ paymentOption });
	}, [paymentOption, updateSettings]);

	useEffect(() => {
		setCurrency(currency || countryCurrency);
	}, [currency, countryCurrency]);

	// Wrapper for handleConnect
	const onConnect = async (gateway: string) => {
		await handleConnect({
			gateway,
			currencyCode,
			actionNonce,
			wpNonce,
			getSettings,
			updateSettings,
			setConnectionStatus,
			apiEndpoint: API_ENDPOINT,
		});
	};

	// Wrapper for handleNextTab
	const onNextTab = () => {
		handleNextTab({
			currencyCode,
			updateSettings,
			skipPaymentsTab,
			moveToNextTab,
			paymentOption,
			paymentsTabExists,
			paymentsTabAdded,
			setPaymentsTabAdded,
			addTab,
			reorderTabs,
			skipToNextTab,
		});
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

	const tabSettings = {
		currency: currencyCode,
		paymentOption,
		currentTab: 1,
		connectionStatus,
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
						<SingleGatewayContent
							singleGateway={singleGateway}
							connectionStatus={connectionStatus}
						/>
					)}

					<CurrencySelector
						currencies={currencies}
						currencyCode={currencyCode}
						onCurrencyChange={onCurrencyChange}
						hasCountryWithSingleGateway={hasCountryWithSingleGateway}
					/>

					{ ! hasCountryWithSingleGateway && (
						<PaymentSelector
							paymentGateways={paymentGateways}
							paymentOption={paymentOption}
							onPaymentOptionChange={handlePaymentOptionChanged}
						/>
					)}
				</div>

				{/* Display buttons based on context */}
				<div className="tec-tickets-onboarding__tab-actions">
					{/* Show the appropriate navigation button based on context */}
					{!hasCountryWithSingleGateway ? (
						/* Standard next button when there's no single gateway */
						<NextButton
							moveToNextTab={onNextTab}
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
									onConnect={() => onConnect(singleGateway)}
									onContinue={onNextTab}
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
