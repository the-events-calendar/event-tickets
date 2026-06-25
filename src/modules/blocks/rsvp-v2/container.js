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

const mapStateToProps = ( state ) => {
	const rsvpId = selectors.getRSVPId( state );

	return {
		created: selectors.getRSVPCreated( state ),
		isAddEditOpen: selectors.getRSVPIsAddEditOpen( state ),
		isLoading: selectors.getRSVPIsLoading( state ),
		isModalShowing: isModalShowing( state ) && getModalTicketId( state ) === rsvpId,
		hasRecurrenceRules: hasRecurrenceRules( state ),
		noRsvpsOnRecurring: noRsvpsOnRecurring(),
		rsvpId,
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
	} )( dispatch, ownProps ),
	closeBlockOverlays: createCloseBlockOverlays( { dispatch, actions } ),
	closeBlockOverlaysOnDeselect: createCloseBlockOverlays( {
		dispatch,
		actions,
		closeAttendeeModal: false,
	} ),
} );

export default compose( withStore(), connect( mapStateToProps, mapDispatchToProps ), withSaveData() )( RSVP );
