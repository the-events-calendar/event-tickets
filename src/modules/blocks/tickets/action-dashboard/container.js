/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * WordPress dependencies
 */
import { withDispatch, withSelect } from '@wordpress/data';
import { createBlock } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import Template from './template';
import { withStore } from '@moderntribe/common/hoc';
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
	},
	updateTicket() {
		const { activeBlockId } = ownProps;
		dispatch( actions.updateTicket( activeBlockId ) );
	},
} );

const applyWithSelect = withSelect( ( select, ownProps ) => {
	const { getBlockCount } = select( 'core/editor' );
	const { clientId } = ownProps;
	return {
		nextChildPosition: getBlockCount( clientId ),
	};
} );

const applyWithDispatch = withDispatch( ( dispatch, ownProps ) => {
	const { removeBlock, insertBlock } = dispatch( 'core/editor' );

	return {
		onCancelClick() {
			const { activeBlockId, hasBeenCreated, isBeingEdited, cancelEdit } = ownProps;

			if ( isBeingEdited ) {
				cancelEdit();
			} else if ( ! hasBeenCreated ) {
				removeBlock( activeBlockId );
			}
		},
		onConfirmClick() {
			const { isEditing, createNewEntry, nextChildPosition, isBeingEdited, updateTicket } = ownProps;
			if ( isBeingEdited ) {
				updateTicket();
			} else if ( isEditing ) {
				createNewEntry();
			} else {
				const block = createBlock( 'tribe/tickets-item', {} );
				const { clientId } = ownProps;
				insertBlock( block, nextChildPosition, clientId );
			}
		},
	};
} );

export default compose(
	withStore(),
	connect(
		mapStateToProps,
		mapDispatchToProps,
	),
	applyWithSelect,
	applyWithDispatch,
)( Template );
