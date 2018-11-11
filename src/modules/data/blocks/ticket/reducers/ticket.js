/**
 * External dependencies
 */
import moment from 'moment/moment';

/**
 * Internal dependencies
 */
import * as types from './../types';
import { TICKET_TYPES } from '@moderntribe/tickets/data/utils';
import { moment as momentUtil } from '@moderntribe/common/utils';
import { getDefaultProviderCurrency } from '@moderntribe/tickets/data/utils';

const currentMoment = moment();
const ADDITIONAL_DAYS = 3;
export const DEFAULT_STATE = {
	title: '',
	description: '',
	price: '',
	SKU: '',
	startDate: momentUtil.toDate( currentMoment ),
	startDateMoment: currentMoment,
	endDate: momentUtil.toDate( currentMoment.clone().add( ADDITIONAL_DAYS, 'days' ) ),
	endDateMoment: currentMoment,
	startTime: momentUtil.toTime24Hr( currentMoment ),
	endTime: momentUtil.toTime24Hr( currentMoment ),
	dateIsPristine: false,
	capacityType: TICKET_TYPES.shared,
	capacity: '',
	isEditing: false,
	ticketId: 0,
	sold: 0,
	isLoading: false,
	hasBeenCreated: false,
	available: 0,
	provider: '',
	currencySymbol: getDefaultProviderCurrency(),
};

export default ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case types.SET_TICKET_TITLE:
			return {
				...state,
				title: action.payload.title,
			};
		case types.SET_TICKET_DESCRIPTION:
			return {
				...state,
				description: action.payload.description,
			};
		case types.SET_TICKET_PRICE:
			return {
				...state,
				price: action.payload.price,
			};
		case types.SET_TICKET_SKU:
			return {
				...state,
				SKU: action.payload.SKU,
			};
		case types.SET_TICKET_START_DATE:
			return {
				...state,
				startDate: action.payload.startDate,
			};
		case types.SET_TICKET_START_TIME:
			return {
				...state,
				startTime: action.payload.startTime,
			};
		case types.SET_TICKET_END_DATE:
			return {
				...state,
				endDate: action.payload.endDate,
			};
		case types.SET_TICKET_END_TIME:
			return {
				...state,
				endTime: action.payload.endTime,
			};
		case types.SET_TICKET_CAPACITY:
			return {
				...state,
				capacity: action.payload.capacity,
			};
		case types.SET_TICKET_CAPACITY_TYPE:
			return {
				...state,
				capacityType: action.payload.capacityType,
			};
		case types.SET_TICKET_IS_EDITING:
			return {
				...state,
				isEditing: action.payload.isEditing,
			};
		case types.SET_TICKET_ID:
			return {
				...state,
				ticketId: action.payload.ticketId,
			};
		case types.SET_TICKET_PROVIDER:
			return {
				...state,
				provider: action.payload.provider,
			};
		case types.SET_TICKET_CURRENCY:
			return {
				...state,
				currencySymbol: action.payload.currencySymbol,
			};
		case types.SET_TICKET_DATE_PRISTINE:
			return {
				...state,
				dateIsPristine: action.payload.dateIsPristine,
			};
		case types.SET_TICKET_START_DATE_MOMENT:
			return {
				...state,
				startDateMoment: action.payload.startDateMoment,
			};
		case types.SET_TICKET_END_DATE_MOMENT:
			return {
				...state,
				endDateMoment: action.payload.endDateMoment,
			};
		case types.SET_TICKET_IS_LOADING:
			return {
				...state,
				isLoading: action.payload.isLoading,
			};
		case types.SET_TICKET_HAS_BEEN_CREATED:
			return {
				...state,
				hasBeenCreated: action.payload.hasBeenCreated,
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
		default:
			return state;
	}
};
