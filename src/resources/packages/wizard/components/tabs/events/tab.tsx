import React from 'react';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { CheckboxControl } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import { SETTINGS_STORE_KEY } from '../../../data';
import NextButton from '../../buttons/next';
import SkipButton from '../../buttons/skip';
import TECIcon from './img/tec';

const EventsContent = ( { moveToNextTab, skipToNextTab } ) => {
	const eventsCalendarInstalled = useSelect(
		( select ) => select( SETTINGS_STORE_KEY ).getSetting( 'tec-events-installed' ) || false,
		[]
	);
	const eventsCalendarActive = useSelect(
		( select ) => select( SETTINGS_STORE_KEY ).getSetting( 'tec-events-active' ) || false,
		[]
	);
	const [ eventsValue, setEventsValue ] = useState( true ); // Default to install/activate.

	// Create tabSettings object to pass to NextButton.
	const tabSettings = {
		eventsCalendar: eventsValue,
		currentTab: 4,
	};

	const message = ! eventsCalendarInstalled
		? __( 'Yes, install The Events Calendar for free on my website.', 'event-tickets' )
		: __( 'Activate the The Events Calendar Plugin for me.', 'event-tickets' );

	return (
		<>
			<TECIcon />
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
				{ ! eventsCalendarActive && (
					<div className="tec-tickets-onboarding__checkbox tec-tickets-onboarding__checkbox--events">
						<CheckboxControl
							__nextHasNoMarginBottom
							aria-describedby="tec-tickets-onboarding__checkbox-description"
							checked={ eventsValue }
							onChange={ setEventsValue }
							id="tec-tickets-onboarding__events-checkbox-input"
						/>
						<div className="tec-tickets-onboarding__checkbox-description">
							<label htmlFor="tec-tickets-onboarding__events-checkbox-input">{ message }</label>
							<div id="tec-tickets-onboarding__checkbox-description"></div>
						</div>
					</div>
				) }
				<NextButton tabSettings={ tabSettings } moveToNextTab={ moveToNextTab } disabled={ false } />
				<SkipButton skipToNextTab={ skipToNextTab } currentTab={ 4 } />
			</div>
		</>
	);
};

export default EventsContent;
