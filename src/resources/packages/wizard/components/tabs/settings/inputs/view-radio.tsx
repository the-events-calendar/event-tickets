import React from "react";
import { __ } from '@wordpress/i18n';

interface RadioOption {
	value: string;
	label: string;
	help?: string;
}

interface ViewRadioProps {
	selectedValue: string;
	onChange: (value: string) => void;
	options: RadioOption[];
	name: string;
}

const ViewRadio = ({ selectedValue, onChange, options, name }: ViewRadioProps) => {
	return (
		<div className="tec-tickets-onboarding__radio-group">
			{options.map((option) => {
				return (
					<label key={option.value} className="tec-tickets-onboarding__radio-label">
						<input
							type="radio"
							name={name}
							value={option.value}
							checked={selectedValue === option.value}
							onChange={(e) => onChange(e.target.value)}
						/>
						{option.label}
						{option.help && (
							<span className="tec-tickets-onboarding__radio-help">
								({option.help})
							</span>
						)}
					</label>
				);
			})}
		</div>
	);
};

export default ViewRadio;
