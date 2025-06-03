import React from 'react';
import { __ } from '@wordpress/i18n';

/**
 * Interface for PaymentRadioOptions props.
 *
 * @since TBD
 */
interface PaymentRadioOptionsProps {
	paymentGateways: {
		stripe: boolean;
		square: boolean;
		paypal: boolean;
	};
	paymentOption: string;
	onPaymentOptionChange: (value: string) => void;
}

/**
 * Component that renders payment gateway radio options.
 *
 * @since TBD
 *
 * @param {PaymentRadioOptionsProps} props Component props.
 *
 * @return {JSX.Element} The component.
 */
const PaymentRadioOptions: React.FC<PaymentRadioOptionsProps> = ({
	paymentGateways,
	paymentOption,
	onPaymentOptionChange,
}) => {
	const handleChange = (value: string) => {
		onPaymentOptionChange(value);
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

export default PaymentRadioOptions;
