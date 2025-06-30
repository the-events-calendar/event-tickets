import { Ticket } from './Ticket';

// Action type constants.
const SET_TICKETS_FOR_POST = 'SET_TICKETS_FOR_POST';

// Action type definitions.
type SetTicketsForPostAction = {
	type: typeof SET_TICKETS_FOR_POST;
	postId: number;
	tickets: Ticket[];
}

type StoreDispatch = {
	setTicketsForPost: ( postId: number, tickets: Ticket[] ) => SetTicketsForPostAction;
}

export {
	SET_TICKETS_FOR_POST,
	SetTicketsForPostAction,
	StoreDispatch
}
