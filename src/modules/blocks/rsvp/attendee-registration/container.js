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
import { actions, selectors } from '../../../data/blocks/rsvp';
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

const mapDispatchToProps = ( dispatch, ownProps ) => {
	return {
		onClick: () => {
			dispatch( actions.setRSVPIsModalOpen( true ) );
		},
		onClose: ( e ) => {
			if ( ! e.target.classList.contains( 'components-modal__content' ) ) {
				dispatch( actions.setRSVPIsModalOpen( ownProps.clientId, false ) );
			}
		},
		onIframeLoad: ( iframe ) => {
			const iframeWindow = iframe.contentWindow;

			// Track whether the form was submitted so handleUnload can mark changes.
			let wasFormSubmitted = false;

			// show overlay
			const showOverlay = () => {
				wasFormSubmitted = true;
				iframe.nextSibling.classList.add( 'tribe-editor__attendee-registration__modal-overlay--show' );
			};

			// add event listener for form submit
			const form = iframeWindow.document.querySelector( '#event-tickets-attendee-information' );
			form.addEventListener( 'submit', showOverlay );

			// Listen for real-time IAC changes posted from the iframe (ET+ posts these on radio change).
			const handleIacMessage = ( event ) => {
				if (
					event.source === iframeWindow &&
					event.data &&
					event.data.action === 'tec_rsvp_iac_change'
				) {
					dispatch( actions.setRSVPIAC( event.data.iac ) );
				}
			};
			window.addEventListener( 'message', handleIacMessage );

			// remove listeners
			const removeListeners = () => {
				iframeWindow.removeEventListener( 'unload', handleUnload ); // eslint-disable-line no-use-before-define,max-len
				form.removeEventListener( 'submit', showOverlay );
				window.removeEventListener( 'message', handleIacMessage );
			};

			// handle unload on iframe unload
			const handleUnload = () => {
				// remove listeners
				removeListeners( iframeWindow );

				// check if there are meta fields
				const metaFields = iframeWindow.document.querySelector( '#tribe-tickets-attendee-sortables' );
				const hasFields = metaFields ? Boolean( metaFields.firstElementChild ) : false;

				// Sync final IAC value to Redux on close (covers the case where postMessage was not sent).
				const iacInput = iframeWindow.document.querySelector( 'input[name="ticket_iac"]:checked' );
				if ( iacInput ) {
					dispatch( actions.setRSVPIAC( iacInput.value ) );
				}

				// If the form was submitted (not just dismissed), mark RSVP as having changes
				// so the "Update RSVP" button becomes enabled and the REST save includes the new IAC.
				if ( wasFormSubmitted ) {
					dispatch( actions.setRSVPHasChanges( true ) );
				}

				// dispatch actions
				dispatch( actions.setRSVPHasAttendeeInfoFields( hasFields ) );
				dispatch( actions.setRSVPIsModalOpen( false ) );
			};

			// add handler to iframe window unload
			iframeWindow.addEventListener( 'unload', handleUnload );

			// add target blank to "Learn more" link
			const introLink = iframeWindow.document.querySelector( '.tribe-intro > a' );
			if ( introLink ) {
				introLink.setAttribute( 'target', '_blank' );
			}
		},
	};
};

export default compose( withStore(), connect( mapStateToProps, mapDispatchToProps ) )( RSVPAttendeeRegistration );
