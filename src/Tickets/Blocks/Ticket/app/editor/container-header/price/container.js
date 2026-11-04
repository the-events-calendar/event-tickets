/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import Template from './template';
import { withStore } from '@moderntribe/common/hoc';
import { selectors, actions } from '../../../../../../../modules/data/blocks/ticket';

const mapStateToProps = ( state, ownProps ) => {
	const mappedProps = {
		available: selectors.getTicketAvailable( state, ownProps ),
		currencyDecimalPoint: selectors.getTicketCurrencyDecimalPoint( state, ownProps ),
		currencyNumberOfDecimals: selectors.getTicketCurrencyNumberOfDecimals( state, ownProps ),
		currencyPosition: selectors.getTicketCurrencyPosition( state, ownProps ),
		currencySymbol: selectors.getTicketCurrencySymbol( state, ownProps ),
		currencyThousandsSep: selectors.getTicketCurrencyThousandsSep( state, ownProps ),
		isDisabled: selectors.isTicketDisabled( state, ownProps ),
		isUnlimited: selectors.isUnlimitedTicket( state, ownProps ),
		price: selectors.getTicketPrice( state, ownProps ) || '0',
		tempPrice: selectors.getTicketTempPrice( state, ownProps ),
		showSalePrice: selectors.showSalePrice( state, ownProps ),
		salePrice: selectors.getSalePrice( state, ownProps ) || '',
		onSale: selectors.getTicketOnSale( state, ownProps ),
	};

	/**
	 * Filters the properties mapped from the state for a ticket's price and the amount under it.
	 *
	 * @since TBD
	 *
	 * @param {Object} mappedProps      The mapped props; `available` and `amountLabel` make the line under the price.
	 * @param {Object} context.state    The state of the block.
	 * @param {Object} context.ownProps The props passed to the block.
	 */
	return applyFilters( 'tec.tickets.blocks.Ticket.Price.mappedProps', mappedProps, { state, ownProps } );
};

const mapDispatchToProps = ( dispatch, ownProps ) => ( {
	onTempPriceChange: ( e ) => {
		const { clientId } = ownProps;
		dispatch( actions.setTicketTempPrice( clientId, e.target.value ) );
		dispatch( actions.setTicketHasChanges( clientId, true ) );
	},
} );

export default compose( withStore(), connect( mapStateToProps, mapDispatchToProps ) )( Template );
