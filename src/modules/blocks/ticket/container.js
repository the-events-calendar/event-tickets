/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import Template from './template';
import { plugins } from '@moderntribe/common/data';
import { withSaveData, withStore } from '@moderntribe/common/hoc';
import { actions, selectors } from '@moderntribe/tickets/data/blocks/ticket';

const mapStateToProps = ( state, ownProps ) => {
	const props = { blockId: ownProps.clientId };

	return {
		blockId: ownProps.clientId,
		hasTicketsPlus: plugins.selectors.hasPlugin( state )( plugins.constants.TICKETS_PLUS ),
		hasBeenCreated: selectors.getTicketHasBeenCreated( state, props ),
		isDisabled: selectors.isTicketDisabled( state, props ),
		isLoading: selectors.getTicketIsLoading( state, props ),
		ticketId: selectors.getTicketId( state, props ),
	};
};

const mapDispatchToProps = ( dispatch, ownProps ) => {
	const { clientId } = ownProps;

	return {
		onBlockUpdate: ( isSelected ) => (
			dispatch( actions.setTicketIsSelected( clientId, isSelected ) )
		),
		removeTicketBlock: () => {
			dispatch( actions.deleteTicket( clientId ) );
		},
		setInitialState: ( props ) => {
			dispatch( actions.registerTicketBlock( clientId ) );
			dispatch( actions.setTicketInitialState( props ) );
		},
	};
};

export default compose(
	withStore( { isolated: true } ),
	connect(
		mapStateToProps,
		mapDispatchToProps,
	),
	withSaveData(),
)( Template );

