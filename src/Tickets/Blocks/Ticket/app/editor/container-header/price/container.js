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

const mapStateToProps = ( state, ownProps ) => ( {
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
	/**
	 * Filters whether the ticket header shows the ticket's sale price.
	 *
	 * @since TBD
	 *
	 * @param {boolean} onSale   Whether the ticket is on sale, as the server last said.
	 * @param {string}  clientId The client ID of the ticket block.
	 */
	onSale: applyFilters(
		'tec.tickets.blocks.Ticket.onSale',
		selectors.getTicketOnSale( state, ownProps ),
		ownProps.clientId
	),
} );

const mapDispatchToProps = ( dispatch, ownProps ) => ( {
	onTempPriceChange: ( e ) => {
		const { clientId } = ownProps;
		dispatch( actions.setTicketTempPrice( clientId, e.target.value ) );
		dispatch( actions.setTicketHasChanges( clientId, true ) );
	},
} );

export default compose( withStore(), connect( mapStateToProps, mapDispatchToProps ) )( Template );
