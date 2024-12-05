/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * WordPress dependencies
 */
import { dispatch as wpDispatch } from '@wordpress/data';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import Template from './template';
import { actions, selectors } from '@moderntribe/tickets/data/blocks/ticket';
import { withStore } from '@moderntribe/common/hoc';

/**
 * Whether the confirm button should be disabled.
 *
 * @since 5.16.0
 *
 * @param {Object} state    The state of the store.
 * @param {Object} ownProps The own props of the component.
 *
 * @return {boolean} Whether the confirm button should be disabled.
 */
const getIsConfirmDisabled = (state, ownProps) => {
	const shouldConfirmBeDisabled =
		selectors.isTicketDisabled(state, ownProps) ||
		selectors.getTicketHasDurationError(state, ownProps) ||
		!selectors.getTicketHasChanges(state, ownProps) ||
		!selectors.isTicketValid(state, ownProps);

	/**
	 * Filters whether the confirm button should be disabled.
	 *
	 * @since 5.16.0
	 *
	 * @param {boolean} isDisabled Whether the button is disabled.
	 * @param {Object}  state      The state of the store.
	 * @param {Object}  ownProps   The own props of the component.
	 */
	return applyFilters(
		'tec.tickets.blocks.confirmButton.isDisabled',
		shouldConfirmBeDisabled,
		state,
		ownProps
	);
};

const onCancelClick = ( state, dispatch, ownProps ) => () => {
	if ( selectors.getTicketHasBeenCreated( state, ownProps ) ) {
		dispatch( actions.setTicketTempDetails( ownProps.clientId, {
			title: selectors.getTicketTitle( state, ownProps ),
			description: selectors.getTicketDescription( state, ownProps ),
			price: selectors.getTicketPrice( state, ownProps ),
			sku: selectors.getTicketSku( state, ownProps ),
			iac: selectors.getTicketIACSetting( state, ownProps ),
			startDate: selectors.getTicketStartDate( state, ownProps ),
			startDateInput: selectors.getTicketStartDateInput( state, ownProps ),
			startDateMoment: selectors.getTicketStartDateMoment( state, ownProps ),
			endDate: selectors.getTicketEndDate( state, ownProps ),
			endDateInput: selectors.getTicketEndDateInput( state, ownProps ),
			endDateMoment: selectors.getTicketEndDateMoment( state, ownProps ),
			startTime: selectors.getTicketStartTime( state, ownProps ),
			endTime: selectors.getTicketEndTime( state, ownProps ),
			startTimeInput: selectors.getTicketStartTimeInput( state, ownProps ),
			endTimeInput: selectors.getTicketEndTimeInput( state, ownProps ),
			capacityType: selectors.getTicketCapacityType( state, ownProps ),
			capacity: selectors.getTicketCapacity( state, ownProps ),
			salePriceChecked: selectors.getSalePriceChecked( state, ownProps ),
			salePrice: selectors.getSalePrice( state, ownProps ),
			saleStartDate: selectors.getTicketSaleStartDate( state, ownProps ),
			saleStartDateInput: selectors.getTicketSaleStartDateInput( state, ownProps ),
			saleStartDateMoment: selectors.getTicketSaleStartDateMoment( state, ownProps ),
			saleEndDate: selectors.getTicketSaleEndDate( state, ownProps ),
			saleEndDateInput: selectors.getTicketSaleEndDateInput( state, ownProps ),
			saleEndDateMoment: selectors.getTicketSaleEndDateMoment( state, ownProps ),
		} ) );
		dispatch( actions.setTicketsTempSharedCapacity(
			selectors.getTicketsSharedCapacity( state ),
		) );
		dispatch( actions.setTicketHasChanges( ownProps.clientId, false ) );
	} else {
		dispatch( actions.removeTicketBlock( ownProps.clientId ) );
		wpDispatch( 'core/block-editor' ).removeBlocks( ownProps.clientId );
	}
	wpDispatch( 'core/block-editor' ).clearSelectedBlock(); };

const onConfirmClick = ( state, dispatch, ownProps ) => () => (
	selectors.getTicketHasBeenCreated( state, ownProps )
		? dispatch( actions.updateTicket( ownProps.clientId ) )
		: dispatch( actions.createNewTicket( ownProps.clientId ) )
);

const mapStateToProps = ( state, ownProps ) => ( {
	hasBeenCreated: selectors.getTicketHasBeenCreated( state, ownProps ),
	isCancelDisabled: selectors.isTicketDisabled( state, ownProps ),
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
