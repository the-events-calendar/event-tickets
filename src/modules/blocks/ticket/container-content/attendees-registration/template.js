/**
 * External dependencies
 */
import React, { createRef, PureComponent } from 'react';
import PropTypes from 'prop-types';

/**
 * Wordpress dependencies
 */
import { Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { LabelWithModal } from '@moderntribe/common/elements';
import './style.pcss';

const helperText = __( 'Save your ticket to enable attendee registration fields', 'event-tickets' );
const label = __( 'Attendee Registration', 'event-tickets' );
const linkTextAdd = __( '+ Add', 'event-tickets' );
const linkTextEdit = __( 'Edit', 'event-tickets' );

class AttendeesRegistration extends PureComponent {
	static propTypes = {
		attendeeRegistrationURL: PropTypes.string.isRequired,
		hasAttendeeInfoFields: PropTypes.bool.isRequired,
		isCreated: PropTypes.bool.isRequired,
		isDisabled: PropTypes.bool.isRequired,
		isModalOpen: PropTypes.bool.isRequired,
		onClick: PropTypes.func.isRequired,
		onClose: PropTypes.func.isRequired,
		onIframeLoad: PropTypes.func.isRequired,
	};

	constructor( props ) {
		super( props );
		this.iFrame = createRef();
	}

	render() {
		const {
			attendeeRegistrationURL,
			hasAttendeeInfoFields,
			isCreated,
			isDisabled,
			isModalOpen,
			onClick,
			onClose,
			onIframeLoad,
		} = this.props;

		const linkText = hasAttendeeInfoFields ? linkTextEdit : linkTextAdd;

		const modalContent = (
			<div className="tribe-editor__ticket__attendee-registration-modal-content">
				<iframe
					className="tribe-editor__ticket__attendee-registration-modal-iframe"
					onLoad={ () => onIframeLoad( this.iFrame.current ) }
					ref={ this.iFrame }
					src={ attendeeRegistrationURL }
				>
				</iframe>
				<div className="tribe-editor__ticket__attendee-registration-modal-overlay">
					<Spinner />
				</div>
			</div>
		);

		return (
			<div className="tribe-editor__ticket__attendee-registration">
				<LabelWithModal
					className="tribe-editor__ticket__attendee-registration-label-with-modal"
					isOpen={ isModalOpen }
					label={ label }
					modalButtonDisabled={ isDisabled }
					modalButtonLabel={ linkText }
					modalClassName="tribe-editor__ticket__attendee-registration-modal"
					modalContent={ modalContent }
					modalTitle={ label }
					onClick={ onClick }
					onClose={ onClose }
				/>
				{ ! isCreated && (
					<span className="tribe-editor__ticket__attendee-registration-helper-text">
						{ helperText }
					</span>
				) }
			</div>
		);
	}
}

export default AttendeesRegistration;
