import React from 'react';
import { __ } from '@wordpress/i18n';
import StripeLogo from '../payments/img/stripe';
import SquareLogo from '../payments/img/square';
import PayPalLogo from '../payments/img/paypal';

/**
 * Component to display content specific to a single payment gateway.
 *
 * @since TBD
 */
interface SingleGatewayContentProps {
	singleGateway: string | null;
	connectionStatus: string;
}

const SingleGatewayContent: React.FC<SingleGatewayContentProps> = ({
	singleGateway,
	connectionStatus,
}) => {
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

export default SingleGatewayContent;
