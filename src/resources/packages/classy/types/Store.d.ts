import { TicketSettings } from './Ticket';

export type StoreState = {
	tickets: TicketSettings[] | null;
	loading?: boolean;
};

/**
 * The StoreSelect type defines the shape of the selectors used in the store.
 *
 * @since TBD
 */
export type StoreSelect = {
	getTickets: ( eventId: number ) => TicketSettings[];
	getTicketById: ( ticketId: number ) => TicketSettings | undefined;
	isLoading: () => boolean;
};

/**
 * The StoreDispatch type defines the shape of the actions used in the store.
 *
 * @since TBD
 */
export type StoreDispatch = {
	addTicket: ( ticket: TicketSettings ) => void;
	deleteTicket: ( ticketId: number ) => void;
	setIsLoading: ( isLoading: boolean ) => void;
	setTickets: ( tickets: TicketSettings[] ) => void;
	updateTicket: ( ticketId: number, ticketData: TicketSettings ) => void;
};

/**
 * This type defines selectors for the core/editor store that we use in our application.
 *
 * Note that these selectors are not part of the Classy package, but are used in conjunction with it.
 *
 * @since TBD
 */
export type CoreEditorSelect = {
	getCurrentPostId: () => number | null;
	getEditedPostAttribute: ( attribute: string ) => any;
};

/**
 * This type defines the dispatch actions for the core/editor store that we use in our application.
 *
 * Note that these actions are not part of the Classy package, but are used in conjunction with it.
 *
 * @since TBD
 */
export type CoreEditorDispatch = {
	editPost: ( attributes: Record<string, any> ) => void;
}
