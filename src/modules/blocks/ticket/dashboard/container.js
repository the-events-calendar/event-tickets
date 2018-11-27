/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import Template from './template';
import { actions, selectors } from '@moderntribe/tickets/data/blocks/ticket';
import { withStore } from '@moderntribe/common/hoc';

const getIsCancelDisabled = ( state, ownProps ) => (
	! selectors.getTicketHasChanges( state, ownProps )
		|| selectors.isTicketDisabled( state, ownProps )
);

const getIsConfirmDisabled = ( state, ownProps ) => (
	! selectors.getTicketTempTitle( state, ownProps )
		|| ! selectors.getTicketHasChanges( state, ownProps )
		|| selectors.isTicketDisabled( state, ownProps )
);

const onCancelClick = ( state, dispatch, ownProps ) => () => {
	dispatch( actions.setTicketTempDetails( ownProps.blockId, {
		title: selectors.getTicketTitle( state, ownProps ),
		description: selectors.getTicketDescription( state, ownProps ),
		price: selectors.getTicketPrice( state, ownProps ),
		sku: selectors.getTicketSku( state, ownProps ),
		startDate: selectors.getTicketStartDate( state, ownProps ),
		startDateInput: selectors.getTicketStartDateInput( state, ownProps ),
		startDateMoment: selectors.getTicketStartDateMoment( state, ownProps ),
		endDate: selectors.getTicketEndDate( state, ownProps ),
		endDateInput: selectors.getTicketEndDateInput( state, ownProps ),
		endDateMoment: selectors.getTicketEndDateMoment( state, ownProps ),
		startTime: selectors.getTicketStartTime( state, ownProps ),
		endTime: selectors.getTicketEndTime( state, ownProps ),
		capacityType: selectors.getTicketCapacityType( state, ownProps ),
		capacity: selectors.getTicketCapacity( state, ownProps ),
	} ) );
	dispatch( actions.setTicketsTempSharedCapacity(
		selectors.getTicketsSharedCapacity( state ),
	) );
	dispatch( actions.setTicketHasChanges( ownProps.blockId, false ) );
};

const onConfirmClick = ( state, dispatch, ownProps ) => () => (
	selectors.getTicketHasBeenCreated( state, ownProps )
		? dispatch( actions.updateTicket( ownProps.blockId ) )
		: dispatch( actions.createNewTicket( ownProps.blockId ) )
);

const mapStateToProps = ( state, ownProps ) => ( {
	hasBeenCreated: selectors.getTicketHasBeenCreated( state, ownProps ),
	isCancelDisabled: getIsCancelDisabled( state, ownProps ),
	isConfirmDisabled: getIsConfirmDisabled( state, ownProps ),
	state,
} );

const mergeProps = ( stateProps, dispatchProps, ownProps ) => {
	const { state, ...restStateProps } = stateProps;
	const { dispatch } = dispatchProps;

	return {
		...ownProps,
		...restStateProps,
		onCancelClick: onCancelClick( state, dispatch, ownProps ),
		onConfirmClick: onConfirmClick( state, dispatch, ownProps ),
	};
};

export default compose(
	withStore(),
	connect( mapStateToProps, null, mergeProps ),
)( Template );
