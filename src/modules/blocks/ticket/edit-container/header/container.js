/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import Template from './template';
import { withStore } from '@moderntribe/common/src/modules/hoc';
import { selectors, actions } from '@moderntribe/tickets/data/blocks/ticket';

const mapStateToProps = ( state, ownProps ) => ( {
	title: selectors.getTicketTitle( state, ownProps ),
	description: selectors.getTicketDescription( state, ownProps ),
	price: selectors.getTicketPrice( state, ownProps ),
	provider: selectors.getSelectedProvider( state, ownProps ),
	currencySymbol: selectors.getTicketCurrency( state, ownProps ),
} );

const mapDispatchToProps = ( dispatch, ownProps ) => ( {
	setTitle( title ) {
		const { blockId } = ownProps;
		dispatch( actions.setTitle( blockId, title ) );
	},
	setDescription( description ) {
		const { blockId } = ownProps;
		dispatch( actions.setDescription( blockId, description ) );
	},
	setPrice( price ) {
		const { blockId } = ownProps;
		dispatch( actions.setPrice( blockId, price ) );
	},
	setTicketCurrency( currencySymbol ) {
		const { blockId } = ownProps;
		dispatch( actions.setTicketCurrency( blockId, currencySymbol ) );
	},
} );

export default compose(
	withStore(),
	connect(
		mapStateToProps,
		mapDispatchToProps,
	),
)( Template );
