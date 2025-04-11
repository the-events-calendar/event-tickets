import React from 'react';
import {CheckboxControl} from '@wordpress/components';
import {useState} from '@wordpress/element';
import {__} from '@wordpress/i18n';

const OptInCheckbox = ({ initialOptin, onChange }) => {
	const [ isChecked, setChecked ] = useState( initialOptin );

	const handleChange = (newCheckedState) => {
		setChecked(newCheckedState);
		onChange(newCheckedState); // Call the onChange callback passed from the parent
	};

	return (
		<div className="tec-tickets-onboarding__checkbox tec-tickets-onboarding__checkbox--optin">
			<CheckboxControl
				__nextHasNoMarginBottom
				aria-describedby="tec-tickets-onboarding__checkbox-description"
				checked={isChecked}
				onChange={handleChange}
				id="tec-tickets-onboarding__optin-checkbox-input"
			/>
			<div className="tec-tickets-onboarding__checkbox-description">
				<label htmlFor="tec-tickets-onboarding__optin-checkbox-input">
				{__("Yes, I’d like to share basic information about my usage for future improvements.", "event-tickets")}
				</label>
				<div
					id="tec-tickets-onboarding__checkbox-description"
				>
				<a href="https://evnt.is/1bcl" target="_blank">{__("What permissions are being granted?", "event-tickets")}</a>
				</div>
			</div>
		</div>
	);
};

export default OptInCheckbox;
