import React from "react";
import { CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const ViewCheckbox = ({ isChecked, onChange, setPaymentOption }) => {
	const viewLabel = __( 'Accept payments online (Powered by Stripe)', 'event-tickets' );

	const handleChange = (checked) => {
		onChange(checked);
		setPaymentOption(checked ? 'stripe' : '');
	};

	return (
		<div
			id={`tec-tickets-onboarding__checkbox`}
			className="tec-tickets-onboarding__checkbox tec-tickets-onboarding__checkbox--view"
		>
			<CheckboxControl
				__nextHasNoMarginBottom
				aria-describedby={`tec-tickets-onboarding__checkbox-label`}
				checked={isChecked}
				onChange={handleChange}
				id={`tec-tickets-onboarding__checkbox-input`}
				className="tec-tickets-onboarding__checkbox-input"
			/>
			<div>
				<label
					id={`tec-tickets-onboarding__checkbox-label`}
					htmlFor={`tec-tickets-onboarding__checkbox-input`}
					className={isChecked ? "tec-tickets-onboarding__checkbox-label tec-tickets-onboarding__checkbox-label--checked" : "tec-tickets-onboarding__checkbox-label"}
				>
					{viewLabel}
				</label>
			</div>
		</div>
	);
};

export default ViewCheckbox;
