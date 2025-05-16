import React from 'react';
import { __ } from '@wordpress/i18n';
import { BaseControl } from '@wordpress/components';
import PaymentRadioOptions from './payment-radio-options';

/**
 * Interface for PaymentSelector props.
 *
 * @since TBD
 */
interface PaymentSelectorProps {
	paymentGateways: {
		stripe: boolean;
		square: boolean;
		paypal: boolean;
	};
	paymentOption: string;
	onPaymentOptionChange: (value: string) => void;
}

/**
 * Component that renders the payment selector section.
 *
 * @since TBD
 *
 * @param {PaymentSelectorProps} props Component props.
 *
 * @return {JSX.Element} The component.
 */
const PaymentSelector: React.FC<PaymentSelectorProps> = ({
	paymentGateways,
	paymentOption,
	onPaymentOptionChange,
}) => {
	return (
		<BaseControl
			__nextHasNoMarginBottom
			id="payment-options"
			label={ __( 'Ticket Payments', 'event-tickets' ) }
			className="tec-tickets-onboarding__form-field tec-tickets-onboarding__payment-options"
		>
			<p className="tec-tickets-onboarding__subtitle">
				{ __( 'Choose how you\'d like to accept payments:', 'event-tickets' ) }
			</p>

			<PaymentRadioOptions
				paymentGateways={paymentGateways}
				paymentOption={paymentOption}
				onPaymentOptionChange={onPaymentOptionChange}
			/>

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
	);
};

export default PaymentSelector;
