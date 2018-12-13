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

const getHasTicketsOnSale = ( state ) => {
	const allClientIds = selectors.getTicketsAllClientIds;
	const hasTicketsOnSale = allClientIds.reduce( ( onSale, clientId ) => {
		const props = { clientId };
		const isOnSale = selectors.isTicketOnSale( state, props );
		return onSale || isOnSale;
	}, false );

	return hasTicketsOnSale;
};

const getShowInactiveBlock = ( state, ownProps ) => {
	const showIfBlockIsSelected = ownProps.isSelected && ! selectors.hasTickets( state );
	const showIfBlockIsNotSelected = ! ownProps.isSelected
		&& ! selectors.hasATicketSelected( state )
		&& ( ! selectors.hasCreatedTickets( state ) || ! getHasTicketsOnSale( state ) );

	return showIfBlockIsSelected || showIfBlockIsNotSelected;
};

const mapStateToProps = ( state, ownProps ) => ( {
	hasOverlay: getHasOverlay( state, ownProps ),
	hasProviders: selectors.hasTicketProviders(),
	showAvailability: ownProps.isSelected && selectors.hasCreatedTickets( state ),
	showInactiveBlock: getShowInactiveBlock( state, ownProps ),
} );

export default compose(
	withStore(),
	connect( mapStateToProps ),
)( Template );

