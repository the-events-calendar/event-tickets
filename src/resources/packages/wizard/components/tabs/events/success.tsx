import React from 'react';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import SuccessIcon from './img/success';
import FinishButton from '../../buttons/finish';
import TECInstallIcon from './img/tec';
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
			<TECInstallIcon />
			<div className="tec-tickets-onboarding__tab-header">
				<h1 className="tec-tickets-onboarding__tab-heading">
					{ __( 'The Events Calendar', 'event-tickets' ) }
				</h1>
			</div>
			<div className="tec-tickets-onboarding__tab-content install-success">
				<div className="tec-tickets-onboarding__form-wrapper">
					<div className="tec-tickets-onboarding__success-icon">
						<SuccessIcon />
					</div>
					<h3 className="tec-tickets-onboarding__success-heading">
						{ __( 'Congratulations!', 'event-tickets' ) }
					</h3>
					<p className="tec-tickets-onboarding__success-message">
						{ onlyActivated
							? __( 'The Events Calendar is now activated.', 'event-tickets' )
							: __( 'The Events Calendar is installed and activated.', 'event-tickets' ) }
					</p>
				</div>
				<Button
					variant="primary"
					className="tec-tickets-onboarding__button tec-tickets-onboarding__button--next"
					href={`/wp-admin/edit.php?post_type=tribe_events&page=${tecWizardCompleted ? 'tec-events-settings' : 'first-time-setup'}`}
				>
					{ tecWizardCompleted
						? __( 'Go to The Events Calendar Settings', 'event-tickets' )
						: __( 'Continue to The Events Calendar Setup', 'event-tickets' ) }
				</Button>
				<FinishButton />
			</div>
		</>
	);
};

export default SuccessContent;
