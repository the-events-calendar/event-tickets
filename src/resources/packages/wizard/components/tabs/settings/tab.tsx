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
	const visitedFields = useSelect((select) =>
		select(SETTINGS_STORE_KEY).getVisitedFields()
	);
	const setVisitedField = useDispatch(SETTINGS_STORE_KEY).setVisitedField;
	const {
		currency,
		country,
		currencies,
		countries,
	}: {
		currency: string;
		country: string;
		currencies: Record<string, { symbol: string; name: string }>;
		countries: Record<string, { label: string; value: string }>;
	} = useSelect((select) => {
		const store = select(SETTINGS_STORE_KEY);
		return {
			currency: store.getSetting('currency'),
			country: store.getSetting('country'),
			currencies: store.getSetting('currencies'),
			countries: store.getSetting('countries'),
		};
	}, []);
	const [currencyCode, setCurrency] = useState(currency || 'USD');
	const [selectedCountry, setCountry] = useState(country || 'US');
	const [paymentOption, setPaymentOption] = useState('');
	const [canContinue, setCanContinue] = useState(false);

	// Create tabSettings object to pass to NextButton.
	const tabSettings = {
		currency: currencyCode,
		country: selectedCountry,
		paymentOption,
		currentTab: 2, // Include the current tab index.
	};

	useEffect(() => {
		// Define the event listener function.
		const handleChange = (event) => {
			setVisitedField(event.target.id);
		};

		const fields = document
			.getElementById('settingsPanel')
			?.querySelectorAll('input, select, textarea');
		fields?.forEach((field) => {
			field.addEventListener('change', handleChange);
		});

		return () => {
			fields?.forEach((field) => {
				field.removeEventListener('change', handleChange);
			});
		};
	}, []);

	// Compute whether the "Continue" button should be enabled
	useEffect(() => {
		// Since most of these are selects, we just ensure there is a value.
		const fieldsToCheck = {
			currencyCode: currencyCode,
			country: selectedCountry,
			'visit-at-least-one': hasVisitedHere(),
		};

		setCanContinue(Object.values(fieldsToCheck).every((field) => !!field));
	}, [currencyCode, selectedCountry, visitedFields, paymentOption]);

	const hasVisitedHere = () => {
		const values = [
			!!currencyCode && !!selectedCountry,
		];
		const fields = ['currencyCode', 'country'];
		return fields.some((field) => visitedFields.includes(field)) || values;
	};

	const isUSD = selectedCountry === 'US';

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
						id="country"
						label={__(
							'Where in the world will you host your events?',
							'event-tickets'
						)}
						className="tec-tickets-onboarding__form-field"
					>
						<select
							id="country"
							onChange={(e) => setCountry(e.target.value)}
							defaultValue={selectedCountry}
						>
							{Object.entries(countries)
								.flatMap(([continent, countries]) =>
									Object.entries(countries).map(([code, name]) => ({
										code,
										name,
										continent
									}))
								)
								.sort((a, b) => a.name.localeCompare(b.name))
								.map(({ code, name }) => (
									<option key={code} value={code}>
										{name}
									</option>
								))}
						</select>
						<span className="tec-tickets-onboarding__required-label">
							{__('Country is required.', 'event-tickets')}
						</span>
						<span className="tec-tickets-onboarding__invalid-label">
							{__('Country is invalid.', 'event-tickets')}
						</span>
					</BaseControl>

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
							isUSD ? (
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
						{isUSD ? (
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
								onChange={setPaymentOption}
								setPaymentOption={setPaymentOption}
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
