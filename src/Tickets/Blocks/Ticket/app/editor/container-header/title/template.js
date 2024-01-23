/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import AttendeeRegistrationIcons from './attendee-registration-icons/container';
import './style.pcss';

const TicketContainerHeaderTitle = ( {
	clientId,
	showAttendeeRegistrationIcons = true,
	title,
} ) => {
	return (
		<div className="tribe-editor__ticket__container-header-title">
			<h3 className="tribe-editor__ticket__container-header-title-label">
				{ title }
				{
					showAttendeeRegistrationIcons
					? ( <AttendeeRegistrationIcons clientId={ clientId } /> )
					: null
				}
			</h3>
		</div>
	);
};

TicketContainerHeaderTitle.propTypes = {
	clientId: PropTypes.string,
	isDisabled: PropTypes.bool,
	isSelected: PropTypes.bool,
	onTempTitleChange: PropTypes.func,
	tempTitle: PropTypes.string,
	title: PropTypes.string,
};

export default TicketContainerHeaderTitle;
