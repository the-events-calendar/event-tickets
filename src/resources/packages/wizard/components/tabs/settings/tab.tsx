import React from 'react';
import { BaseControl } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import { __, _x } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { SETTINGS_STORE_KEY } from '../../../data';
import NextButton from '../../buttons/next';
import SkipButton from '../../buttons/skip';
import TicketIcon from './img/ticket';
import ViewCheckbox from './inputs/view-checkbox';
import ViewRadio from './inputs/view-radio';

const SettingsContent = ({ moveToNextTab, skipToNextTab }) => {
	const updateSettings = useDispatch(SETTINGS_STORE_KEY).updateSettings;
	const {
		currency,
		currencies,
		paymentOption: storedPaymentOption,
	}: {
		currency: string;
		currencies: Record<string, { symbol: string; name: string }>;
		paymentOption: string;
	} = useSelect((select) => {
		const store = select(SETTINGS_STORE_KEY);
		return {
			currency: store.getSetting('currency'),
			currencies: store.getSetting('currencies'),
			paymentOption: store.getSetting('paymentOption'),
		};
	}, []);
	const [currencyCode, setCurrency] = useState(currency || 'USD');
	const [paymentOption, setPaymentOption] = useState(storedPaymentOption || '');
	const [canContinue, setCanContinue] = useState(false);

	useEffect(() => {
		updateSettings({ paymentOption });
	}, [paymentOption, updateSettings]);

	const tabSettings = {
		currency: currencyCode,
		paymentOption,
		currentTab: 1,
	};

	// Compute whether the "Continue" button should be enabled
	useEffect(() => {
		const fieldsToCheck = {
			currencyCode: currencyCode,
		};

		setCanContinue(Object.values(fieldsToCheck).every((field) => !!field));
	}, [currencyCode, paymentOption]);

	// TODO: Remove this once we are ready to enable Square.
	const supportsSquare = false;

	return (
		<>
			<TicketIcon />
			<div className="tec-tickets-onboarding__tab-header">
				<h1 className="tec-tickets-onboarding__tab-heading">
					{__('Selling Tickets', 'event-tickets')}
				</h1>
				<p className="tec-tickets-onboarding__tab-subheader">
					{__(
						'Nail down the basics of your ticket setup.',
						'event-tickets'
					)}
				</p>
			</div>
			<div className="tec-tickets-onboarding__tab-content">
				<div className="tec-tickets-onboarding__form-wrapper">
					<BaseControl
						__nextHasNoMarginBottom
						id="currency-code"
						label={__('Currency symbol', 'event-tickets')}
						className="tec-tickets-onboarding__form-field"
					>
						<select
							onChange={(e) => setCurrency(e.target.value)}
							defaultValue={currencyCode}
						>
							{Object.entries(currencies).map(([key, data]) => (
								<option key={key} value={data['code']}>
									{data['symbol']} {data['code']}
								</option>
							))}
						</select>
						<span className="tec-tickets-onboarding__required-label">
							{__(
								'Currency symbol is required.',
								'event-tickets'
							)}
						</span>
						<span className="tec-tickets-onboarding__invalid-label">
							{__('Currency symbol is invalid.', 'event-tickets')}
						</span>
					</BaseControl>

					<BaseControl
						__nextHasNoMarginBottom
						id="payment-options"
						label={
							supportsSquare ? (
								<>
									<div className="tec-tickets-onboarding__payment-title">
										{__('Ticket payments', 'event-tickets')}
									</div>
									{__('Choose how you\'d like to accept payments:', 'event-tickets')}
								</>
							) : (
								<>
								<div className="tec-tickets-onboarding__payment-title">
									{__('Ticket payments', 'event-tickets')}
								</div>
							</>
							)
						}
						help={__('Free tickets and RSVP options are always available.', 'event-tickets')}
						className="tec-tickets-onboarding__form-field"
					>
						{supportsSquare ? (
							<ViewRadio
								selectedValue={paymentOption}
								onChange={setPaymentOption}
								name="payment-option"
								options={[
									{
										value: 'stripe',
										label: 'Online',
										help: 'Powered by Stripe',
									},
									{
										value: 'square',
										label: 'Online and in-person',
										help: 'Powered by Square',
									},
								]}
							/>
						) : (
							<ViewCheckbox
								isChecked={paymentOption === 'stripe'}
								onChange={(checked) => setPaymentOption(checked ? 'stripe' : '')}
								setPaymentOption={setPaymentOption}
								label="Accept payments online"
								help="Powered by Stripe"
							/>
						)}
					</BaseControl>
				</div>
				<NextButton
					disabled={!canContinue}
					moveToNextTab={moveToNextTab}
					tabSettings={tabSettings}
				/>
				<SkipButton skipToNextTab={skipToNextTab} currentTab={2} />
			</div>
		</>
	);
};

export default SettingsContent;
