/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import Template from './template';

import { withSaveData, withStore } from '@moderntribe/common/src/modules/hoc';
import { actions, selectors } from '@moderntribe/tickets/data/blocks/ticket';

const mapStateToProps = ( state, ownProps ) => {
	const props = { blockId: ownProps.clientId };
	return {
		isBlockSelected: selectors.getParentOrChildSelected( state ),
		isEditing: selectors.getTicketEditing( state, props ),
		dateIsPristine: ! selectors.getTicketExpires( state, props ),
		hasBeenCreated: selectors.getTicketHasBeenCreated( state, props ),
		isLoading: selectors.getTicketIsLoading( state, props ),
		ticketId: selectors.getTicketId( state, props ),
		isTicketDisabled: selectors.isTicketDisabled( state, props ),
	};
};

const mapDispatchToProps = ( dispatch, ownProps ) => ( {
	setIsSelected( selected ) {
		dispatch( actions.setChildBlockSelected( selected ) );
	},
	onBlockRemoved() {
		const { clientId } = ownProps;
		dispatch( actions.requestRemovalOfTicketBlock( clientId ) );
	},
	setInitialState( props ) {
		dispatch( actions.setTicketInitialState( props ) );
		const { clientId } = ownProps;
		dispatch( actions.registerTicketBlock( clientId ) );
	},
} );

export default compose(
	withStore( { isolated: true } ),
	connect(
		mapStateToProps,
		mapDispatchToProps,
	),
	withSaveData(),
)( Template );

