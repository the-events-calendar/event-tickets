import React from 'react';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Button, Spinner } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { SETTINGS_STORE_KEY } from '../../data';
import { API_ENDPOINT } from '../../data/settings/constants';

const SetupButton = ({ disabled, moveToNextTab, tabSettings }) => {
	const completeTab = useDispatch(SETTINGS_STORE_KEY).completeTab;
	const actionNonce = useSelect((select) => select(SETTINGS_STORE_KEY).getSetting('action_nonce'), []);
	const wpNonce = useSelect((select) => select(SETTINGS_STORE_KEY).getSetting('_wpnonce'), []);
	const updateSettings = useDispatch(SETTINGS_STORE_KEY).updateSettings;
	const getSettings = useSelect((select) => select(SETTINGS_STORE_KEY).getSettings);
	const getCompletedTabs = useSelect((select) => select(SETTINGS_STORE_KEY).getCompletedTabs);
	const getSkippedTabs = useSelect((select) => select(SETTINGS_STORE_KEY).getSkippedTabs);

	const [isSaving, setSaving] = useState(false);
	const [isClicked, setClicked] = useState(false);

	// Reset isSaving state when any field in tabSettings changes
	useEffect(() => {
		if (tabSettings && !isSaving) {
			// If the user changes any field, we reset the saving state
			setSaving(false);
			// and the button clicked state.
			setClicked(false);
		}
	}, [tabSettings]);

	useEffect(() => {
		const handleTabChange = async () => {
			// Set the saving state.
			setSaving(true);

			// Add our action nonce.
			tabSettings.action_nonce = actionNonce;

			// Mark the tab as completed.
			completeTab(tabSettings.currentTab);

			// Update settings Store for the current tab.
			updateSettings(tabSettings);

			// Add the wpnonce to the apiFetch middleware so we don't have to mess with it.
			apiFetch.use(apiFetch.createNonceMiddleware(wpNonce));

			const result = await apiFetch({
				method: 'POST',
				data: {
					...getSettings(), // Add settings data
					completedTabs: getCompletedTabs(), // Include completedTabs
					skippedTabs: getSkippedTabs(), // Include skippedTabs
				},
				path: API_ENDPOINT,
			});

			if (result.success) {
				// Mark the step as completed on the landing page.
				const stepIndicators = Array.from(
					document.getElementsByClassName(`tec-tickets-onboarding-step-${tabSettings.currentTab}`)
				);

				stepIndicators.map((stepIndicator: Element) => {
					stepIndicator.classList.add('tec-admin-page__onboarding-step--completed');
				});

				// Reset the saving state.
				setSaving(false);

				// Move to the next tab.
				moveToNextTab();
			}

			setSaving(false);
		};

		if (isClicked) {
			handleTabChange();
		}
	}, [isClicked]);

	return (
		<>
			<Button
				variant="primary"
				disabled={disabled || isSaving}
				onClick={() => setClicked(true)}
				className="tec-tickets-onboarding__button tec-tickets-onboarding__button--setup"
			>
				{isSaving && __('Setting up...', 'event-tickets')}
				{isSaving && <Spinner />}
				{!isSaving && __('Set up payments', 'event-tickets')}
			</Button>
		</>
	);
};

export default SetupButton;
