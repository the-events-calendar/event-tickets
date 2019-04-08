/**
 * External dependencies
 */
import React, { createRef, PureComponent } from 'react';
import PropTypes from 'prop-types';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { LabelWithModal } from '@moderntribe/common/elements';
import './style.pcss';

const helperText = __( 'Save your RSVP to enable attendee registration fields', 'event-tickets' );
const label = __( 'Attendee Registration', 'event-tickets' );
const linkTextAdd = __( '+ Add', 'event-tickets' );
const linkTextEdit = __( 'Edit', 'event-tickets' );

class RSVPAttendeeRegistration extends PureComponent {
	static propTypes = {
		attendeeRegistrationURL: PropTypes.string,
		hasAttendeeInfoFields: PropTypes.bool,
		isCreated: PropTypes.bool,
		isDisabled: PropTypes.bool,
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

		const iFrame = (
			<iframe
				className="tribe-editor__rsvp__attendee-registration-modal-iframe"
				onLoad={ () => onIframeLoad( this.iFrame.current.contentWindow ) }
				ref={ this.iFrame }
				src={ attendeeRegistrationURL }
			>
			</iframe>
		);

		return (
			<div className="tribe-editor__rsvp__attendee-registration">
				<LabelWithModal
					className="tribe-editor__rsvp__attendee-registration-label-with-link"
					isOpen={ isModalOpen }
					label={ label }
					modalButtonDisabled={ isDisabled }
					modalButtonLabel={ linkText }
					modalClassName="tribe-editor__rsvp__attendee-registration-modal"
					modalContent={ iFrame }
					modalTitle={ label }
					onClick={ onClick }
					onClose={ onClose }
				/>
				{ ! isCreated && (
					<span className="tribe-editor__rsvp__attendee-registration-helper-text">
						{ helperText }
					</span>
				) }
			</div>
		);
	}
}

export default RSVPAttendeeRegistration;
