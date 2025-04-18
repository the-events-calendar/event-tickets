import React from 'react';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import SuccessIcon from './img/success';
import FinishButton from '../../buttons/finish';
import TECIcon from './img/tec';

const SuccessContent = () => {
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
					<div className="tec-tickets-onboarding__success-heading">
						{ __( 'Congratulations!', 'event-tickets' ) }
					</div>
					<p className="tec-tickets-onboarding__success-message">
						{ __( 'The Events Calendar is now installed.', 'event-tickets' ) }
					</p>
					<Button
						variant="primary"
						className="tec-tickets-onboarding__button tec-tickets-onboarding__button--next"
						href="/wp-admin/edit.php?post_type=tribe_events&page=first-time-setup"
					>
						{ __( 'Continue to The Events Calendar Setup', 'event-tickets' ) }
					</Button>
					<FinishButton />
				</div>
			</div>
		</>
	);
};

export default SuccessContent;
