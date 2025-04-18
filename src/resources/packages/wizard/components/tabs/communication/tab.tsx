import React from 'react';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { BaseControl, Notice } from '@wordpress/components';
import NextButton from '../../buttons/next';
import SkipButton from '../../buttons/skip';
import EmailIcon from './img/email';

const CommunicationContent = ({ moveToNextTab, skipToNextTab }) => {
	const [email, setEmail] = useState('');
	const [senderName, setSenderName] = useState('');
	const [isEmailValid, setIsEmailValid] = useState(true);
	const [isNameValid, setIsNameValid] = useState(true);
	const [hasInteracted, setHasInteracted] = useState({
		email: false,
		name: false,
	});

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
		setEmail(value);
		setHasInteracted((prev) => ({ ...prev, email: true }));
		setIsEmailValid(validateEmail(value));
	};

	// Handle name change
	const handleNameChange = (e) => {
		const value = e.target.value;
		setSenderName(value);
		setHasInteracted((prev) => ({ ...prev, name: true }));
		setIsNameValid(validateName(value));
	};

	// Create tabCommunication object to pass to NextButton.
	const tabCommunication = {
		email,
		senderName,
		currentTab: 3,
	};

	// Compute whether the "Continue" button should be enabled
	const canContinue = isEmailValid && isNameValid && email && senderName;

	// Get validation messages
	const getValidationMessages = () => {
		const messages: string[] = [];

		if (hasInteracted.email && !email) {
			messages.push(__('Email is required.', 'event-tickets'));
		} else if (hasInteracted.email && email && !isEmailValid) {
			messages.push(
				__('Please enter a valid email address.', 'event-tickets')
			);
		}

		if (hasInteracted.name && !senderName) {
			messages.push(__('Sender name is required.', 'event-tickets'));
		} else if (hasInteracted.name && senderName && !isNameValid) {
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
							'When your customers receive an email about a purchase what address should it be from?',
							'event-tickets'
						)}
						className="tec-tickets-onboarding__form-field"
					>
						<input
							type="email"
							id="sender-email"
							value={email}
							onChange={handleEmailChange}
							placeholder={__('Email', 'event-tickets')}
							className="tec-tickets-onboarding__input"
						/>
						{hasInteracted.email && !email && (
							<span className="tec-tickets-onboarding__required-label">
								{__('Email is required.', 'event-tickets')}
							</span>
						)}
						{hasInteracted.email && email && !isEmailValid && (
							<span className="tec-tickets-onboarding__invalid-label">
								{__(
									'Please enter a valid email address.',
									'event-tickets'
								)}
							</span>
						)}
					</BaseControl>

					<BaseControl
						__nextHasNoMarginBottom
						id="sender-name"
						label={__(
							'Who should we say the email is from (sender name)?',
							'event-tickets'
						)}
						className="tec-tickets-onboarding__form-field"
					>
						<input
							type="text"
							id="sender-name"
							value={senderName}
							onChange={handleNameChange}
							placeholder={__('Name', 'event-tickets')}
							className="tec-tickets-onboarding__input"
						/>
						{hasInteracted.name && !senderName && (
							<span className="tec-tickets-onboarding__required-label">
								{__(
									'Sender name is required.',
									'event-tickets'
								)}
							</span>
						)}
						{hasInteracted.name && senderName && !isNameValid && (
							<span className="tec-tickets-onboarding__invalid-label">
								{__(
									'Please enter a valid name (at least 2 characters).',
									'event-tickets'
								)}
							</span>
						)}
					</BaseControl>
				</div>
				{validationMessages.length > 0 && (
					<Notice
						status="warning"
						isDismissible={false}
						className="tec-tickets-onboarding__validation-notice"
					>
						<ul>
							{validationMessages.map((message, index) => (
								<li key={index}>{message}</li>
							))}
						</ul>
					</Notice>
				)}
				<NextButton
					tabSettings={tabCommunication}
					moveToNextTab={moveToNextTab}
					disabled={!canContinue}
				/>
				<SkipButton skipToNextTab={skipToNextTab} currentTab={3} />
			</div>
		</>
	);
};

export default CommunicationContent;
