import { Ticket } from './Ticket';

export type Actions = {
	// Tickets CRUD actions
	fetchTickets: (postId: number) => Promise<void>;
	createTicket: (ticketData: Partial<Ticket>) => Promise<void>;
	updateTicket: (ticketId: number, ticketData: Partial<Ticket>) => Promise<void>;
	deleteTicket: (ticketId: number) => Promise<void>;
	setCurrentPostId: (postId: number) => void;
	clearError: () => void;
};
