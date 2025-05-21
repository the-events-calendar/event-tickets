import React, { useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { BaseControl, Notice } from '@wordpress/components';
import { SETTINGS_STORE_KEY } from "../../../data";
import NextButton from '../../buttons/next';
import SkipButton from '../../buttons/skip';
import EmailIcon from './img/email';
import { getSetting } from '../../../data/settings/selectors';

const CommunicationContent = ({ moveToNextTab, skipToNextTab }) => {
	const userEmailFromStore = useSelect(
		( select ) => select( SETTINGS_STORE_KEY ).getSetting( 'userEmail' ) || '',
		[]
	);
	const [userEmail, setUserEmail] = useState(userEmailFromStore || '');
	const userNameFromStore = useSelect(
		( select ) => select( SETTINGS_STORE_KEY ).getSetting( 'userName' ) || '',
		[]
	);
	const [userName, setUserName] = useState(userNameFromStore || '');
	const [isEmailValid, setIsEmailValid] = useState(true);
	const [isNameValid, setIsNameValid] = useState(true);
	const [hasInteracted, setHasInteracted] = useState({
		email: false,
		name: false,
	});

	const updateSettings = useDispatch(SETTINGS_STORE_KEY).updateSettings;

	useEffect(() => {
		updateSettings({ userEmail, userName });
	}, [userEmail, userName, updateSettings]);

	// Email validation function
	const validateEmail = (email) => {
		const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
		return emailRegex.test(email);
	};

	// Name validation function
	const validateName = (name) => {
		return name.trim().length > 1;
	};

	// Handle email change
	const handleEmailChange = (e) => {
		const value = e.target.value;
		setUserEmail(value);
	};

	// Handle name change
	const handleNameChange = (e) => {
		const value = e.target.value;
		setUserName(value);
	};

	// Handle email blur
	const handleEmailBlur = () => {
		setHasInteracted((prev) => ({ ...prev, email: true }));
		setIsEmailValid(validateEmail(userEmail));
	};

	// Handle name blur
	const handleNameBlur = () => {
		setHasInteracted((prev) => ({ ...prev, name: true }));
		setIsNameValid(validateName(userName));
	};

	// Create tabCommunication object to pass to NextButton.
	const tabCommunication = {
		userEmail,
		userName,
		currentTab: 2,
	};

	// Compute whether the "Continue" button should be enabled
	const canContinue = isEmailValid && isNameValid && userEmail && userName;

	// Get validation messages
	const getValidationMessages = () => {
		const messages: string[] = [];

		if (hasInteracted.email && !userEmail) {
			messages.push(__('Email is required.', 'event-tickets'));
		} else if (hasInteracted.email && userEmail && !isEmailValid) {
			messages.push(
				__('Please enter a valid email address.', 'event-tickets')
			);
		}

		if (hasInteracted.name && !userName) {
			messages.push(__('Sender name is required.', 'event-tickets'));
		} else if (hasInteracted.name && userName && !isNameValid) {
			messages.push(
				__(
					'Please enter a valid name (at least 2 characters).',
					'event-tickets'
				)
			);
		}

		return messages;
	};

	const validationMessages = getValidationMessages();

	return (
		<>
			<EmailIcon />
			<div className="tec-tickets-onboarding__tab-header">
				<h1 className="tec-tickets-onboarding__tab-heading">
					{__('Communication', 'event-tickets')}
				</h1>
				<p className="tec-tickets-onboarding__tab-subheader">
					{__(
						"Put your best face forward—let us know how you'd like your name and email to appear to customers.",
						'event-tickets'
					)}
				</p>
			</div>
			<div className="tec-tickets-onboarding__tab-content">
				<div className="tec-tickets-onboarding__form-wrapper">
					<BaseControl
						__nextHasNoMarginBottom
						id="sender-email"
						label={__(
							'Email Address',
							'event-tickets'
						)}
						help={__(
							'When your customers receive an email about a purchase what address should it be from?',
							'event-tickets'
						)}
						className="tec-tickets-onboarding__form-field"
					>
						<input
							type="email"
							id="sender-email"
							value={userEmail}
							onChange={handleEmailChange}
							onBlur={handleEmailBlur}
							placeholder={__('Email', 'event-tickets')}
							className={`tec-tickets-onboarding__input${hasInteracted.email && (!userEmail || !isEmailValid) ? ' tec-tickets-onboarding__input--invalid' : ''}`}
							required={true}
						/>
						{hasInteracted.email && !userEmail && (
							<span className="tec-tickets-onboarding__invalid-label" style={{display: 'block' }}>
								{__('Email is required.', 'event-tickets')}
							</span>
						)}
						{hasInteracted.email && userEmail && !isEmailValid && (
							<span className="tec-tickets-onboarding__invalid-label" style={{display: 'block' }}>
								{__('Please enter a valid email address.', 'event-tickets')}
							</span>
						)}
					</BaseControl>

					<BaseControl
						__nextHasNoMarginBottom
						id="sender-name"
						label={__(
							'Sender Name',
							'event-tickets'
						)}
						help={__(
							'Who should we say the email is from?',
							'event-tickets'
						)}
						className="tec-tickets-onboarding__form-field"
					>
						<input
							type="text"
							id="sender-name"
							value={userName}
							onChange={handleNameChange}
							onBlur={handleNameBlur}
							placeholder={__('Name', 'event-tickets')}
							className={`tec-tickets-onboarding__input${hasInteracted.name && (!userName || !isNameValid) ? ' tec-tickets-onboarding__input--invalid' : ''}`}
							required={true}
						/>
						{hasInteracted.name && !userName && (
							<span className="tec-tickets-onboarding__invalid-label" style={{display: 'block' }}>
								{__('Sender name is required.', 'event-tickets')}
							</span>
						)}
						{hasInteracted.name && userName && !isNameValid && (
							<span className="tec-tickets-onboarding__invalid-label" style={{display: 'block' }}>
								{__('Please enter a valid name (at least 2 characters).', 'event-tickets')}
							</span>
						)}
					</BaseControl>
				</div>
				<NextButton
					tabSettings={tabCommunication}
					moveToNextTab={moveToNextTab}
					disabled={!canContinue}
					onSuccess={() => {}}
				/>
				<SkipButton skipToNextTab={skipToNextTab} currentTab={2} />
			</div>
		</>
	);
};

export default CommunicationContent;
