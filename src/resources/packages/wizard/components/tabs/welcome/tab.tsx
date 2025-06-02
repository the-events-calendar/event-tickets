import React, { useState, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { BaseControl } from '@wordpress/components';
import SetupButton from '../../buttons/setup';
import ExitButton from '../../buttons/exit';
import OptInCheckbox from './inputs/opt-in';
import Illustration from './img/wizard-welcome-img.png';
import { SETTINGS_STORE_KEY } from '../../../data';

const WelcomeContent = ( { moveToNextTab } ) => {
	const optin = useSelect( ( select ) => select( SETTINGS_STORE_KEY ).getSetting( 'optin' ) || false, [] );
	const country = useSelect( ( select ) => select( SETTINGS_STORE_KEY ).getSetting( 'country' ) || 'US', [] );
	const countries = useSelect( ( select ) => select( SETTINGS_STORE_KEY ).getSetting( 'countries' ) || {}, [] );
	const updateSettings = useDispatch( SETTINGS_STORE_KEY ).updateSettings;

	const [ originalValue, setOriginalValue ] = useState( optin );
	const [ optinValue, setOptinValue ] = useState( optin );
	const [ selectedCountry, setCountry ] = useState( country );

	useEffect( () => {
		setOptinValue( optin );
	}, [ optin ] );

	useEffect( () => {
		const currentCountry = selectedCountry || country;
		const hasStripe = countries[currentCountry]?.has_stripe || false;

		// Update settings store to carry available payment options forward.
		// @TODO: Add Square when it's ready.
		updateSettings( {
			country: currentCountry,
			paymentOption: hasStripe ? 'stripe' : ''
		} );
	}, [selectedCountry, country, countries, updateSettings] );

	const tabSettings = {
		optin: optinValue,
		country: selectedCountry,
		paymentOption: countries[selectedCountry]?.has_stripe ? 'stripe' : '',
		currentTab: 0,
		begun: true,
	};

	return (
		<>
			<div className="tec-tickets-onboarding__tab-hero">
				<img
					src={ Illustration }
					className="tec-tickets-onboarding__welcome-header"
					alt="Welcome"
					role="presentation"
				/>
			</div>
			<div className="tec-tickets-onboarding__tab-header">
				<h1 className="tec-tickets-onboarding__tab-heading">
					{ __( 'Welcome to Event Tickets', 'event-tickets' ) }
				</h1>
				<p className="tec-tickets-onboarding__tab-subheader">
					{ __(
						"Congratulations on installing the top ticket management solution for WordPress - now let's make it yours.",
						'event-tickets'
					) }
				</p>
			</div>
			<div className="tec-tickets-onboarding__tab-content">
				<div className="tec-tickets-onboarding__form-wrapper">
					<BaseControl
						__nextHasNoMarginBottom
						id="country"
						label={ __( 'Where in the world will you host your events?', 'event-tickets' ) }
						className="tec-tickets-onboarding__form-field"
					>
						<select
							id="country"
							onChange={ ( e ) => setCountry( e.target.value ) }
							defaultValue={ selectedCountry }
							required
						>
							{ Object.entries( countries )
								.map( ( [ code, country ] ) => ( {
									code,
									name: country.name,
									continent: country.group,
								} ) )
								.sort( ( a, b ) => a.name.localeCompare( b.name ) )
								.map( ( { code, name } ) => (
									<option key={ code } value={ code }>
										{ name }
									</option>
								) ) }
						</select>
						<span className="tec-tickets-onboarding__required-label">
							{ __( 'Country is required.', 'event-tickets' ) }
						</span>
						<span className="tec-tickets-onboarding__invalid-label">
							{ __( 'Country is invalid.', 'event-tickets' ) }
						</span>
					</BaseControl>
				</div>
					<SetupButton tabSettings={ tabSettings } moveToNextTab={ moveToNextTab } />
					<ExitButton />

				<div className="tec-tickets-onboarding__tab-footer">
					{ ! originalValue && <OptInCheckbox initialOptin={ optin } onChange={ setOptinValue } /> }
				</div>
			</div>
		</>
	);
};

export default WelcomeContent;
