/**
 * External dependencies
 */
import moment from 'moment';

/**
 * Internal dependencies
 */
import * as constants from '../../../constants';
import * as types from '../../../types';
import { globals, moment as momentUtil } from '@moderntribe/common/utils';

const datePickerFormat = globals.tecDateSettings().datepickerFormat;
const currentMoment = moment();
const bufferDuration = globals.tickets().end_sale_buffer_duration ? globals.tickets().end_sale_buffer_duration : 2;
const bufferYears = globals.tickets().end_sale_buffer_years ? globals.tickets().end_sale_buffer_years : 1;
const endMoment = currentMoment.clone().add( bufferDuration, 'hours' ).add( bufferYears, 'years' );

const startDateInput = datePickerFormat
	? currentMoment.format( momentUtil.toFormat( datePickerFormat ) )
	: momentUtil.toDate( currentMoment );
const endDateInput = datePickerFormat
	? endMoment.format( momentUtil.toFormat( datePickerFormat ) )
	: momentUtil.toDate( endMoment );
const iac = globals.iacVars().iacDefault ? globals.iacVars().iacDefault : 'none';

export const DEFAULT_STATE = {
	title: '',
	description: '',
	price: '',
	sku: '',
	iac,
	startDate: momentUtil.toDatabaseDate( currentMoment ),
	startDateInput,
	startDateMoment: currentMoment,
	endDate: momentUtil.toDatabaseDate( endMoment ),
	endDateInput,
	endDateMoment: endMoment,
	startTime: momentUtil.toDatabaseTime( currentMoment ),
	endTime: momentUtil.toDatabaseTime( endMoment ),
	startTimeInput: momentUtil.toTime( currentMoment ),
	endTimeInput: momentUtil.toTime( endMoment ),
	capacityType: constants.TICKET_TYPES[ constants.UNLIMITED ],
	capacity: '',
	salePriceChecked: false,
	salePrice: '',
	saleStartDate: '',
	saleStartDateInput: '',
	saleStartDateMoment: '',
	saleEndDate: '',
	saleEndDateInput: '',
	saleEndDateMoment: '',
};

export default ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case types.SET_TICKET_TEMP_TITLE:
			return {
				...state,
				title: action.payload.title,
			};
		case types.SET_TICKET_TEMP_DESCRIPTION:
			return {
				...state,
				description: action.payload.description,
			};
		case types.SET_TICKET_TEMP_PRICE:
			return {
				...state,
				price: action.payload.price,
			};
		case types.SET_TICKET_TEMP_SKU:
			return {
				...state,
				sku: action.payload.sku,
			};
		case types.SET_TICKET_TEMP_IAC_SETTING:
			return {
				...state,
				iac: action.payload.iac,
			};
		case types.SET_TICKET_TEMP_START_DATE:
			return {
				...state,
				startDate: action.payload.startDate,
			};
		case types.SET_TICKET_TEMP_START_DATE_INPUT:
			return {
				...state,
				startDateInput: action.payload.startDateInput,
			};
		case types.SET_TICKET_TEMP_START_DATE_MOMENT:
			return {
				...state,
				startDateMoment: action.payload.startDateMoment,
			};
		case types.SET_TICKET_TEMP_END_DATE:
			return {
				...state,
				endDate: action.payload.endDate,
			};
		case types.SET_TICKET_TEMP_END_DATE_INPUT:
			return {
				...state,
				endDateInput: action.payload.endDateInput,
			};
		case types.SET_TICKET_TEMP_END_DATE_MOMENT:
			return {
				...state,
				endDateMoment: action.payload.endDateMoment,
			};
		case types.SET_TICKET_TEMP_START_TIME:
			return {
				...state,
				startTime: action.payload.startTime,
			};
		case types.SET_TICKET_TEMP_END_TIME:
			return {
				...state,
				endTime: action.payload.endTime,
			};
		case types.SET_TICKET_TEMP_START_TIME_INPUT:
			return {
				...state,
				startTimeInput: action.payload.startTimeInput,
			};
		case types.SET_TICKET_TEMP_END_TIME_INPUT:
			return {
				...state,
				endTimeInput: action.payload.endTimeInput,
			};
		case types.SET_TICKET_TEMP_CAPACITY_TYPE:
			return {
				...state,
				capacityType: action.payload.capacityType,
			};
		case types.SET_TICKET_TEMP_CAPACITY:
			return {
				...state,
				capacity: action.payload.capacity,
			};
		case types.SET_TICKET_TEMP_SALE_PRICE_CHECK:
			return {
				...state,
				salePriceChecked: action.payload.checked,
			};
		case types.SET_TICKET_TEMP_SALE_PRICE:
			return {
				...state,
				salePrice: action.payload.salePrice,
			};
		case types.SET_TICKET_TEMP_SALE_START_DATE:
			return {
				...state,
				saleStartDate: action.payload.startDate,
			};
		case types.SET_TICKET_TEMP_SALE_START_DATE_INPUT:
			return {
				...state,
				saleStartDateInput: action.payload.startDateInput,
			};
		case types.SET_TICKET_TEMP_SALE_START_DATE_MOMENT:
			return {
				...state,
				saleStartDateMoment: action.payload.startDateMoment,
			};
		case types.SET_TICKET_TEMP_SALE_END_DATE:
			return {
				...state,
				saleEndDate: action.payload.endDate,
			};
		case types.SET_TICKET_TEMP_SALE_END_DATE_INPUT:
			return {
				...state,
				saleEndDateInput: action.payload.endDateInput,
			};
		case types.SET_TICKET_TEMP_SALE_END_DATE_MOMENT:
			return {
				...state,
				saleEndDateMoment: action.payload.endDateMoment,
			};
		default:
			return state;
	}
};
