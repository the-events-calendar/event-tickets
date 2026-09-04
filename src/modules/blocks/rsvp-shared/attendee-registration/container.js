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

const mapStateToProps = ( state, ownProps ) => ( {
	attendeeRegistrationURL: getAttendeeRegistrationUrl( state ),
	hasAttendeeInfoFields: selectors.getRSVPHasAttendeeInfoFields( state ),
	isCreated: selectors.getRSVPCreated( state ),
	isDisabled: ownProps.isDisabled !== undefined ? ownProps.isDisabled : getIsDisabled( state ),
	isModalOpen: selectors.getRSVPIsModalOpen( state ),
	helperText: ownProps.helperText || '',
	showHelperText: ownProps.showHelperText !== undefined ? ownProps.showHelperText : false,
} );

const mapDispatchToProps = ( dispatch, ownProps ) => ( {
	onClick:
		ownProps.onClick ||
		( () => {
			dispatch( actions.setRSVPIsModalOpen( true ) );
		} ),
	onClose: () => {
		dispatch( actions.setRSVPIsModalOpen( false ) );
	},
	onIframeLoad: ( iframe ) => {
		const iframeWindow = iframe.contentWindow;

		let wasFormSubmitted = false;

		window.tribe_event_tickets_plus = window.tribe_event_tickets_plus || {};
		window.tribe_event_tickets_plus.rsvp = window.tribe_event_tickets_plus.rsvp || {};
		const previousOnIacChange = window.tribe_event_tickets_plus.rsvp.onIacChange;
		window.tribe_event_tickets_plus.rsvp.onIacChange = ( iacValue ) => {
			dispatch( actions.setRSVPIAC( iacValue ) );
		};

		const showOverlay = () => {
			wasFormSubmitted = true;
			iframe.nextSibling.classList.add( 'tribe-editor__attendee-registration__modal-overlay--show' );
		};

		const form = iframeWindow.document.querySelector( '#event-tickets-attendee-information' );
		form.addEventListener( 'submit', showOverlay );

		const removeListeners = () => {
			iframeWindow.removeEventListener( 'pagehide', handleUnload ); // eslint-disable-line no-use-before-define,max-len
			form.removeEventListener( 'submit', showOverlay );
			window.tribe_event_tickets_plus.rsvp.onIacChange = previousOnIacChange;
		};

		const handleUnload = () => {
			removeListeners( iframeWindow );

			const iframeDocument = iframeWindow.document;
			const fieldNames = [];

			const iacContainer = iframeDocument.querySelector( '.tribe-tickets__admin-attendees-info-iac-fields' );
			if ( iacContainer && ! iacContainer.classList.contains( 'tribe-common-a11y-hidden' ) ) {
				const iacTypes = iacContainer.querySelectorAll( '.tribe-tickets-attendee-info-field-type' );
				iacTypes.forEach( ( el ) => {
					if ( el.textContent?.trim() ) {
						fieldNames.push( el.textContent.trim() );
					}
				} );
			}

			const sortablesContainer = iframeDocument.querySelector( '#tribe-tickets-attendee-sortables' );
			if ( sortablesContainer ) {
				const fieldPostboxes = sortablesContainer.querySelectorAll( '.meta-postbox' );
				fieldPostboxes.forEach( ( postbox ) => {
					const input = postbox.querySelector( 'input.ticket_field[name*="[label]"]' );
					if ( input && input.value?.trim() ) {
						fieldNames.push( input.value.trim() );
					}
				} );
			}

			dispatch( actions.setRSVPAttendeeInfoFieldNames( fieldNames ) );

			const metaFields = iframeDocument.querySelector( '#tribe-tickets-attendee-sortables' );
			const hasFields = Boolean( metaFields.firstElementChild );

			const iacInput = iframeDocument.querySelector( 'input[name="ticket_iac"]:checked' );
			if ( iacInput ) {
				dispatch( actions.setRSVPIAC( iacInput.value ) );
			}

			if ( wasFormSubmitted ) {
				dispatch( actions.setRSVPHasChanges( true ) );
			}

			dispatch( actions.setRSVPHasAttendeeInfoFields( hasFields ) );
			dispatch( actions.setRSVPIsModalOpen( false ) );
		};

		// Chrome (and other modern browsers) do not reliably fire `unload` for same-origin
		// iframe navigations triggered by a form submit; `pagehide` is the supported replacement.
		iframeWindow.addEventListener( 'pagehide', handleUnload );

		const introLink = iframeWindow.document.querySelector( '.tribe-intro > a' );
		if ( introLink ) {
			introLink.setAttribute( 'target', '_blank' );
		}
	},
} );

export default compose( withStore(), connect( mapStateToProps, mapDispatchToProps ) )( RSVPAttendeeRegistration );
