import React from 'react';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import CheckIcon from '../tabs/payments/img/check';
import ErrorIcon from '../tabs/payments/img/error';

interface GatewayConnectionButtonProps {
	connectionStatus: 'connected' | 'connecting' | 'failed' | 'disconnected';
	gatewayType: string;
	connectText: string;
	onConnect: () => void;
	onContinue: () => void;
	hideStatus?: boolean;
}

const GatewayConnectionButton: React.FC<GatewayConnectionButtonProps> = ({
	connectionStatus,
	gatewayType,
	connectText,
	onConnect,
	onContinue,
	hideStatus = false,
}) => {
	if (connectionStatus === 'connected') {
		return (
			<>
				{!hideStatus && (
					<div className="tec-tickets-onboarding__connection-status tec-tickets-onboarding__connection-status--connected">
						<CheckIcon /> {__('Connected', 'event-tickets')}
					</div>
				)}
				<Button
					isPrimary
					className="tec-tickets-onboarding__next-button"
					onClick={onContinue}
				>
					{__('Continue', 'event-tickets')}
				</Button>
			</>
		);
	}

	if (connectionStatus === 'failed') {
		return (
			<>
				{!hideStatus && (
					<div className="tec-tickets-onboarding__connection-error">
						<ErrorIcon />
						<span className="tec-tickets-onboarding__error-text">
							{__('Connection failed. ', 'event-tickets')}
							<a href="/wp-admin/admin.php?page=tec-tickets-help" className="tec-tickets-onboarding__support-link">
								{__('Contact Support ↗', 'event-tickets')}
							</a>
						</span>
					</div>
				)}
				<Button
					isPrimary
					className="tec-tickets-onboarding__try-again"
					onClick={onConnect}
				>
					{__('Try again', 'event-tickets')}
				</Button>
			</>
		);
	}

	return (
		<Button
			isPrimary
			className="tec-tickets-onboarding__connect-gateway tec-tickets-onboarding__next-button"
			onClick={onConnect}
			disabled={connectionStatus === 'connecting'}
		>
			{connectionStatus === 'connecting'
				? __('Connecting...', 'event-tickets')
				: connectText
			}
		</Button>
	);
};

export default GatewayConnectionButton;
