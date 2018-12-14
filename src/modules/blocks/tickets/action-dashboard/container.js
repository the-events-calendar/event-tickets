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
import { plugins } from '@moderntribe/common/data';
import { withStore } from '@moderntribe/common/hoc';
import { selectors } from '@moderntribe/tickets/data/blocks/ticket';

const getHasRecurrenceRules = ( state ) => {
	let hasRules = false;
	try {
		hasRules = window.tribe[ plugins.constants.EVENTS_PRO_PLUGIN ].data.blocks.recurring.selectors.hasRules( state );
	} catch ( e ) {
		// ¯\_(ツ)_/¯
	}
	return hasRules;
};

const mapStateToProps = ( state, ownProps ) => ( {
	hasTicketsPlus: plugins.selectors.hasPlugin( state )( plugins.constants.TICKETS_PLUS ),
	hasRecurrenceRules: getHasRecurrenceRules( state ),
	hasCreatedTickets: selectors.hasCreatedTickets( state ),
	onConfirmClick: () => {
		const { clientId } = ownProps;
		const { getBlockCount } = select( 'core/editor' );
		const { insertBlock } = wpDispatch( 'core/editor' );

		const nextChildPosition = getBlockCount( clientId );
		const block = createBlock( 'tribe/tickets-item', {} );
		insertBlock( block, nextChildPosition, clientId );
	},
} );

export default compose(
	withStore(),
	connect( mapStateToProps ),
)( Template );
