import React from 'react';
import { BaseControl } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import { __, _x } from '@wordpress/i18n';
import { useSelect, useDispatch } from "@wordpress/data";
import { SETTINGS_STORE_KEY } from "../../../data";
import NextButton from '../../buttons/next';
import SkipButton from '../../buttons/skip';
import GearIcon from './img/gear';

const dateFormatOptions = [
	{ label: _x('October 29, 2024', 'example date in "F j, Y" format', 'event-tickets'), value: 'F j, Y' },
	{ label: _x('29 October, 2024', 'example date in "j F, Y" format', 'event-tickets'), value: 'j F, Y' },
	{ label: _x('10/29/2024', 'example date in "m/d/Y" format', 'event-tickets'), value: 'm/d/Y' },
	{ label: _x('29/10/2024', 'example date in "d/m/Y" format', 'event-tickets'), value: 'd/m/Y' },
	{ label: _x('2024-10-29', 'example date in "Y-m-d" format', 'event-tickets'), value: 'Y-m-d' },
];

const startDayOptions = [
	{ label: __('Sunday', 'event-tickets'), value: '0' },
	{ label: __('Monday', 'event-tickets'), value: '1' },
	{ label: __('Tuesday', 'event-tickets'), value: '2' },
	{ label: __('Wednesday', 'event-tickets'), value: '3' },
	{ label: __('Thursday', 'event-tickets'), value: '4' },
	{ label: __('Friday', 'event-tickets'), value: '5' },
	{ label: __('Saturday', 'event-tickets'), value: '6' },
];

const SettingsContent = ({moveToNextTab, skipToNextTab}) => {
	const visitedFields = useSelect(select => select(SETTINGS_STORE_KEY).getVisitedFields());
	const setVisitedField = useDispatch(SETTINGS_STORE_KEY).setVisitedField;
	const { currency, timezone_string, date_format, start_of_week, timezones, currencies }: { currency: string, timezone_string: string, date_format: string, start_of_week: number, timezones: Record<string, Record<string, string>>, currencies: Record<string, { symbol: string, name: string }> } = useSelect(
		(select) => {
			const store = select(SETTINGS_STORE_KEY);
			return {
				currency: store.getSetting('currency'),
				timezone_string: store.getSetting('timezone_string'),
				date_format: store.getSetting('date_format'),
				start_of_week: store.getSetting('start_of_week'),
				timezones: store.getSetting('timezones'),
				currencies: store.getSetting('currencies'),
			};
		},
		[]
	);
	const [ currencyCode, setCurrency ] = useState( currency );
	const [ timeZone, setTimeZone ] = useState( timezone_string );
	const [ dateFormat, setDateFormat ] = useState( date_format || dateFormatOptions[0].value );
	const [ weekStart, setWeekStart ] = useState( start_of_week || 0 );
	const [canContinue, setCanContinue] = useState(false);

	let timeZoneMessage = __("Please ensure your time zone is correct.", 'event-tickets');

	if ( ! timezone_string ) {
		timeZoneMessage = __("Please select your time zone.", 'event-tickets');
	} else if ( timezone_string.includes('UTC') ) {
		timeZoneMessage = __('Please select your time zone as UTC offsets are not supported.', 'event-tickets');
	}

	// Create tabSettings object to pass to NextButton.
	const tabSettings = {
		currency: currencyCode,
		timezone_string: timeZone,
		date_format: dateFormat,
		start_of_week: weekStart,
		currentTab: 2, // Include the current tab index.
	};

	useEffect(() => {
		// Define the event listener function.
		const handleChange = (event) => {
			setVisitedField(event.target.id);
		};

		const fields = document.getElementById('settingsPanel')?.querySelectorAll('input, select, textarea');
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
			"currencyCode": currencyCode,
			"timeZone": isValidTimeZone(),
			"dateFormat": dateFormat,
			"weekStart": weekStart,
			'visit-at-least-one': hasVisitedHere(),
		};

		setCanContinue(Object.values(fieldsToCheck).every((field) => !!field));
	}, [currencyCode, timeZone, dateFormat, weekStart, visitedFields]);

	const hasVisitedHere = () => {
		const values = [!!currencyCode && !!timeZone && !!dateFormat && !!weekStart];
		const fields = ['currencyCode', 'timeZone', 'dateFormat', 'weekStart'];
		return fields.some(field => visitedFields.includes(field)) || values;
	}

	const isValidTimeZone = () => {
		const inputId = 'time-zone';
		const isVisited = visitedFields.includes(inputId);
		const isValid = !isVisited || !!timeZone;
		const fieldEle = document.getElementById(inputId);
		const parentEle = fieldEle?.closest('.tec-tickets-onboarding__form-field');

		if ( isVisited ) {
			toggleClasses(timeZone, fieldEle, parentEle, isValid);
		}

		return isValid;
	}

	const toggleClasses = (field, fieldEle, parentEle, isValid) => {
		if ( !field ) {
			parentEle.classList.add('invalid', 'empty');
			fieldEle.classList.add('invalid');
		} else if ( !isValid ) {
			parentEle.classList.add('invalid');
			fieldEle.classList.add('invalid');
		} else {
			parentEle.classList.remove('invalid', 'empty');
			fieldEle.classList.remove('invalid');
		}
	}

	return (
		<>
			<GearIcon />
			<div className="tec-tickets-onboarding__tab-header">
				<h1 className="tec-tickets-onboarding__tab-heading">{__('Event Settings', 'event-tickets')}</h1>
				<p className="tec-tickets-onboarding__tab-subheader">{__('Let\'s get your events with the correct basic settings.', 'event-tickets')}</p>
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
							defaultValue={ currencyCode }
						>
							{Object.entries(currencies).map(([key, data]) => (
								<option key={key} value={key}>{data['symbol']} ({data['name']})</option>
							))}
						</select>
						<span className="tec-tickets-onboarding__required-label">{__('Currency symbol is required.', 'event-tickets')}</span>
						<span className="tec-tickets-onboarding__invalid-label">{__('Currency symbol is invalid.', 'event-tickets')}</span>
					</BaseControl>

					<BaseControl
						__nextHasNoMarginBottom
						id="time-zone"
						label={__('Time zone', 'event-tickets')}
						className="tec-tickets-onboarding__form-field"
					>
						<select
							id="time-zone"
							onChange={(e) => setTimeZone(e.target.value)}
							describedby="time-zone-description"
							defaultValue={ timeZone }
						>
							<option value="">{__("Select a non-UTC timezone.", 'event-tickets' )}</option>
							{Object.entries(timezones).map(([key, cities]) => (
								<optgroup key={key} className="continent" label={key}>
									{Object.entries(cities as {[key: string]: string}).map(([key, city]) => (
										<option key={key} value={key}>{city}</option>
									))}
								</optgroup>
							))}
						</select>
						<span id="time-zone-description" className="tec-tickets-onboarding__field-description">{timeZoneMessage}</span>
						<span className="tec-tickets-onboarding__required-label">{__('A non-UTC time zone is required.', 'event-tickets')}</span>
						<span className="tec-tickets-onboarding__invalid-label">{__('Time zone is invalid.', 'event-tickets')}</span>
					</BaseControl>

					<BaseControl
						__nextHasNoMarginBottom
						id="date-format"
						label={__('Date format', 'event-tickets')}
						className="tec-tickets-onboarding__form-field"
					>
						<select
							id="date-format"
							onChange={(e) => setDateFormat(e.target.value)}
							defaultValue={ dateFormat }
						>
							{dateFormatOptions.map(({label, value}) => (
								<option key={value} value={value}>{label}</option>
							))}
						</select>
						<span className="tec-tickets-onboarding__required-label">{__('Date format is required.', 'event-tickets')}</span>
						<span className="tec-tickets-onboarding__invalid-label">{__('Date format is invalid.', 'event-tickets')}</span>
					</BaseControl>

					<BaseControl
						__nextHasNoMarginBottom
						id="week-starts"
						label={__('Your week starts on', 'event-tickets')}
						className="tec-tickets-onboarding__form-field"
					>
						<select
							id="week-starts"
							onChange={(e) => setWeekStart(e.target.value)}
							defaultValue={ weekStart }
						>
							{startDayOptions.map(({label, value}) => (
								<option key={value} value={value}>{label}</option>
							))}
						</select>
						<span className="tec-tickets-onboarding__required-label">{__('Currency symbol is required.', 'event-tickets')}</span>
						<span className="tec-tickets-onboarding__invalid-label">{__('Currency symbol is invalid.', 'event-tickets')}</span>
					</BaseControl>

				</div>
				<NextButton disabled={!canContinue} moveToNextTab={moveToNextTab} tabSettings={tabSettings}/>
				<SkipButton skipToNextTab={skipToNextTab} currentTab={2} />
			 </div>
		</>
	);
};

export default SettingsContent;
