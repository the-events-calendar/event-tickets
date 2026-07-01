/**
 * V2 RSVP Container
 *
 * This container wraps the V2 RSVP template but uses V2 thunks for API calls.
 */

/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import RSVP from './template';
import { actions, selectors, thunks } from '../../data/blocks/rsvp-v2';
import { isModalShowing, getModalTicketId } from '../../data/shared/move/selectors';
import { withStore } from '@moderntribe/common/hoc';
import withSaveData from '../hoc/with-save-data';
import { hasRecurrenceRules, noRsvpsOnRecurring } from '@moderntribe/common/utils/recurrence';
import { createSetInitialState } from '../rsvp-shared/utils/create-set-initial-state';
import { createCloseBlockOverlays } from '../rsvp-shared/utils/create-close-block-overlays';
import { hydrateRsvpFromEditorConfig } from '../../data/blocks/rsvp-v2/utils/hydrate-rsvp-from-editor-config';
import { computeRsvpFingerprint } from '../../data/blocks/rsvp-v2/utils/compute-rsvp-fingerprint';

const mapStateToProps = ( state ) => {
	const rsvpId = selectors.getRSVPId( state );

	return {
		created: selectors.getRSVPCreated( state ),
		isAddEditOpen: selectors.getRSVPIsAddEditOpen( state ),
		isInitializing: selectors.getRSVPIsInitializing( state ),
		isLoading: selectors.getRSVPIsLoading( state ),
		isModalShowing: isModalShowing( state ) && getModalTicketId( state ) === rsvpId,
		hasRecurrenceRules: hasRecurrenceRules( state ),
		noRsvpsOnRecurring: noRsvpsOnRecurring(),
		rsvpId,
		goingCount: String( selectors.getRSVPGoingCount( state ) ?? '' ),
		notGoingCount: String( selectors.getRSVPNotGoingCount( state ) ?? '' ),
		rsvpFingerprint: computeRsvpFingerprint( state ),
	};
};

const mapDispatchToProps = ( dispatch, ownProps ) => ( {
	initializeRSVP: () => dispatch( actions.initializeRSVP() ),
	onBlockRemoved: ( props ) => {
		if ( props.created && props.rsvpId ) {
			dispatch( thunks.deleteRSVP( props.rsvpId ) );
		}

		dispatch( actions.deleteRSVP() );
	},
	setInitialState: createSetInitialState( {
		actions,
		thunks,
		hydrateCountsFromAttributes: false,
		hydrateFromEditorConfig: true,
		hydrateFromEditorConfigFn: hydrateRsvpFromEditorConfig,
	} )( dispatch, ownProps ),
	closeBlockOverlays: createCloseBlockOverlays( { dispatch, actions } ),
	closeBlockOverlaysOnDeselect: createCloseBlockOverlays( {
		dispatch,
		actions,
		closeAttendeeModal: false,
	} ),
} );

export default compose( withStore(), connect( mapStateToProps, mapDispatchToProps ), withSaveData() )( RSVP );
