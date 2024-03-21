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
	isDisabled: selectors.isTicketDisabled( state, ownProps ),
	minDefaultPrice: selectors.isZeroPriceValid( state, ownProps ) ? 0 : 1,
	tempPrice: selectors.getTicketTempPrice( state, ownProps ),
	showSalePrice: selectors.showSalePrice( state, ownProps ),
	clientId: ownProps.clientId,
} );

const mapDispatchToProps = ( dispatch, ownProps ) => ( {
	onTempPriceChange: ( e ) => {
		const { clientId } = ownProps;
		dispatch( actions.setTicketTempPrice( clientId, e.value ) );
		dispatch( actions.setTicketHasChanges( clientId, true ) );
	},
} );

export default compose(
	withStore(),
	connect(
		mapStateToProps,
		mapDispatchToProps,
	),
)( Template );
