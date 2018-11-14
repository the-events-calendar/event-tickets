/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import Template from './template';
import { withSaveData, withStore } from '@moderntribe/common/hoc';
import { selectors } from '@moderntribe/tickets/data/blocks/ticket';

const mapStateToProps = ( state ) => ( {
	total: selectors.getTicketsIndependentAndSharedCapacity( state ),
	available: selectors.getTicketsIndependentAndSharedAvailable( state ),
	tickets: selectors.getTicketsArray( state ),
	isLoading: selectors.isParentBlockLoading( state ),
	hasProviders: selectors.hasTicketProviders(),
	isTicketDisabled: selectors.isTicketDisabled( state, {
		blockId: selectors.getActiveBlockId( state ),
	} ),
} );

export default compose(
	withStore(),
	connect(
		mapStateToProps,
	),
	withSaveData(),
)( Template );

