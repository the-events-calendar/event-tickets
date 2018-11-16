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
import { selectors } from '@moderntribe/tickets/data/blocks/ticket';

const getHasOverlay = ( state, ownProps ) => (
	selectors.getTicketsIsSettingsOpen( state )
		|| (
			! selectors.getTicketsIsSettingsOpen( state )
				&& ! selectors.hasATicketSelected( state )
				&& ! ownProps.isSelected
		)
);

const mapStateToProps = ( state, ownProps ) => ( {
	hasOverlay: getHasOverlay( state, ownProps ),
	hasTickets: selectors.hasTickets( state ),
	hasProviders: selectors.hasTicketProviders(),
} );

export default compose(
	withStore(),
	connect( mapStateToProps ),
)( Template );

