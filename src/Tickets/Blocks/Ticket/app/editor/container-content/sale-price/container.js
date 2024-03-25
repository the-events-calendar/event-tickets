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
import {globals, moment as momentUtil} from "@moderntribe/common/utils";

const onFromDateChange = ( dispatch, ownProps ) => ( date, modifiers, dayPickerInput ) => {
	console.log( 'onFromDateChange', date, modifiers, dayPickerInput );
	dispatch( actions.processTicketSaleStartDate( ownProps.clientId, date, dayPickerInput ) );
};

const onToDateChange = ( dispatch, ownProps ) => ( date, modifiers, dayPickerInput ) => {
	console.log( 'onToDateChange', date, modifiers, dayPickerInput );
	dispatch( actions.processTicketSaleEndDate( ownProps.clientId, date, dayPickerInput ) );
};

const mapStateToProps = ( state, ownProps ) => {
	const datePickerFormat = globals.tecDateSettings().datepickerFormat
		? momentUtil.toFormat( globals.tecDateSettings().datepickerFormat )
		: 'LL';

	const startDateMoment = selectors.getTicketTempSaleStartDateMoment( state, ownProps );
	const endDateMoment = selectors.getTicketTempSaleEndDateMoment( state, ownProps );
	const fromDate = startDateMoment && startDateMoment.toDate();
	const toDate = endDateMoment && endDateMoment.toDate();

	const validSalePrice = selectors.isTicketSalePriceValid( state, ownProps );

	return {
		isDisabled: selectors.isTicketDisabled( state, ownProps ),
		currencyDecimalPoint: selectors.getTicketCurrencyDecimalPoint( state, ownProps ),
		currencyNumberOfDecimals: selectors.getTicketCurrencyNumberOfDecimals( state, ownProps ),
		currencyPosition: selectors.getTicketCurrencyPosition( state, ownProps ),
		currencySymbol: selectors.getTicketCurrencySymbol( state, ownProps ),
		currencyThousandsSep: selectors.getTicketCurrencyThousandsSep( state, ownProps ),
		minDefaultPrice: selectors.isZeroPriceValid( state, ownProps ) ? 0 : 1,
		tempPrice: selectors.getTicketTempPrice( state, ownProps ),
		salePriceChecked: selectors.getTempSalePriceChecked( state, ownProps ),
		salePrice: selectors.getTempSalePrice( state, ownProps ),
		dateFormat: datePickerFormat,
		fromDate: fromDate,
		toDate: toDate,
		fromDateInput: selectors.getTicketTempSaleStartDateInput( state, ownProps ),
		toDateInput: selectors.getTicketTempSaleEndDateInput( state, ownProps ),
		validSalePrice,
	}
};

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
	},

	onFromDateChange: onFromDateChange( dispatch, ownProps ),
	onToDateChange: onToDateChange( dispatch, ownProps ),
} );

export default compose(
	withStore(),
	connect(
		mapStateToProps,
		mapDispatchToProps,
	),
)( Template );
