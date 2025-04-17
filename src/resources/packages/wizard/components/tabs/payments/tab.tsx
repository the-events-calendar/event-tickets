import React from 'react';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { CheckboxControl } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import { SETTINGS_STORE_KEY } from '../../../data';
import NextButton from '../../buttons/next';
import SkipButton from '../../buttons/skip';
import TicketsIcon from './img/tickets';

const PaymentsContent = ({ moveToNextTab, skipToNextTab }) => {
	// Create tabPayments object to pass to NextButton.
	const tabPayments = {
		eventTickets: true,
		currentTab: 2,
	};

	return (
		<>
			<TicketsIcon />
			<div className="tec-tickets-onboarding__tab-header">
				<h1 className="tec-tickets-onboarding__tab-heading">
					{__('Sell your tickets online', 'event-tickets')}
				</h1>
				<p className="tec-tickets-onboarding__tab-subheader">
					{__(
						'Easily accept payments with your trusted gateway',
						'event-tickets'
					)}
				</p>
			</div>
			<div className="tec-tickets-onboarding__tab-content">
				<div
					alignment="top"
					justify="center"
					spacing={0}
					className="tec-tickets-onboarding__checkbox tec-tickets-onboarding__checkbox--tickets"
				>
					<CheckboxControl
						__nextHasNoMarginBottom
						aria-describedby="tec-tickets-onboarding__checkbox-description"
						checked={true}
						onChange={() => {}}
						id="tec-tickets-onboarding__tickets-checkbox-input"
					/>
					<div className="tec-tickets-onboarding__checkbox-description">
						<label htmlFor="tec-tickets-onboarding__tickets-checkbox-input">
							{__('Sell tickets online', 'event-tickets')}
						</label>
						<div id="tec-tickets-onboarding__checkbox-description"></div>
					</div>
				</div>
				<NextButton
					tabPayments={tabPayments}
					moveToNextTab={moveToNextTab}
					disabled={false}
				/>
				<SkipButton skipToNextTab={skipToNextTab} currentTab={5} />
			</div>
		</>
	);
};

export default PaymentsContent;
