/**
 * Internal dependencies
 */
import * as types from '../../types';
import {
	getDefaultCurrencyPosition,
	getDefaultProviderCurrency,
	getDefaultProviderCurrencyDecimalPoint,
	getDefaultProviderCurrencyNumberOfDecimals,
	getDefaultProviderCurrencyThousandsSep,
} from '../../utils';
import details, { DEFAULT_STATE as DETAILS_DEFAULT_STATE } from './ticket/details';
import tempDetails, { DEFAULT_STATE as TEMP_DETAILS_DEFAULT_STATE } from './ticket/temp-details';

export const DEFAULT_STATE = {
	details: DETAILS_DEFAULT_STATE,
	tempDetails: TEMP_DETAILS_DEFAULT_STATE,
	sold: 0,
	available: 0,
	ticketId: 0,
	currencyDecimalPoint: getDefaultProviderCurrencyDecimalPoint(),
	currencyNumberOfDecimals: getDefaultProviderCurrencyNumberOfDecimals(),
	currencyPosition: getDefaultCurrencyPosition(),
	currencySymbol: getDefaultProviderCurrency(),
	currencyThousandsSep: getDefaultProviderCurrencyThousandsSep(),
	provider: '',
	hasAttendeeInfoFields: false,
	isLoading: false,
	isModalOpen: false,
	hasBeenCreated: false,
	hasChanges: false,
	hasDurationError: false,
	isSelected: false,
};

export default ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case types.SET_TICKET_ATTENDEE_INFO_FIELDS:
		case types.SET_TICKET_TITLE:
		case types.SET_TICKET_DESCRIPTION:
		case types.SET_TICKET_PRICE:
		case types.SET_TICKET_ON_SALE:
		case types.SET_TICKET_SKU:
		case types.SET_TICKET_IAC_SETTING:
		case types.SET_TICKET_START_DATE:
		case types.SET_TICKET_START_DATE_INPUT:
		case types.SET_TICKET_START_DATE_MOMENT:
		case types.SET_TICKET_END_DATE:
		case types.SET_TICKET_END_DATE_INPUT:
		case types.SET_TICKET_END_DATE_MOMENT:
		case types.SET_TICKET_START_TIME:
		case types.SET_TICKET_END_TIME:
		case types.SET_TICKET_START_TIME_INPUT:
		case types.SET_TICKET_END_TIME_INPUT:
		case types.SET_TICKET_CAPACITY_TYPE:
		case types.SET_TICKET_CAPACITY:
		case types.SET_TICKET_TYPE:
		case types.SET_TICKET_TYPE_DESCRIPTION:
		case types.SET_TICKET_TYPE_ICON_URL:
		case types.SET_TICKET_TYPE_NAME:
		case types.SET_TICKET_SALE_PRICE_CHECK:
		case types.SET_TICKET_SALE_PRICE:
		case types.SET_TICKET_SALE_START_DATE:
		case types.SET_TICKET_SALE_START_DATE_INPUT:
		case types.SET_TICKET_SALE_START_DATE_MOMENT:
		case types.SET_TICKET_SALE_END_DATE:
		case types.SET_TICKET_SALE_END_DATE_INPUT:
		case types.SET_TICKET_SALE_END_DATE_MOMENT:
			return {
				...state,
				details: details( state.details, action ),
			};
		case types.SET_TICKET_TEMP_TITLE:
		case types.SET_TICKET_TEMP_DESCRIPTION:
		case types.SET_TICKET_TEMP_PRICE:
		case types.SET_TICKET_TEMP_SKU:
		case types.SET_TICKET_TEMP_IAC_SETTING:
		case types.SET_TICKET_TEMP_START_DATE:
		case types.SET_TICKET_TEMP_START_DATE_INPUT:
		case types.SET_TICKET_TEMP_START_DATE_MOMENT:
		case types.SET_TICKET_TEMP_END_DATE:
		case types.SET_TICKET_TEMP_END_DATE_INPUT:
		case types.SET_TICKET_TEMP_END_DATE_MOMENT:
		case types.SET_TICKET_TEMP_START_TIME:
		case types.SET_TICKET_TEMP_END_TIME:
		case types.SET_TICKET_TEMP_START_TIME_INPUT:
		case types.SET_TICKET_TEMP_END_TIME_INPUT:
		case types.SET_TICKET_TEMP_CAPACITY_TYPE:
		case types.SET_TICKET_TEMP_CAPACITY:
		case types.SET_TICKET_TEMP_SALE_PRICE_CHECK:
		case types.SET_TICKET_TEMP_SALE_PRICE:
		case types.SET_TICKET_TEMP_SALE_START_DATE:
		case types.SET_TICKET_TEMP_SALE_START_DATE_INPUT:
		case types.SET_TICKET_TEMP_SALE_START_DATE_MOMENT:
		case types.SET_TICKET_TEMP_SALE_END_DATE:
		case types.SET_TICKET_TEMP_SALE_END_DATE_INPUT:
		case types.SET_TICKET_TEMP_SALE_END_DATE_MOMENT:
			return {
				...state,
				tempDetails: tempDetails( state.tempDetails, action ),
			};
		case types.SET_TICKET_SOLD:
			return {
				...state,
				sold: action.payload.sold,
			};
		case types.SET_TICKET_AVAILABLE:
			return {
				...state,
				available: action.payload.available,
			};
		case types.SET_TICKET_ID:
			return {
				...state,
				ticketId: action.payload.ticketId,
			};
		case types.SET_TICKET_CURRENCY_SYMBOL:
			return {
				...state,
				currencySymbol: action.payload.currencySymbol,
			};
		case types.SET_TICKET_CURRENCY_POSITION:
			return {
				...state,
				currencyPosition: action.payload.currencyPosition,
			};
		case types.SET_TICKET_PROVIDER:
			return {
				...state,
				provider: action.payload.provider,
			};
		case types.SET_TICKET_HAS_ATTENDEE_INFO_FIELDS:
			return {
				...state,
				hasAttendeeInfoFields: action.payload.hasAttendeeInfoFields,
			};
		case types.SET_TICKET_IS_LOADING:
			return {
				...state,
				isLoading: action.payload.isLoading,
			};
		case types.SET_TICKET_IS_MODAL_OPEN:
			return {
				...state,
				isModalOpen: action.payload.isModalOpen,
			};
		case types.SET_TICKET_HAS_BEEN_CREATED:
			return {
				...state,
				hasBeenCreated: action.payload.hasBeenCreated,
			};
		case types.SET_TICKET_HAS_CHANGES:
			return {
				...state,
				hasChanges: action.payload.hasChanges,
			};
		case types.SET_TICKET_HAS_DURATION_ERROR:
			return {
				...state,
				hasDurationError: action.payload.hasDurationError,
			};
		case types.SET_TICKET_IS_SELECTED:
			return {
				...state,
				isSelected: action.payload.isSelected,
			};
		default:
			return state;
	}
};
