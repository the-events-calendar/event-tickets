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
import RSVPAttendeeRegistration from './template';
import * as actions from '../../../data/blocks/rsvp-shared/actions';
import * as selectors from '../../../data/blocks/rsvp-shared/selectors';
import { withStore } from '@moderntribe/common/hoc';
import { globals } from '@moderntribe/common/utils';

const getAttendeeRegistrationUrl = ( state ) => {
	const adminURL = globals.adminUrl();
	const postType = select( 'core/editor' ).getCurrentPostType();
	const rsvpId = selectors.getRSVPId( state );

	return `${ adminURL }edit.php?post_type=${ postType }&page=attendee-registration&ticket_id=${ rsvpId }&tribe_events_modal=1`; // eslint-disable-line max-len
};

const getIsDisabled = ( state ) =>
	selectors.getRSVPIsLoading( state ) ||
	selectors.getRSVPSettingsOpen( state ) ||
	! selectors.getRSVPCreated( state );

const mapStateToProps = ( state ) => ( {
	attendeeRegistrationURL: getAttendeeRegistrationUrl( state ),
	hasAttendeeInfoFields: selectors.getRSVPHasAttendeeInfoFields( state ),
	isCreated: selectors.getRSVPCreated( state ),
	isDisabled: getIsDisabled( state ),
	isModalOpen: selectors.getRSVPIsModalOpen( state ),
} );

const mapDispatchToProps = ( dispatch ) => ( {
	onClick: () => {
		dispatch( actions.setRSVPIsModalOpen( true ) );
	},
	onClose: () => {
		dispatch( actions.setRSVPIsModalOpen( false ) );
	},
	onIframeLoad: ( iframe ) => {
		const iframeWindow = iframe.contentWindow;

		const showOverlay = () => {
			iframe.nextSibling.classList.add( 'tribe-editor__attendee-registration__modal-overlay--show' );
		};

		const form = iframeWindow.document.querySelector( '#event-tickets-attendee-information' );
		form.addEventListener( 'submit', showOverlay );

		const removeListeners = () => {
			iframeWindow.removeEventListener( 'unload', handleUnload ); // eslint-disable-line no-use-before-define,max-len
			form.removeEventListener( 'submit', showOverlay );
		};

		const handleUnload = () => {
			removeListeners( iframeWindow );

			const metaFields = iframeWindow.document.querySelector( '#tribe-tickets-attendee-sortables' );
			const hasFields = Boolean( metaFields.firstElementChild );

			dispatch( actions.setRSVPHasAttendeeInfoFields( hasFields ) );
			dispatch( actions.setRSVPIsModalOpen( false ) );
		};

		iframeWindow.addEventListener( 'unload', handleUnload );

		const introLink = iframeWindow.document.querySelector( '.tribe-intro > a' );
		if ( introLink ) {
			introLink.setAttribute( 'target', '_blank' );
		}
	},
} );

export default compose( withStore(), connect( mapStateToProps, mapDispatchToProps ) )( RSVPAttendeeRegistration );
