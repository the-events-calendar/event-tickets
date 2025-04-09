import React from "react";
import { CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const ViewCheckbox = ({ view, isChecked, onChange, icon }) => {
	const viewLabel = view === 'all' ? __( 'Select all the views', 'event-tickets' ) : view.charAt(0).toUpperCase() + view.slice(1);
	return (
		<div
			id={`event-tickets-onboarding__checkbox-${view}`}
			className="event-tickets-onboarding__checkbox event-tickets-onboarding__checkbox--view"
		>
			<CheckboxControl
				__nextHasNoMarginBottom
				aria-describedby={`event-tickets-onboarding__checkbox-label-${view}`}
				checked={isChecked}
				onChange={(isChecked) => onChange(view, isChecked)} // Pass the view and new checked state to parent
				id={`event-tickets-onboarding__checkbox-input-${view}`}
				className="event-tickets-onboarding__checkbox-input"
				value={view}
			/>
			<div>
				<label
					id={`event-tickets-onboarding__checkbox-label-${view}`}
					htmlFor={`event-tickets-onboarding__checkbox-input-${view}`}
					className={isChecked ? "event-tickets-onboarding__checkbox-label event-tickets-onboarding__checkbox-label--checked" : "event-tickets-onboarding__checkbox-label"}
				>
					{icon}
					{viewLabel}
				</label>
			</div>
		</div>
	);
};

export default ViewCheckbox;
