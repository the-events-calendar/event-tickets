/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * WordPress dependencies
 */
import { select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import AttendeeRegistration from './template';
import { selectors } from '@moderntribe/tickets/data/blocks/ticket';
import { withStore } from '@moderntribe/common/hoc';
import { globals } from '@moderntribe/common/utils';
<<<<<<< HEAD:src/modules/blocks/ticket/container-content/attendees-registration/container.js
=======
const { config } = globals;
>>>>>>> release/F18.3:src/modules/blocks/ticket/edit-container/content/attendees-registration/container.js

const getAttendeeRegistrationUrl = ( state, ownProps ) => {
	const adminURL = globals.adminUrl();
	const postType = select( 'core/editor' ).getCurrentPostType();
	const ticketId = selectors.getTicketId( state, ownProps );

	return `${ adminURL }edit.php?post_type=${ postType }&page=attendee-registration&ticket_id=${ ticketId }`;
};

const mapStateToProps = ( state, ownProps ) => {
	const isCreated = selectors.getTicketHasBeenCreated( state, ownProps );

	return {
		attendeeRegistrationURL: getAttendeeRegistrationUrl( state, ownProps ),
		isCreated,
		isDisabled: selectors.isTicketDisabled( state, ownProps ) || ! isCreated,
	};
};

export default compose(
	withStore(),
	connect( mapStateToProps ),
)( AttendeeRegistration );
