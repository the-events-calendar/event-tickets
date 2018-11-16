/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import Template from './template';
import { withStore } from '@moderntribe/common/hoc';
import {
	selectors,
	actions,
} from '@moderntribe/tickets/data/blocks/ticket';

const mapStateToProps = ( state, ownProps ) => ( {
	isDisabled: selectors.isTicketDisabled( state, ownProps ),
	sharedCapacity: selectors.getTicketsSharedCapacity( state ),
	tempCapacity: selectors.getTicketTempCapacity( state, ownProps ),
	tempCapacityType: selectors.getTicketTempCapacityType( state, ownProps ),
	tempCapacityTypeOption: selectors.getTicketTempCapacityTypeOption( state, ownProps ),
	tempSharedCapacity: selectors.getTicketsTempSharedCapacity( state ),
} );

const mapDispatchToProps = ( dispatch, ownProps ) => {
	const { blockId } = ownProps;

	return {
		onTempCapacityChange: ( e ) => {
			dispatch( actions.setTicketTempCapacity( blockId, e.target.value ) );
			dispatch( actions.setTicketHasChanges( blockId, true ) );
		},
		onTempCapacityTypeChange: ( selectedOption ) => {
			dispatch( actions.setTicketTempCapacityType( blockId, selectedOption.value ) );
			dispatch( actions.setTicketHasChanges( blockId, true ) );
		},
		onTempSharedCapacityChange: ( e ) => {
			dispatch( actions.setTicketsTempSharedCapacity( e.target.value ) );
			dispatch( actions.setTicketHasChanges( blockId, true ) );
		},
	};
};

export default compose(
	withStore(),
	connect(
		mapStateToProps,
		mapDispatchToProps,
	),
)( Template );
