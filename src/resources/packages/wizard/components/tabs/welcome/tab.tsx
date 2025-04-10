import React, { useState, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from "@wordpress/data";
import SetupButton from '../../buttons/setup';
import ExitButton from '../../buttons/exit';
import OptInCheckbox from './inputs/opt-in';
import Illustration from './img/wizard-welcome-img.svg';
import { SETTINGS_STORE_KEY } from "../../../data";

const WelcomeContent = ({moveToNextTab, skipToNextTab}) => {
	const optin = useSelect(select => select(SETTINGS_STORE_KEY).getSetting('optin') || false, []);
	const [originalValue, setOriginalValue] = useState(optin);
	const [optinValue, setOptinValue] = useState(optin); // Store the updated optin value

	useEffect(() => {
		// Update the local state if the optin value changes
		setOptinValue(optin);
	}, [optin]);

	// Create tabSettings object to pass to NextButton
	const tabSettings = {
		optin: optinValue, // Include updated optin value
		currentTab: 0, // Include the current tab index.
		begun: true, // Indicate that the user has started the wizard.
	};

	return (
		<>
			<div className="tec-tickets-onboarding__tab-hero">
				<img src={Illustration} className="tec-tickets-onboarding__welcome-header" alt="Welcome" role="presentation" />
			</div>
			<div className="tec-tickets-onboarding__tab-header">
				<h1 className="tec-tickets-onboarding__tab-heading">{__("Welcome to Event Tickets", "event-tickets")}</h1>
				<p className="tec-tickets-onboarding__tab-subheader">{__("Congratulations on installing the top ticket management solution for WordPress - now let’s make it yours.", "event-tickets")}</p>
			</div>
			<div className="tec-tickets-onboarding__tab-content">
				<SetupButton
					tabSettings={tabSettings}
					moveToNextTab={moveToNextTab}
				/>
				<ExitButton />
			</div>
			<div className="tec-tickets-onboarding__tab-footer">
				{!originalValue && <OptInCheckbox initialOptin={optin} onChange={setOptinValue} />}
			</div>
		</>
	);
};

export default WelcomeContent;
