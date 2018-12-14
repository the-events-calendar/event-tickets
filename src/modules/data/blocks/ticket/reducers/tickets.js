/**
 * External dependencies
 */
import { combineReducers } from 'redux';
import omit from 'lodash/omit';

/**
 * Internal dependencies
 */
import * as types from '@moderntribe/tickets/data/blocks/ticket/types';
import ticket from './tickets/ticket';

export const byId = ( state = {}, action ) => {
	switch ( action.type ) {
		case types.SET_TICKET_TITLE:
		case types.SET_TICKET_DESCRIPTION:
		case types.SET_TICKET_PRICE:
		case types.SET_TICKET_SKU:
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
		case types.SET_TICKET_TEMP_TITLE:
		case types.SET_TICKET_TEMP_DESCRIPTION:
		case types.SET_TICKET_TEMP_PRICE:
		case types.SET_TICKET_TEMP_SKU:
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
		case types.SET_TICKET_SOLD:
		case types.SET_TICKET_AVAILABLE:
		case types.SET_TICKET_ID:
		case types.SET_TICKET_CURRENCY_SYMBOL:
		case types.SET_TICKET_CURRENCY_POSITION:
		case types.SET_TICKET_PROVIDER:
		case types.SET_TICKET_IS_LOADING:
		case types.SET_TICKET_HAS_BEEN_CREATED:
		case types.SET_TICKET_HAS_CHANGES:
		case types.SET_TICKET_IS_SELECTED:
		case types.REGISTER_TICKET_BLOCK:
			return {
				...state,
				[ action.payload.blockId ]: ticket( state[ action.payload.blockId ], action ),
			};
		case types.REMOVE_TICKET_BLOCK:
			return omit( state, [ action.payload.blockId ] );
		default:
			return state;
	}
};

export const allIds = ( state = [], action ) => {
	switch ( action.type ) {
		case types.REGISTER_TICKET_BLOCK:
			return [ ...state, action.payload.blockId ];
		case types.REMOVE_TICKET_BLOCK:
			return state.filter( ( id ) => action.payload.blockId !== id );
		default:
			return state;
	}
};

export default combineReducers( {
	byId,
	allIds,
} );
