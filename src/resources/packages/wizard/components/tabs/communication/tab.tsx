import React from "react";
import {__} from '@wordpress/i18n';
import {useSelect} from "@wordpress/data";
import { CheckboxControl } from '@wordpress/components';
import {useState, useEffect} from '@wordpress/element';
import {SETTINGS_STORE_KEY} from "../../../data";
import NextButton from '../../buttons/next';
import SkipButton from '../../buttons/skip';
import TicketsIcon from './img/tickets';

const TicketsContent = ({moveToNextTab, skipToNextTab}) => {
	const eventTicketsInstalled = useSelect(select => select(SETTINGS_STORE_KEY).getSetting("tec-tickets-installed") || false, []);
	const eventTicketsActive = useSelect(select => select(SETTINGS_STORE_KEY).getSetting("tec-tickets-active") || false, []);
	const [ticketValue, setTicketValue] = useState(true); // Default to install/activate.

	// Create tabCommunication object to pass to NextButton.
	const tabCommunication = {
		eventTickets: ticketValue,
		currentTab: 5, // Include the current tab index.
	}

	const message = (!eventTicketsInstalled) ? __("Yes, install and activate Event Tickets for free on my website.", "event-tickets") : __("Activate the Event Tickets Plugin for me.", "event-tickets");

	return (
		<>
			<TicketsIcon />
			<div className="tec-tickets-onboarding__tab-header">
				<h1 className="tec-tickets-onboarding__tab-heading">{__("Event Tickets", "event-tickets")}</h1>
				<p className="tec-tickets-onboarding__tab-subheader">{__("Will you be selling tickets or providing attendees the ability to RSVP to your events?", "event-tickets")}</p>
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
				<NextButton tabCommunication={tabCommunication} moveToNextTab={moveToNextTab} disabled={false}/>
				<SkipButton skipToNextTab={skipToNextTab} currentTab={5} />
			</div>
		</>
	);
};

export default TicketsContent;
