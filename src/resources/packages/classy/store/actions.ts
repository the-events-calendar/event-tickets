import { Ticket } from '../types/Ticket';
// import {
// 	SetTicketsForPostAction
// } from '../types/StoreActions.d.ts';

export const SET_TICKETS_FOR_POST = 'SET_TICKETS_FOR_POST';

type SetTicketsForPostAction = {
	type: typeof SET_TICKETS_FOR_POST;
	postId: number;
	tickets: Ticket[];
}

// export function setTicketsForPost(
// 	postId: number,
// 	tickets: Ticket[]
// ): SetTicketsForPostAction {
// 	return {
// 		type: SET_TICKETS_FOR_POST,
// 		payload: {
// 			postId: postId,
// 			tickets: tickets,
// 		}
// 	};
// }

export default {
	setTicketsForPost: ( options ): SetTicketsForPostAction => ( { type: SET_TICKETS_FOR_POST, options } ),
};
