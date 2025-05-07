import React from 'react';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import SuccessIcon from './img/success';
import FinishButton from '../../buttons/finish';
import TECIcon from './img/tec';
import { useSelect } from '@wordpress/data';
import { SETTINGS_STORE_KEY } from '../../../data/settings/constants';

interface SuccessContentProps {
	onlyActivated?: boolean;
	alreadyActivated?: boolean;
}

const SuccessContent = ( { onlyActivated = false, alreadyActivated = false }: SuccessContentProps ) => {
	const tecWizardCompleted = useSelect(
		( select ) => select( SETTINGS_STORE_KEY ).getSetting( 'tec-wizard-completed' ) || false,
		[]
	);

	return (
		<>
			<TECIcon />
			<div className="tec-tickets-onboarding__tab-header">
				<h1 className="tec-tickets-onboarding__tab-heading">
					{ __( 'The Events Calendar', 'event-tickets' ) }
				</h1>
			</div>
			<div className="tec-tickets-onboarding__tab-content">
				<div className="tec-tickets-onboarding__form-wrapper events-success">
					<div className="tec-tickets-onboarding__success-icon">
						<SuccessIcon />
					</div>
					<h3 className="tec-tickets-onboarding__success-heading">
						{ __( 'Congratulations!', 'event-tickets' ) }
					</h3>
					<p className="tec-tickets-onboarding__success-message">
						{ alreadyActivated
							? __( 'The Events Calendar was already installed and activated.', 'event-tickets' )
							: onlyActivated
							? __( 'The Events Calendar is now activated.', 'event-tickets' )
							: __( 'The Events Calendar is now installed and activated.', 'event-tickets' ) }
					</p>
					<Button
						variant="primary"
						className="tec-tickets-onboarding__button tec-tickets-onboarding__button--next"
						href={
							tecWizardCompleted
								? '/wp-admin/edit.php?post_type=tribe_events&page=tec-events-settings'
								: '/wp-admin/edit.php?post_type=tribe_events&page=first-time-setup'
						}
					>
						{ tecWizardCompleted
							? __( 'Go to The Events Calendar Settings', 'event-tickets' )
							: __( 'Continue to The Events Calendar Setup', 'event-tickets' ) }
					</Button>
					<FinishButton />
				</div>
			</div>
		</>
	);
};

export default SuccessContent;
