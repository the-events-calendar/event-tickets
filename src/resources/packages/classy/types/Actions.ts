import { Ticket } from './Ticket';

export const SET_CURRENT_POST_ID = 'SET_CURRENT_POST_ID';
export const SET_TICKETS = 'SET_TICKETS';
export const SET_TICKETS_FOR_EVENT = 'SET_TICKETS_FOR_EVENT';

export type SetCurrentPostIdAction = {
	type: typeof SET_CURRENT_POST_ID;
	postId: number;
}

export type SetTicketsForEventAction = {
	type: typeof SET_TICKETS_FOR_EVENT;
	postId: number;
	tickets: Ticket[];
}

export type SetTicketsAction = {
	type: typeof SET_TICKETS;
	tickets: Ticket[];
}

export type Actions = {
	// Tickets CRUD actions
	fetchTickets: (postId: number) => Promise<void>;
	createTicket: (ticketData: Partial<Ticket>) => Promise<void>;
	updateTicket: (ticketId: number, ticketData: Partial<Ticket>) => Promise<void>;
	deleteTicket: (ticketId: number) => Promise<void>;
	setCurrentPostId: (postId: number) => void;
	clearError: () => void;
};
