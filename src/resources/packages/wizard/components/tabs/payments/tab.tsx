import React from 'react';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { Button } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import { SETTINGS_STORE_KEY } from '../../../data';
import NextButton from '../../buttons/next';
import SkipButton from '../../buttons/skip';
import CartIcon from './img/cart';
import StripeLogo from './img/stripe';
import SquareLogo from './img/square';
import CheckIcon from './img/check';
import ErrorIcon from './img/error';

const PaymentsContent = ({ moveToNextTab, skipToNextTab }) => {
	const paymentOption = useSelect((select) => select(SETTINGS_STORE_KEY).getSetting('paymentOption'), []);
	const [connectionStatus, setConnectionStatus] = useState('disconnected');

	// Create tabSettings object to pass to NextButton
	const tabSettings = {
		eventTickets: true,
		currentTab: 2,
	};

	const handleConnect = (gateway: string) => {
		setConnectionStatus('connecting');

		// TODO: Add connection logic here for the gateways
		// Test code to simulate connection states
		setTimeout(() => {
			if (gateway === 'stripe') {
				setConnectionStatus('connected');
			} else {
				setConnectionStatus('failed');
			}
		}, 1000);
	};

	const isConnected = connectionStatus === 'connected';

	const renderPaymentGateway = () => {
		const gatewayConfig = {
			stripe: {
				logo: <StripeLogo />,
				description: __('Enable credit card payments, Afterpay, Klarna and more on your website.', 'event-tickets'),
				connectText: __('Connect to Stripe', 'event-tickets'),
			},
			square: {
				logo: <SquareLogo />,
				description: __('Charge online and on location. Compatible with any Square powered hardware for in-person transactions.', 'event-tickets'),
				connectText: __('Connect to Square', 'event-tickets'),
			},
		};

		const config = gatewayConfig[paymentOption];

		if (!config) return null;

		return (
			<div className="tec-tickets-onboarding__form-wrapper">
				<div className="tec-tickets-onboarding__payment-gateway">
					<div className="tec-tickets-onboarding__gateway-logo">
						{config.logo}
					</div>
					<p className="tec-tickets-onboarding__gateway-description">
						{config.description}
					</p>
					{connectionStatus === 'connected' ? (
						<div className="tec-tickets-onboarding__connection-status tec-tickets-onboarding__connection-status--connected">
							<CheckIcon /> {__('Connected', 'event-tickets')}
						</div>
					) : connectionStatus === 'failed' ? (
						<>
							<div className="tec-tickets-onboarding__connection-error">
								<ErrorIcon />
								<span className="tec-tickets-onboarding__error-text">
									{__('Connection failed. ', 'event-tickets')}
									<a href="/wp-admin/admin.php?page=tec-tickets-help" className="tec-tickets-onboarding__support-link">
										{__('Contact Support ↗', 'event-tickets')}
									</a>
								</span>
							</div>
							<Button
								isPrimary
								className="tec-tickets-onboarding__try-again"
								onClick={() => handleConnect(paymentOption)}
							>
								{__('Try again', 'event-tickets')}
							</Button>
						</>
					) : (
						<Button
							isPrimary
							className="tec-tickets-onboarding__connect-gateway"
							onClick={() => handleConnect(paymentOption)}
							disabled={connectionStatus === 'connecting'}
						>
							{connectionStatus === 'connecting'
								? __('Connecting...', 'event-tickets')
								: config.connectText
							}
						</Button>
					)}
				</div>
			</div>
		);
	};

	return (
		<>
			<CartIcon />
			<div className="tec-tickets-onboarding__tab-header">
				<h1 className="tec-tickets-onboarding__tab-heading">
					{__('Sell your tickets online', 'event-tickets')}
				</h1>
				<p className="tec-tickets-onboarding__tab-subheader">
					{__('Easily accept payments with your trusted gateway', 'event-tickets')}
				</p>
			</div>
			<div className="tec-tickets-onboarding__tab-content">
				{['stripe', 'square'].includes(paymentOption) ? (
					renderPaymentGateway()
				) : (
					<div className="tec-tickets-onboarding__payment-option">
						{__('Selected payment option:', 'event-tickets')} {paymentOption || __('None', 'event-tickets')}
					</div>
				)}
				<NextButton
					tabSettings={tabSettings}
					moveToNextTab={moveToNextTab}
					disabled={['stripe', 'square'].includes(paymentOption) && !isConnected}
				/>
				<SkipButton skipToNextTab={skipToNextTab} currentTab={5} />
			</div>
		</>
	);
};

export default PaymentsContent;
