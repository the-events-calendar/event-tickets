import React from "react";
import {__} from '@wordpress/i18n';
import {useSelect} from "@wordpress/data";
import { CheckboxControl } from '@wordpress/components';
import {useState, useEffect} from '@wordpress/element';
import {SETTINGS_STORE_KEY} from "../../../data";
import NextButton from '../../buttons/next';
import SkipButton from '../../buttons/skip';
import TicketsIcon from './img/tickets';

<<<<<<<< HEAD:src/resources/packages/wizard/components/tabs/payments/tab.tsx
const PaymentsContent = ({moveToNextTab, skipToNextTab}) => {
========
const CommunicationContent = ({moveToNextTab, skipToNextTab}) => {
>>>>>>>> debe62927f59ae2eec01d4b0551fe78888b10604:src/resources/packages/wizard/components/tabs/communication/tab.tsx
	const eventTicketsInstalled = useSelect(select => select(SETTINGS_STORE_KEY).getSetting("tec-tickets-installed") || false, []);
	const eventTicketsActive = useSelect(select => select(SETTINGS_STORE_KEY).getSetting("tec-tickets-active") || false, []);
	const [ticketValue, setTicketValue] = useState(true); // Default to install/activate.

<<<<<<<< HEAD:src/resources/packages/wizard/components/tabs/payments/tab.tsx
	// Create tabPayments object to pass to NextButton.
	const tabPayments = {
========
	// Create tabCommunication object to pass to NextButton.
	const tabCommunication = {
>>>>>>>> debe62927f59ae2eec01d4b0551fe78888b10604:src/resources/packages/wizard/components/tabs/communication/tab.tsx
		eventTickets: ticketValue,
		currentTab: 5, // Include the current tab index.
	}

	const message = (!eventTicketsInstalled) ? __("Yes, install and activate Event Tickets for free on my website.", "event-tickets") : __("Activate the Event Tickets Plugin for me.", "event-tickets");

	return (
		<>
			<TicketsIcon />
			<div className="tec-tickets-onboarding__tab-header">
				<h1 className="tec-tickets-onboarding__tab-heading">{__("Sell your tickets online", "event-tickets")}</h1>
				<p className="tec-tickets-onboarding__tab-subheader">{__("Easily accept payments with your trusted gateway", "event-tickets")}</p>
			</div>
			<div className="tec-tickets-onboarding__tab-content">
				{!eventTicketsActive &&(
					<div
						alignment="top"
						justify="center"
						spacing={0}
						className="tec-tickets-onboarding__checkbox tec-tickets-onboarding__checkbox--tickets"
					>
						<CheckboxControl
							__nextHasNoMarginBottom
							aria-describedby="tec-tickets-onboarding__checkbox-description"
							checked={ticketValue}
							onChange={setTicketValue}
							id="tec-tickets-onboarding__tickets-checkbox-input"
						/>
						<div className="tec-tickets-onboarding__checkbox-description">
							<label htmlFor="tec-tickets-onboarding__tickets-checkbox-input">
								{message}
							</label>
							<div
								id="tec-tickets-onboarding__checkbox-description"
							>
							</div>
						</div>
					</div>
				)}
<<<<<<<< HEAD:src/resources/packages/wizard/components/tabs/payments/tab.tsx
				<NextButton tabPayments={tabPayments} moveToNextTab={moveToNextTab} disabled={false}/>
========
				<NextButton tabCommunication={tabCommunication} moveToNextTab={moveToNextTab} disabled={false}/>
>>>>>>>> debe62927f59ae2eec01d4b0551fe78888b10604:src/resources/packages/wizard/components/tabs/communication/tab.tsx
				<SkipButton skipToNextTab={skipToNextTab} currentTab={5} />
			</div>
		</>
	);
};

<<<<<<<< HEAD:src/resources/packages/wizard/components/tabs/payments/tab.tsx
export default PaymentsContent;
========
export default CommunicationContent;
>>>>>>>> debe62927f59ae2eec01d4b0551fe78888b10604:src/resources/packages/wizard/components/tabs/communication/tab.tsx
