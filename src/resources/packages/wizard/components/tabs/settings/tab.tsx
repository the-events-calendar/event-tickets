import React from 'react';
import { BaseControl } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import { __, _x } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { SETTINGS_STORE_KEY } from '../../../data';
import { API_ENDPOINT } from '../../../data/settings/constants';
import NextButton from '../../buttons/next';
import SkipButton from '../../buttons/skip';
import TicketIcon from './img/ticket';
import StripeLogo from '../payments/img/stripe';
import CheckIcon from '../payments/img/check';
import ErrorIcon from '../payments/img/error';
import { Button } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

const SettingsContent = ( { moveToNextTab, skipToNextTab } ) => {
	const [ connectionStatus, setConnectionStatus ] = useState( 'disconnected' );
	const getSettings = useSelect( ( select ) => select( SETTINGS_STORE_KEY ).getSettings );
	const countryCurrency = useSelect( ( select ) => select( SETTINGS_STORE_KEY ).getCountryCurrency() );
	const updateSettings = useDispatch( SETTINGS_STORE_KEY ).updateSettings;
	const {
		currency,
		currencies,
		paymentOption: storedPaymentOption,
	}: {
		currency: string;
		currencies: Record< string, { symbol: string; name: string } >;
		paymentOption: string;
	} = useSelect( ( select ) => {
		const store = select( SETTINGS_STORE_KEY );
		return {
			currency: store.getSetting( 'currency' ),
			currencies: store.getSetting( 'currencies' ),
			paymentOption: store.getSetting( 'paymentOption' ),
		};
	}, [] );

	const wpNonce = useSelect( ( select ) => select( SETTINGS_STORE_KEY ).getSetting( '_wpnonce' ), [] );
	const [ currencyCode, setCurrency ] = useState( currency || countryCurrency );
	const [ paymentOption, setPaymentOption ] = useState(storedPaymentOption || '');

	useEffect(() => {
		if (storedPaymentOption !== undefined) {
			setPaymentOption(storedPaymentOption);
		}
	}, [storedPaymentOption]);

	useEffect(() => {
		if (!paymentOption) {
			setPaymentOption('stripe');
		}
	}, [paymentOption]);

	useEffect(() => {
		updateSettings({ paymentOption });
	}, [paymentOption, updateSettings]);

	useEffect(() => {
		setCurrency(currency || countryCurrency || 'USD');
	}, [currency, countryCurrency]);

	useEffect( () => {
		const settings = getSettings();
		if ( settings.stripeConnected ) {
			setConnectionStatus( 'connected' );
		}
	}, [] );

	const tabSettings = {
		currency: currencyCode,
		paymentOption,
		currentTab: 1,
	};

	const handleConnect = async ( gateway: string ) => {
		setConnectionStatus( 'connecting' );

		updateSettings( tabSettings );

		apiFetch.use( apiFetch.createNonceMiddleware( wpNonce ) );

		const result = await apiFetch( {
			method: 'POST',
			data: {
				...getSettings(),
				gateway: gateway,
				action: 'connect',
			},
			path: API_ENDPOINT,
		} );

		updateSettings(tabSettings);

		if ( result.signup_url ) {
			window.location.href = result.signup_url;
		} else {
			setConnectionStatus( 'failed' );
		}
	};

	const renderStripeGateway = () => {
		return (
			<div className="tec-tickets-onboarding__payment-gateway">
				<div className="tec-tickets-onboarding__gateway-logo">
					<StripeLogo />
				</div>
				<p className="tec-tickets-onboarding__gateway-description">
					{ __( 'Enable credit card payments, Afterpay, Klarna and more on your website.', 'event-tickets' ) }
				</p>
				{ connectionStatus === 'connected' ? (
					<div className="tec-tickets-onboarding__connection-status tec-tickets-onboarding__connection-status--connected">
						<CheckIcon /> { __( 'Connected', 'event-tickets' ) }
					</div>
				) : connectionStatus === 'failed' ? (
					<>
						<div className="tec-tickets-onboarding__connection-error">
							<ErrorIcon />
							<span className="tec-tickets-onboarding__error-text">
								{ __( 'Connection failed. ', 'event-tickets' ) }
								<a
									href="/wp-admin/admin.php?page=tec-tickets-help"
									className="tec-tickets-onboarding__support-link"
								>
									{ __( 'Contact Support ↗', 'event-tickets' ) }
								</a>
							</span>
						</div>
						<Button
							isPrimary
							className="tec-tickets-onboarding__try-again"
							onClick={ () => handleConnect( paymentOption ) }
						>
							{ __( 'Try again', 'event-tickets' ) }
						</Button>
					</>
				) : (
					''
				) }
			</div>
		);
	};


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
					{ paymentOption === 'stripe' && renderStripeGateway() }
					{ (!paymentOption || (paymentOption === 'stripe' && connectionStatus !== 'connected')) && (
						<BaseControl
							__nextHasNoMarginBottom
							id="currency-code"
							label={ __( 'Currency', 'event-tickets' ) }
							className="tec-tickets-onboarding__form-field"
						>
							<select
								onChange={ ( e ) => setCurrency( e.target.value ) }
								value={ currencyCode }
								required
							>
								{ Object.entries( currencies ).map( ( [ key, data ] ) => (
									<option key={ key } value={ data[ 'code' ] }>
										{ data[ 'name' ] } ({ data[ 'code' ] })
									</option>
								) ) }
							</select>
							<span className="tec-tickets-onboarding__required-label">
								{ __( 'Currency is required.', 'event-tickets' ) }
							</span>
							<span className="tec-tickets-onboarding__invalid-label">
								{ __( 'Currency is invalid.', 'event-tickets' ) }
							</span>
						</BaseControl>
					) }
				</div>
				{ !paymentOption || connectionStatus === 'connected' ? (
					<NextButton
						moveToNextTab={ moveToNextTab }
						tabSettings={ tabSettings }
						disabled={ false }
						onSuccess={() => {}}
					/>
				) : (
					<Button
						isPrimary
						className="tec-tickets-onboarding__button"
						onClick={ () => handleConnect( paymentOption ) }
						disabled={ connectionStatus === 'connecting' }
					>
						{ connectionStatus === 'connecting'
							? __( 'Connecting...', 'event-tickets' )
							: __( 'Connect to Stripe', 'event-tickets' ) }
					</Button>
				) }
				{ connectionStatus !== 'connected' && (
					<SkipButton skipToNextTab={ skipToNextTab } currentTab={1} />
				) }
			</div>
		</>
	);
};

export default SettingsContent;
