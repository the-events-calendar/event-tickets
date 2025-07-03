import { Action } from 'redux';
import { Ticket } from './Ticket';

export const SET_LOADING = 'SET_LOADING';
export const SET_TICKETS = 'SET_TICKETS';
export const SET_TICKETS_FOR_EVENT = 'SET_TICKETS_FOR_EVENT';

export type SetLoadingAction = {
	isLoading: boolean;
} & Action<typeof SET_LOADING>;

export type SetTicketsForEventAction = {
	postId: number;
	tickets: Ticket[];
} & Action<typeof SET_TICKETS_FOR_EVENT>;

export type SetTicketsAction = {
	tickets: Ticket[];
} & Action<typeof SET_TICKETS>;

export type Actions = {
	fetchTickets: ( postId: number ) => Promise<void>;
	createTicket: ( ticketData: Partial<Ticket> ) => Promise<void>;
	updateTicket: ( ticketId: number, ticketData: Partial<Ticket> ) => Promise<void>;
	deleteTicket: ( ticketId: number ) => Promise<void>;
	clearError: () => void;
};
