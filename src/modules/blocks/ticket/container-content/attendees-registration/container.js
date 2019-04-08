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
import { actions, selectors } from '@moderntribe/tickets/data/blocks/ticket';
import { withStore } from '@moderntribe/common/hoc';
import { globals } from '@moderntribe/common/utils';

const getAttendeeRegistrationUrl = ( state, ownProps ) => {
	const adminURL = globals.adminUrl();
	const postType = select( 'core/editor' ).getCurrentPostType();
	const ticketId = selectors.getTicketId( state, ownProps );

	return `${ adminURL }edit.php?post_type=${ postType }&page=attendee-registration&ticket_id=${ ticketId }&tribe_events_modal=1`;
};

const mapStateToProps = ( state, ownProps ) => {
	const isCreated = selectors.getTicketHasBeenCreated( state, ownProps );

	return {
		attendeeRegistrationURL: getAttendeeRegistrationUrl( state, ownProps ),
		hasAttendeeInfoFields: selectors.getTicketHasAttendeeInfoFields( state, ownProps ),
		isCreated,
		isDisabled: selectors.isTicketDisabled( state, ownProps ) || ! isCreated,
		isModalOpen: selectors.getTicketIsModalOpen( state, ownProps ),
	};
};

const mapDispatchToProps = ( dispatch, ownProps ) => {
	return {
		onClick: () => {
			dispatch( actions.setTicketIsModalOpen( ownProps.clientId, true ) );
		},
		onClose: () => {
			dispatch( actions.setTicketIsModalOpen( ownProps.clientId, false ) );
		},
		onIframeLoad: ( iframeWindow ) => {
			const handleUnload = () => {
				// remove unload listener
				removeUnloadListener( iframeWindow );

				// check if there are meta fields
				const metaFields = iframeWindow.document.querySelector( '#tribe-tickets-attendee-sortables' );
				const hasFields = Boolean( metaFields.firstElementChild );

				// dispatch actions
				dispatch( actions.setTicketHasAttendeeInfoFields( ownProps.clientId, hasFields ) );
				dispatch( actions.setTicketIsModalOpen( ownProps.clientId, false ) );
			};

			const removeUnloadListener = ( win ) => {
				win.removeEventListener( 'unload', handleUnload );
			};

			iframeWindow.addEventListener( 'unload', handleUnload );

			// add target blank to "Learn more" link
			const introLink = iframeWindow.document.querySelector( '.tribe-intro > a' );
			if ( introLink ) {
				introLink.setAttribute( 'target', '_blank' );
			}
		},
	};
};

export default compose(
	withStore(),
	connect( mapStateToProps, mapDispatchToProps ),
)( AttendeeRegistration );
