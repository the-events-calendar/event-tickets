import { Reducer } from 'redux';
import { StoreState } from '../types/StoreState';

const initialState: StoreState = {
	tickets: [],
	currentPostId: null,
	isLoading: false,
	error: null,
};

export type ActionTypes =
	| { type: 'FETCH_TICKETS_START'; payload: { postId: number } }
	| { type: 'FETCH_TICKETS_SUCCESS'; payload: { tickets: any[]; postId: number } }
	| { type: 'FETCH_TICKETS_ERROR'; payload: { error: string } }
	| { type: 'CREATE_TICKET_START' }
	| { type: 'CREATE_TICKET_SUCCESS'; payload: { ticket: any } }
	| { type: 'CREATE_TICKET_ERROR'; payload: { error: string } }
	| { type: 'UPDATE_TICKET_START' }
	| { type: 'UPDATE_TICKET_SUCCESS'; payload: { ticket: any } }
	| { type: 'UPDATE_TICKET_ERROR'; payload: { error: string } }
	| { type: 'DELETE_TICKET_START' }
	| { type: 'DELETE_TICKET_SUCCESS'; payload: { ticketId: number } }
	| { type: 'DELETE_TICKET_ERROR'; payload: { error: string } }
	| { type: 'SET_CURRENT_POST_ID'; payload: { postId: number } }
	| { type: 'CLEAR_ERROR' };

export const reducer: Reducer<StoreState, ActionTypes> = ( state = initialState, action ) => {
	switch ( action.type ) {
		case 'FETCH_TICKETS_START':
			return {
				...state,
				isLoading: true,
				error: null,
				currentPostId: action.payload.postId,
			};

		case 'FETCH_TICKETS_SUCCESS':
			return {
				...state,
				isLoading: false,
				tickets: action.payload.tickets,
				currentPostId: action.payload.postId,
				error: null,
			};

		case 'FETCH_TICKETS_ERROR':
			return {
				...state,
				isLoading: false,
				error: action.payload.error,
			};

		case 'CREATE_TICKET_START':
			return {
				...state,
				isLoading: true,
				error: null,
			};

		case 'CREATE_TICKET_SUCCESS':
			return {
				...state,
				isLoading: false,
				tickets: [ ...state.tickets, action.payload.ticket ],
				error: null,
			};

		case 'CREATE_TICKET_ERROR':
			return {
				...state,
				isLoading: false,
				error: action.payload.error,
			};

		case 'UPDATE_TICKET_START':
			return {
				...state,
				isLoading: true,
				error: null,
			};

		case 'UPDATE_TICKET_SUCCESS':
			return {
				...state,
				isLoading: false,
				tickets: state.tickets.map( ticket =>
					ticket.id === action.payload.ticket.id ? action.payload.ticket : ticket
				),
				error: null,
			};

		case 'UPDATE_TICKET_ERROR':
			return {
				...state,
				isLoading: false,
				error: action.payload.error,
			};

		case 'DELETE_TICKET_START':
			return {
				...state,
				isLoading: true,
				error: null,
			};

		case 'DELETE_TICKET_SUCCESS':
			return {
				...state,
				isLoading: false,
				tickets: state.tickets.filter( ticket => ticket.id !== action.payload.ticketId ),
				error: null,
			};

		case 'DELETE_TICKET_ERROR':
			return {
				...state,
				isLoading: false,
				error: action.payload.error,
			};

		case 'SET_CURRENT_POST_ID':
			return {
				...state,
				currentPostId: action.payload.postId,
			};

		case 'CLEAR_ERROR':
			return {
				...state,
				error: null,
			};

		default:
			return state;
	}
};
