import React from "react";
import { CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

interface ViewCheckboxProps {
	isChecked: boolean;
	onChange: (checked: boolean) => void;
	setPaymentOption: (value: string) => void;
	label: string;
	help?: string;
}

const ViewCheckbox = ({ isChecked, onChange, setPaymentOption, label, help }: ViewCheckboxProps) => {
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
					{__(label, 'event-tickets')}
					{help && (
						<span className="tec-tickets-onboarding__checkbox-help">
							({__(help, 'event-tickets')})
						</span>
					)}
				</label>
			</div>
		</div>
	);
};

export default ViewCheckbox;
