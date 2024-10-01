import defaultState from './default-state';

export default ( state = defaultState, action ) => {
	switch ( action.type ) {
		case 'SET_TICKET_FEES':
			return {
				...state,
				ticketFees: action.ticketFees,
			};
		default:
			return state;
	}
};
