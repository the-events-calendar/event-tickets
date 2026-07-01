/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import RSVP from './template';
import { actions, selectors, thunks } from '../../data/blocks/rsvp';
import { isModalShowing, getModalTicketId } from '../../data/shared/move/selectors';
import { withStore } from '@moderntribe/common/hoc';
import withSaveData from '../hoc/with-save-data';
import { hasRecurrenceRules, noRsvpsOnRecurring } from '@moderntribe/common/utils/recurrence';
import { createSetInitialState } from '../rsvp-shared/utils/create-set-initial-state';
import { createCloseBlockOverlays } from '../rsvp-shared/utils/create-close-block-overlays';
import './filters';

const mapStateToProps = ( state ) => {
	const rsvpId = selectors.getRSVPId( state );

	return {
		created: selectors.getRSVPCreated( state ),
		isAddEditOpen: selectors.getRSVPIsAddEditOpen( state ),
		isInactive: selectors.getRSVPIsInactive( state ),
		isLoading: selectors.getRSVPIsLoading( state ),
		isModalShowing: isModalShowing( state ) && getModalTicketId( state ) === rsvpId,
		isSettingsOpen: selectors.getRSVPSettingsOpen( state ),
		hasRecurrenceRules: hasRecurrenceRules( state ),
		noRsvpsOnRecurring: noRsvpsOnRecurring(),
		rsvpId,
		goingCount: String( selectors.getRSVPGoingCount( state ) ?? '' ),
		notGoingCount: String( selectors.getRSVPNotGoingCount( state ) ?? '' ),
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
		hydrateHeaderImage: true,
	} )( dispatch, ownProps ),
	closeBlockOverlays: createCloseBlockOverlays( { dispatch, actions, closeSettings: true } ),
	closeBlockOverlaysOnDeselect: createCloseBlockOverlays( {
		dispatch,
		actions,
		closeSettings: true,
		closeAttendeeModal: false,
	} ),
} );

export default compose( withStore(), connect( mapStateToProps, mapDispatchToProps ), withSaveData() )( RSVP );
