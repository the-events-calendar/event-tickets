/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';
import moment from 'moment';

/**
 * WordPress dependencies
 */
import { select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import RSVP from './template';
import {
	actions,
	selectors,
	thunks
} from '@moderntribe/tickets/data/blocks/rsvp';
import { withStore, withSaveData } from '@moderntribe/common/hoc';
import { toMomentFromDateTime } from '@moderntribe/common/utils/moment';

const getIsInactive = ( state ) => {
	const startDateObj = selectors.getRSVPStartDateObj( state );
	const startTime = selectors.getRSVPStartTime( state );
	const endDateObj = selectors.getRSVPEndDateObj( state );
	const endTime = selectors.getRSVPEndTime( state );

	if ( ! startDateObj || ! endDateObj ) {
		return false;
	}

	const startMoment = toMomentFromDateTime( startDateObj, startTime );
	const endMoment = toMomentFromDateTime( endDateObj, endTime );
	const currentMoment = moment();

	return ! ( currentMoment.isAfter( startMoment ) && currentMoment.isBefore( endMoment ) );
};

const setInitialState = ( dispatch, ownProps ) => () => {
	const postId = select( 'core/editor' ).getCurrentPostId();
	dispatch( thunks.getRSVP( postId ) );
	const { attributes = {} } = ownProps;
	if ( parseInt( attributes.headerImageId, 10 ) ) {
		dispatch( thunks.getRSVPHeaderImage( attributes.headerImageId ) );
	}
	if ( attributes.goingCount ) {
		dispatch( actions.setRSVPGoingCount( parseInt( attributes.goingCount, 10 ) ) );
	}
	if ( attributes.notGoingCount ) {
		dispatch( actions.setRSVPNotGoingCount(
			parseInt( attributes.notGoingCount, 10 )
		) );
	}
};

const mapStateToProps = ( state ) => ( {
	created: selectors.getRSVPCreated( state ),
	rsvpId: selectors.getRSVPId( state ),
	isInactive: getIsInactive( state ),
	isLoading: selectors.getRSVPIsLoading( state ),
} );

const mapDispatchToProps = ( dispatch, ownProps ) => ( {
	dispatch,
	setInitialState: setInitialState( dispatch, ownProps ),
	initializeRSVP: () => dispatch( actions.initializeRSVP() ),
} );

const mergeProps = ( stateProps, dispatchProps, ownProps ) => {
	const { dispatch, ...restDispatchProps } = dispatchProps;

	return {
		...ownProps,
		...stateProps,
		...restDispatchProps,
		deleteRSVP: () => {
			dispatch( actions.deleteRSVP() );
			if ( stateProps.created && stateProps.rsvpId ) {
				dispatch( thunks.deleteRSVP( stateProps.rsvpId ) );
			}
		},
	};
};

export default compose(
	withStore(),
	connect( mapStateToProps, mapDispatchToProps, mergeProps ),
	withSaveData(),
)( RSVP );
