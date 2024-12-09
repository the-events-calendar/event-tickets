/**
 * Internal dependencies
 */
import tickets from './reducers/tickets';
import headerImage, { DEFAULT_STATE as HEADER_IMAGE_DEFAULT_STATE } from './reducers/header-image';
import * as types from './types';

export const DEFAULT_STATE = {
	headerImage: HEADER_IMAGE_DEFAULT_STATE,
	isSelected: false,
	isSettingsOpen: false,
	isSettingsLoading: false,
	provider: '',
	sharedCapacity: '',
	tempSharedCapacity: '',
	tickets: tickets( undefined, {} ),
};

export default ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case types.SET_TICKETS_HEADER_IMAGE:
			return {
				...state,
				headerImage: headerImage( state.headerImage, action ),
			};
		case types.SET_TICKETS_IS_SELECTED:
			return {
				...state,
				isSelected: action.payload.isSelected,
			};
		case types.SET_TICKETS_IS_SETTINGS_OPEN:
			return {
				...state,
				isSettingsOpen: action.payload.isSettingsOpen,
			};
		case types.SET_TICKETS_IS_SETTINGS_LOADING:
			return {
				...state,
				isSettingsLoading: action.payload.isSettingsLoading,
			};
		case types.SET_TICKETS_PROVIDER:
			return {
				...state,
				provider: action.payload.provider,
			};
		case types.SET_TICKETS_SHARED_CAPACITY:
			return {
				...state,
				sharedCapacity: action.payload.sharedCapacity,
			};
		case types.SET_TICKETS_TEMP_SHARED_CAPACITY:
			return {
				...state,
				tempSharedCapacity: action.payload.tempSharedCapacity,
			};
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
		case types.SET_TICKET_SALE_PRICE_CHECK:
		case types.SET_TICKET_SALE_PRICE:
		case types.SET_TICKET_SALE_START_DATE:
		case types.SET_TICKET_SALE_START_DATE_INPUT:
		case types.SET_TICKET_SALE_START_DATE_MOMENT:
		case types.SET_TICKET_SALE_END_DATE:
		case types.SET_TICKET_SALE_END_DATE_INPUT:
		case types.SET_TICKET_SALE_END_DATE_MOMENT:
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
		case types.SET_TICKET_SOLD:
		case types.SET_TICKET_AVAILABLE:
		case types.SET_TICKET_ID:
		case types.SET_TICKET_CURRENCY_SYMBOL:
		case types.SET_TICKET_CURRENCY_POSITION:
		case types.SET_TICKET_PROVIDER:
		case types.SET_TICKET_HAS_ATTENDEE_INFO_FIELDS:
		case types.SET_TICKET_ATTENDEE_INFO_FIELDS:
		case types.SET_TICKET_IS_LOADING:
		case types.SET_TICKET_IS_MODAL_OPEN:
		case types.SET_TICKET_HAS_BEEN_CREATED:
		case types.SET_TICKET_HAS_CHANGES:
		case types.SET_TICKET_HAS_DURATION_ERROR:
		case types.SET_TICKET_IS_SELECTED:
		case types.SET_TICKET_TYPE:
		case types.SET_TICKET_TYPE_DESCRIPTION:
		case types.SET_TICKET_TYPE_ICON_URL:
		case types.SET_TICKET_TYPE_NAME:
		case types.REGISTER_TICKET_BLOCK:
		case types.REMOVE_TICKET_BLOCK:
		case types.REMOVE_TICKET_BLOCKS:
			return {
				...state,
				tickets: tickets( state.tickets, action ),
			};
		case types.SET_UNEDITABLE_TICKETS:
			return {
				...state,
				uneditableTickets: action.payload.uneditableTickets,
				uneditableTicketsLoading: false,
			};
		case types.SET_UNEDITABLE_TICKETS_LOADING:
			if ( action.loading ) {
				return {
					...state,
					uneditableTickets: [],
					uneditableTicketsLoading: true,
				};
			}
			return {
				...state,
				uneditableTicketsLoading: false,
			};

		default:
			return state;
	}
};
