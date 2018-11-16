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
import { selectors, actions } from '@moderntribe/tickets/data/blocks/ticket';
import { TICKET_TYPES } from '@moderntribe/tickets/data/utils';
import { plugins } from '@moderntribe/common/data';

const mapStateToProps = ( state, ownProps ) => ( {
	type: selectors.getTicketCapacityType( state, ownProps ),
	capacity: selectors.getTicketCapacity( state, ownProps ),
	regularCapacity: selectors.getRegularTicketCapacity( state, ownProps ),
	totalSharedCapacity: selectors.getSharedCapacity( state ),
	tmpSharedCapacity: selectors.getTmpSharedCapacity( state ),
	hasTicketsPlus: plugins.selectors.hasPlugin( state )( plugins.constants.TICKETS_PLUS ),
} );

const mapDispatchToProps = ( dispatch, ownProps ) => ( {
	onSelectType( type ) {
		const { blockId } = ownProps;
		dispatch( actions.setCapacityType( blockId, type ) );
	},
	setCapacity( type, total, value ) {
		const { blockId } = ownProps;
		const totalValue = parseInt( total, 10 );
		let capacity = value;
		/**
		 * Make sure shared capacity does not overflow the total capacity on the FE, this is handled
		 * already by the BE API
		 */
		if ( type === TICKET_TYPES.shared && ! isNaN( totalValue ) ) {
			const currentValue = parseInt( value, 10 );
			if ( ! isNaN( currentValue ) && currentValue > totalValue ) {
				capacity = totalValue;
			}
		}
		dispatch( actions.setCapacity( blockId, capacity ) );
	},
	setTemporarilySharedCapacity( capacity ) {
		dispatch( actions.setTempSharedCapacity( capacity ) );
	},
	setRegularTicketValue( e ) {
		const { blockId } = ownProps;
		dispatch( actions.setRegularTicketValue( blockId, e.target.value ) );
	},
} );

const mergeProps = ( stateProps, dispatchProps, ownProps ) => {
	return {
		...stateProps,
		...dispatchProps,
		...ownProps,
		onCapacityChange( value ) {
			dispatchProps.setCapacity( stateProps.type, stateProps.totalSharedCapacity, value );
		},
	};
};

export default compose(
	withStore(),
	connect(
		mapStateToProps,
		mapDispatchToProps,
		mergeProps,
	),
)( Template );
