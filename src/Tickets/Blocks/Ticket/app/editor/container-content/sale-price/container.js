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
import { selectors, actions } from '@moderntribe/tickets/data/blocks/ticket';

const mapStateToProps = ( state, ownProps ) => ( {
	currencyDecimalPoint: selectors.getTicketCurrencyDecimalPoint( state, ownProps ),
	currencyNumberOfDecimals: selectors.getTicketCurrencyNumberOfDecimals( state, ownProps ),
	currencyPosition: selectors.getTicketCurrencyPosition( state, ownProps ),
	currencySymbol: selectors.getTicketCurrencySymbol( state, ownProps ),
	currencyThousandsSep: selectors.getTicketCurrencyThousandsSep( state, ownProps ),
	minDefaultPrice: selectors.isZeroPriceValid( state, ownProps ) ? 0 : 1,
	tempPrice: selectors.getTicketTempPrice( state, ownProps ),
	salePriceChecked: selectors.getTempSalePriceChecked( state, ownProps ),
	salePrice: selectors.getTempSalePrice( state, ownProps ),
} );

const mapDispatchToProps = ( dispatch, ownProps ) => ( {
	toggleSalePrice: ( e ) => {
		const { clientId } = ownProps;
		dispatch( actions.setTempSalePriceChecked( clientId, e.target.checked ) );
		dispatch( actions.setTicketHasChanges( clientId, true ) );
	},

	updateSalePrice: ( e ) => {
		const { clientId } = ownProps;
		dispatch( actions.setTempSalePrice( clientId, e.value ) );
		dispatch( actions.setTicketHasChanges( clientId, true ) );
	}
} );

export default compose(
	withStore(),
	connect(
		mapStateToProps,
		mapDispatchToProps,
	),
)( Template );
