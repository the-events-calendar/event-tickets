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
import { actions, selectors } from '../../../../../../../modules/data/blocks/ticket';
import { showModal } from '../../../../../../../modules/data/shared/move/actions';

const mapStateToProps = ( state, ownProps ) => ( {
	isDisabled: selectors.isTicketDisabled( state, ownProps ),
	ticketIsSelected: selectors.getTicketIsSelected( state, ownProps ),
	ticketId: selectors.getTicketId( state, ownProps ),
} );

const mapDispatchToProps = ( dispatch, ownProps ) => ( {
	removeTicket: () => {
		dispatch( actions.deleteTicket( ownProps.clientId ) );
	},
	moveTicket: ( ticketId ) => dispatch( showModal( ticketId, ownProps.clientId ) ),
} );

const mergeProps = ( stateProps, dispatchProps, ownProps ) => ( {
	...stateProps,
	...dispatchProps,
	...ownProps,
	moveTicket: () => dispatchProps.moveTicket( stateProps.ticketId ),
} );

export default compose( withStore(), connect( mapStateToProps, mapDispatchToProps, mergeProps ) )( Template );
