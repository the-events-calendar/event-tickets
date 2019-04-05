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
	};
};

const mapDispatchToProps = ( dispatch, ownProps ) => {
	return {
		onIframeLoad: ( iframeWindow ) => {
			// check if there are meta fields
			const metaFields = iframeWindow.document.querySelector( '#tribe-tickets-attendee-sortables' );
			const hasFields = Boolean( metaFields.firstElementChild );

			// if form was submitted and success == 1, dispatch action and close modal
			const queryParameters = iframeWindow.location.search.substr( 1 ).split( '&' );
			queryParameters.forEach( ( parameter ) => {
				const [ key, value ] = parameter.split( '=' );
				if ( key === 'success' && value == 1 ) {
					console.log('here updating has attendee info fields,', hasFields);
					dispatch( actions.setTicketHasAttendeeInfoFields( ownProps.clientId, hasFields ) );
					// close modal
				}
			} );

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
