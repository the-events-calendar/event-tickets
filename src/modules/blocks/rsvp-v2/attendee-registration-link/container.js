/**
 * External dependencies
 */
import React from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import RSVPAttendeeRegistration from '../../rsvp-shared/attendee-registration/container';
import { actions, selectors, thunks } from '../../../data/blocks/rsvp-v2';
import { withStore } from '@moderntribe/common/hoc';

/**
 * The V2 attendee registration link wraps the shared attendee registration
 * container, which owns the full iframe modal logic (overlay, unload
 * listeners, IAC sync bridge). The V2 RSVP data layer re-exports the shared
 * selectors and actions, so the shared container connects to the V2 state
 * tree directly. Only the V2 link copy differs from the shared default.
 *
 * Unlike the shared default, the link is never gated on the RSVP being
 * created: clicking it persists the RSVP on demand (creating the ticket when
 * needed) and only then opens the modal, so attendee information can be
 * collected from the first load of the editor without requiring a save.
 *
 * @param {Object} state The Redux state.
 * @return {boolean} Whether the link should be disabled.
 */
export const getIsDisabled = ( state ) => selectors.getRSVPIsLoading( state ) || selectors.getRSVPSettingsOpen( state );

export const mapStateToProps = ( state ) => ( {
	isDisabled: getIsDisabled( state ),
	showHelperText: false,
	state,
} );

export const mapDispatchToProps = ( dispatch ) => ( { dispatch } );

export const mergeProps = ( stateProps, dispatchProps, ownProps ) => {
	const { state, ...restStateProps } = stateProps;
	const { dispatch } = dispatchProps;
	const getState = () => ownProps.store.getState();

	return {
		...ownProps,
		...restStateProps,
		onClick: async () => {
			// The modal iframe needs a real ticket ID, so persist the RSVP
			// first (creating the ticket when it does not exist yet), then
			// open the modal once the RSVP is created.
			if ( ! selectors.getRSVPCreated( getState() ) ) {
				await dispatch( thunks.persistRSVP() );
			}

			if ( selectors.getRSVPCreated( getState() ) ) {
				dispatch( actions.setRSVPIsModalOpen( true ) );
			}
		},
	};
};

const RSVPAttendeeRegistrationLink = ( props ) => (
	<RSVPAttendeeRegistration
		label=""
		linkTextAdd={ __( '+ Collect attendee information', 'event-tickets' ) }
		linkTextEdit={ __( 'Edit attendee information', 'event-tickets' ) }
		{ ...props }
	/>
);

export default compose(
	withStore(),
	connect( mapStateToProps, mapDispatchToProps, mergeProps )
)( RSVPAttendeeRegistrationLink );
