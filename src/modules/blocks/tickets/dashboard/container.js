/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import TicketsDashboard from './template';
import { selectors } from '@moderntribe/tickets/data/blocks/ticket';
import { withStore } from '@moderntribe/common/hoc';

const mapStateToProps = ( state ) => ( {
	isSettingsOpen: selectors.getSettingsIsOpen( state ),
	activeBlockId: selectors.getActiveBlockId( state ),
	isLoading: selectors.isParentBlockLoading( state ),
	isTicketLoading: selectors.getTicketIsLoading( state, {
		blockId: selectors.getActiveBlockId( state ),
	} ),
} );

export default compose(
	withStore(),
	connect( mapStateToProps ),
)( TicketsDashboard );

