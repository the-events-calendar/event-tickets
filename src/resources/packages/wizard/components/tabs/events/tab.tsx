import React, { useState, useEffect } from "react";
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { CheckboxControl } from '@wordpress/components';
import { SETTINGS_STORE_KEY } from '../../../data';
import NextButton from '../../buttons/next';
import SkipButton from '../../buttons/skip';
import TECInstallIcon from './img/tec';
import SuccessContent from './success';

const EventsContent = ( { moveToNextTab, skipToNextTab } ) => {
	const eventsCalendarInstalled = useSelect(
		( select ) => select( SETTINGS_STORE_KEY ).getSetting( 'tecInstalled' ) || false,
		[]
	);
	const eventsCalendarActive = useSelect(
		( select ) => select( SETTINGS_STORE_KEY ).getSetting( 'tecActive' ) || false,
		[]
	);

	const [showSuccess, setShowSuccess] = useState(false);
	const [eventsValue, setEventsValue] = useState(true);

	useEffect(() => {
		if (eventsCalendarActive) {
			setShowSuccess(true);
		}
	}, [eventsCalendarActive]);

	const handleSuccess = () => {
		setShowSuccess(true);
	};

	const tabSettings = {
		eventsCalendar: eventsValue,
		currentTab: 3,
	};

	const message = !eventsCalendarInstalled
		? __( 'Yes, install The Events Calendar for free on my website.', 'event-tickets' )
		: __( 'Yes, activate The Events Calendar plugin for me.', 'event-tickets' );

	if (showSuccess) {
		return <SuccessContent
			onlyActivated={eventsCalendarInstalled && !eventsCalendarActive}
			alreadyActivated={eventsCalendarActive}
		/>;
	}

	return (
		<>
			<TECInstallIcon />
			<div className="tec-tickets-onboarding__tab-header">
				<h1 className="tec-tickets-onboarding__tab-heading">
					{ __( 'The Events Calendar', 'event-tickets' ) }
				</h1>
				<p className="tec-tickets-onboarding__tab-subheader">
					{ __(
						'Want powerful, seamless event management? Get everything you need—from a sleek calendar user interface to event subscriptions, virtual experiences, and custom automations—all in one place.',
						'event-tickets'
					) }
				</p>
			</div>
			<div className="tec-tickets-onboarding__tab-content">
				<div className="tec-tickets-onboarding__form-wrapper events-install">
					{ !eventsCalendarActive && (
						<div className="tec-tickets-onboarding__checkbox tec-tickets-onboarding__checkbox--events">
							<CheckboxControl
								__nextHasNoMarginBottom
								aria-describedby="tec-tickets-onboarding__checkbox-description"
								checked={eventsValue}
								onChange={setEventsValue}
								id="tec-tickets-onboarding__events-checkbox-input"
							/>
							<div className="tec-tickets-onboarding__checkbox-description">
								<label htmlFor="tec-tickets-onboarding__events-checkbox-input">{message}</label>
								<div id="tec-tickets-onboarding__checkbox-description"></div>
							</div>
						</div>
					) }
					<NextButton
						tabSettings={tabSettings}
						moveToNextTab={moveToNextTab}
						disabled={!eventsValue}
						onSuccess={handleSuccess}
					/>
					<SkipButton skipToNextTab={skipToNextTab} currentTab={3} buttonText={__("Skip and finish setup", "event-tickets")} />
				</div>
			</div>
		</>
	);
};

export default EventsContent;
