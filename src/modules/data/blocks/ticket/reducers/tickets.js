/**
 * External dependencies
 */
import { combineReducers } from 'redux';
import omit from 'lodash/omit';

/**
 * Internal dependencies
 */
import * as types from './../types';
import ticket from './ticket';

const ticketsById = ( state = {}, action ) => {
	switch ( action.type ) {
		case types.SET_TICKET_BLOCK_ID:
		case types.SET_TICKET_TITLE:
		case types.SET_TICKET_DESCRIPTION:
		case types.SET_TICKET_PRICE:
		case types.SET_TICKET_SKU:
		case types.SET_TICKET_START_DATE:
		case types.SET_TICKET_START_TIME:
		case types.SET_TICKET_END_DATE:
		case types.SET_TICKET_END_TIME:
		case types.SET_TICKET_CAPACITY_TYPE:
		case types.SET_TICKET_CAPACITY:
		case types.SET_TICKET_IS_EDITING:
		case types.SET_TICKET_ID:
		case types.SET_TICKET_DATE_PRISTINE:
		case types.SET_TICKET_START_DATE_MOMENT:
		case types.SET_TICKET_END_DATE_MOMENT:
		case types.SET_TICKET_IS_LOADING:
		case types.SET_TICKET_HAS_BEEN_CREATED:
		case types.SET_TICKET_SOLD:
		case types.SET_TICKET_AVAILABLE:
		case types.SET_TICKET_CURRENCY:
		case types.SET_TICKET_PROVIDER:
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

const allTickets = ( state = [], action ) => {
	switch ( action.type ) {
		case types.SET_TICKET_BLOCK_ID:
			return [ ...state, action.payload.blockId ];
		case types.REMOVE_TICKET_BLOCK:
			return state.filter( ( id ) => action.payload.blockId !== id );
		default:
			return state;
	}
};

export default combineReducers( {
	byId: ticketsById,
	allIds: allTickets,
} );
