import defaultState from './default-state';
import { getTicketIdFromCommonStore } from "./common-store-bridge";

export const reducer = ( state = defaultState, action ) => {
	switch ( action.type ) {
		case 'SET_TICKET_FEES':
			return {
				...state,
				ticketFees: action.ticketFees,
			};

		case 'SET_AUTOMATIC_FEES':
			return {
				...state,
				feesAutomatic: action.feesAutomatic,
			};

		case 'SET_AVAILABLE_FEES':
			return {
				...state,
				feesAvailable: action.feesAvailable,
			};

		case 'SET_SELECTED_FEES':
			const ticketPostId = getTicketIdFromCommonStore( action.clientId );

			// If null or empty array, remmove the selected fees.
			if ( ! action.feesSelected || ! action.feesSelected.length ) {
				const { selectedFeesByPostId, selectedFeesByClientId } = state;

				delete selectedFeesByPostId[ ticketPostId ];
				delete selectedFeesByClientId[ action.clientId ];

				return {
					...state,
					selectedFeesByPostId,
					selectedFeesByClientId,
				};
			}

			return {
				...state,
				selectedFeesByPostId: {
					...state.selectedFeesByPostId,
					[ ticketPostId ]: action.feesSelected,
				},
				selectedFeesByClientId: {
					...state.selectedFeesByClientId,
					[ action.clientId ]: action.feesSelected,
				},
			};

		default:
			return state;
	}
};
