/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';
import { isString } from 'lodash';

/**
 * Internal dependencies
 */
import Template from './template';
import { withStore } from '@moderntribe/common/hoc';
import { selectors, actions } from '@moderntribe/tickets/data/blocks/ticket';
import { globals, moment as momentUtil } from "@moderntribe/common/utils";

/**
 * Handles the change event of the from date input.
 *
 * @since 5.9.0
 *
 * @param {Function} dispatch The dispatch function.
 * @param {Object} ownProps The component's own props.
 *
 * @returns {Function} The change event handler.
 */
const onFromDateChange = ( dispatch, ownProps ) => ( date, modifiers, dayPickerInput ) => {
	const { clientId } = ownProps;

	if (
		( isString( dayPickerInput ) && dayPickerInput === '' )
		|| ( ! isString( dayPickerInput ) && dayPickerInput.input.value === '' )
	) {
		dispatch( actions.setTicketTempSaleStartDate( clientId, '' ) );
		dispatch( actions.setTicketTempSaleStartDateMoment( clientId, '' ) );
		dispatch( actions.setTicketTempSaleStartDateInput( clientId, '' ) );
		dispatch( actions.setTicketHasChanges( clientId, true ) );
		return;
	}

	dispatch( actions.processTicketSaleStartDate( clientId, date, dayPickerInput ) );
};

/**
 * Handles the change event of the date picker input.
 *
 * @since 5.9.0
 *
 * @param dispatch The dispatch function.
 * @param ownProps The component's own props.
 *
 * @returns {Function} The change event handler.
 */
const onToDateChange = ( dispatch, ownProps ) => ( date, modifiers, dayPickerInput ) => {
	const { clientId } = ownProps;

	if (
		( isString( dayPickerInput ) && dayPickerInput === '' )
		|| ( ! isString( dayPickerInput ) && dayPickerInput.input.value === '' )
	) {
		dispatch( actions.setTicketTempSaleEndDate( clientId, '' ) );
		dispatch( actions.setTicketTempSaleEndDateMoment( clientId, '' ) );
		dispatch( actions.setTicketTempSaleEndDateInput( clientId, '' ) );
		dispatch( actions.setTicketHasChanges( clientId, true ) );
		return;
	}

	dispatch( actions.processTicketSaleEndDate( clientId, date, dayPickerInput ) );
};

/**
 * Maps the state to the component's props.
 *
 * @since 5.9.0
 *
 * @param state The state.
 * @param ownProps The component's own props.
 *
 * @returns {Object} The component's props.
 */
const mapStateToProps = ( state, ownProps ) => {
	const datePickerFormat = globals.tecDateSettings().datepickerFormat
		? momentUtil.toFormat( globals.tecDateSettings().datepickerFormat )
		: 'LL';

	const startDateMoment = selectors.getTicketTempSaleStartDateMoment( state, ownProps );
	const endDateMoment = selectors.getTicketTempSaleEndDateMoment( state, ownProps );
	const fromDate = startDateMoment && startDateMoment.toDate();
	const toDate = endDateMoment && endDateMoment.toDate();
	const fromDateInput = typeof startDateMoment === 'object' && startDateMoment.isValid() ? selectors.getTicketTempSaleStartDateInput( state, ownProps ) : '';
	const toDateInput = typeof endDateMoment === 'object' && endDateMoment.isValid() ? selectors.getTicketTempSaleEndDateInput( state, ownProps ) : '';

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
		fromDateInput: fromDateInput,
		toDateInput: toDateInput,
		validSalePrice: selectors.isTicketSalePriceValid( state, ownProps ),
	};
};

/**
 * Maps dispatch functions to the component's props.
 *
 * @since 5.9.0
 *
 * @param {Function} dispatch The dispatch function.
 * @param {Object} ownProps The component's own props.
 *
 * @returns {Object} The component's props.
 */
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

/**
 * Connects the component to the store and exports it.
 */
export default compose(
	withStore(),
	connect(
		mapStateToProps,
		mapDispatchToProps,
	),
)( Template );
