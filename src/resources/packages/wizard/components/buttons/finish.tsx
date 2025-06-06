import React from 'react';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Button } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import { MODAL_STORE_KEY, SETTINGS_STORE_KEY } from '../../data';
import { API_ENDPOINT } from '../../data/settings/constants';

const FinishButton = () => {
	const closeModal = useDispatch( MODAL_STORE_KEY ).closeModal;
	const completeTab = useDispatch( SETTINGS_STORE_KEY ).completeTab;

	const actionNonce = useSelect( ( select ) => select( SETTINGS_STORE_KEY ).getSetting( 'action_nonce' ), [] );
	const wpNonce = useSelect( ( select ) => select( SETTINGS_STORE_KEY ).getSetting( '_wpnonce' ), [] );
	const getCompletedTabs = useSelect( ( select ) => select( SETTINGS_STORE_KEY ).getCompletedTabs );
	const getSkippedTabs = useSelect( ( select ) => select( SETTINGS_STORE_KEY ).getSkippedTabs );

	const [ isClicked, setClicked ] = useState( false );

	useEffect( () => {
		const handleFinishWizard = async () => {
			// Mark the last tab as completed
			completeTab( 3 );

			// Add the wpnonce to the apiFetch middleware so we don't have to mess with it.
			apiFetch.use( apiFetch.createNonceMiddleware( wpNonce ) );

			const result = await apiFetch( {
				method: 'POST',
				data: {
					finished: true,
					begun: true,
					action_nonce: actionNonce,
					completedTabs: getCompletedTabs(),
					skippedTabs: getSkippedTabs(),
				},
				path: API_ENDPOINT,
			} );

			setTimeout( () => {
				closeModal();
			}, 1000 );
		};

		if ( isClicked ) {
			handleFinishWizard();
		}
	}, [ isClicked ] );

	return (
		<Button
			variant="link"
			onClick={ () => setClicked( true ) }
			className="tec-tickets-onboarding__button tec-tickets-onboarding__button--skip tec-tickets-onboarding__success-finish"
		>
			{ __( 'Finish the Event Tickets Setup', 'event-tickets' ) }
		</Button>
	);
};

export default FinishButton;
