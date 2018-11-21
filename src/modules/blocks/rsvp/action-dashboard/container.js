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
import RSVPActionDashboard from './template';
import { plugins } from '@moderntribe/common/data';
import { actions, selectors, thunks } from '@moderntribe/tickets/data/blocks/rsvp';
import { withStore } from '@moderntribe/common/hoc';

const getHasRecurrenceRules = ( state ) => {
	let hasRules = false;
	try {
		hasRules = window.tribe[ plugins.constants.EVENTS_PRO_PLUGIN ].data.blocks.recurring.selectors.hasRules( state );
	} catch ( e ) {
		// ¯\_(ツ)_/¯
	}
	return hasRules;
};

const getIsCancelDisabled = ( state ) => (
	! selectors.getRSVPHasChanges( state ) || selectors.getRSVPIsLoading( state )
);

const getIsConfirmDisabled = ( state ) => (
	! selectors.getRSVPTempTitle( state )
		|| ! selectors.getRSVPHasChanges( state )
		|| selectors.getRSVPIsLoading( state )
);

const onCancelClick = ( state, dispatch ) => () => {
	dispatch( actions.setRSVPTempDetails( {
		tempTitle: selectors.getRSVPTitle( state ),
		tempDescription: selectors.getRSVPDescription( state ),
		tempCapacity: selectors.getRSVPCapacity( state ),
		tempNotGoingResponses: selectors.getRSVPNotGoingResponses( state ),
		tempStartDate: selectors.getRSVPStartDate( state ),
		tempStartDateObj: selectors.getRSVPStartDateObj( state ),
		tempEndDate: selectors.getRSVPEndDate( state ),
		tempEndDateObj: selectors.getRSVPEndDateObj( state ),
		tempStartTime: selectors.getRSVPStartTime( state ),
		tempEndTime: selectors.getRSVPEndTime( state ),
	} ) );
	dispatch( actions.setRSVPHasChanges( false ) );
};

const onConfirmClick = ( state, dispatch ) => () => {
	const payload = {
		title: selectors.getRSVPTempTitle( state ),
		description: selectors.getRSVPTempDescription( state ),
		capacity: selectors.getRSVPTempCapacity( state ),
		notGoingResponses: selectors.getRSVPTempNotGoingResponses( state ),
		startDate: selectors.getRSVPTempStartDate( state ),
		startDateObj: selectors.getRSVPTempStartDateObj( state ),
		endDate: selectors.getRSVPTempEndDate( state ),
		endDateObj: selectors.getRSVPTempEndDateObj( state ),
		startTime: selectors.getRSVPTempStartTime( state ),
		endTime: selectors.getRSVPTempEndTime( state ),
	};

	if ( ! selectors.getRSVPCreated( state ) ) {
		dispatch( thunks.createRSVP( {
			...payload,
			postId: select( 'core/editor' ).getCurrentPostId(),
		} ) );
	} else {
		dispatch( thunks.updateRSVP( {
			...payload,
			id: selectors.getRSVPId( state ),
		} ) );
	}
};

const mapStateToProps = ( state ) => ( {
	created: selectors.getRSVPCreated( state ),
	hasRecurrenceRules: getHasRecurrenceRules( state ),
	hasTicketsPlus: plugins.selectors.hasPlugin( state )( plugins.constants.TICKETS_PLUS ),
	isCancelDisabled: getIsCancelDisabled( state ),
	isConfirmDisabled: getIsConfirmDisabled( state ),
	isLoading: selectors.getRSVPIsLoading( state ),
	showCancel: selectors.getRSVPCreated( state ),
	state,
} );

const mergeProps = ( stateProps, dispatchProps, ownProps ) => {
	const { state, ...restStateProps } = stateProps;
	const { dispatch } = dispatchProps;

	return {
		...ownProps,
		...restStateProps,
		onCancelClick: onCancelClick( state, dispatch ),
		onConfirmClick: onConfirmClick( state, dispatch, ownProps ),
	};
};

export default compose(
	withStore(),
	connect( mapStateToProps, null, mergeProps ),
)( RSVPActionDashboard );
