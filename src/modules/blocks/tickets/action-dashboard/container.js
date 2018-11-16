/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * WordPress dependencies
 */
import { dispatch as wpDispatch, select } from '@wordpress/data';
import { createBlock } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import Template from './template';
import { withStore } from '@moderntribe/common/hoc';
<<<<<<< HEAD

const mapStateToProps = ( state, ownProps ) => ( {
	onConfirmClick: () => {
		const { clientId } = ownProps;
		const { getBlockCount } = select( 'core/editor' );
		const { insertBlock } = wpDispatch( 'core/editor' );

		const nextChildPosition = getBlockCount( clientId );
		const block = createBlock( 'tribe/tickets-item', {} );
		insertBlock( block, nextChildPosition, clientId );
=======
import { selectors, actions } from '@moderntribe/tickets/data/blocks/ticket';
import { plugins } from '@moderntribe/common/data';

const mapStateToProps = ( state, ownProps ) => ( {
	hasTicketsPlus: plugins.selectors.hasPlugin( state )( plugins.constants.TICKETS_PLUS ),
	isEditFormValid: selectors.getTicketValidness( state, {
		blockId: ownProps.activeBlockId,
	} ),
	hasBeenCreated: selectors.getTicketHasBeenCreated( state, {
		blockId: ownProps.activeBlockId,
	} ),
	isBeingEdited: selectors.getTicketIsBeingEdited( state, {
		blockId: ownProps.activeBlockId,
	} ),
	hasProviders: selectors.hasTicketProviders(),
} );

const mapDispatchToProps = ( dispatch, ownProps ) => ( {
	createNewEntry() {
		const { activeBlockId } = ownProps;
		dispatch( actions.createNewTicket( activeBlockId ) );
	},
	cancelEdit() {
		const { activeBlockId } = ownProps;
		dispatch( actions.cancelTicketEdit( activeBlockId ) );
>>>>>>> release/F18.3
	},
} );

export default compose(
	withStore(),
	connect( mapStateToProps ),
)( Template );
