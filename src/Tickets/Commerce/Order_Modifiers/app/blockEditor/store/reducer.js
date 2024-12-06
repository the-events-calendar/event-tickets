import defaultState from './default-state';
import { getTicketIdFromCommonStore } from "./common-store-bridge";

export const reducer = ( state = defaultState, action ) => {
	const clientId = action?.clientId;
	let ticketFees = [];
	let ticketPostId;

	switch ( action.type ) {
		case 'SET_ALL_FEES':
			return {
				...state,
				feesAvailable: action.feesAvailable,
				feesAutomatic: action.feesAutomatic,
			}

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

		case 'ADD_FEE_TO_TICKET':
			ticketPostId = getTicketIdFromCommonStore( clientId )
			ticketFees = state.selectedFeesByClientId[ clientId ] || [];
			ticketFees.push( action.feeId );

			return {
				...state,
				selectedFeesByClientId: {
					...state.selectedFeesByClientId,
					[ clientId ]: ticketFees,
				},
				selectedFeesByPostId: {
					...state.selectedFeesByPostId,
					[ ticketPostId ]: ticketFees,
				},
			};

		case 'REMOVE_FEE_FROM_TICKET':
			ticketPostId = getTicketIdFromCommonStore( clientId )
			ticketFees = state.selectedFeesByClientId[ clientId ] || [];

			const index = ticketFees.indexOf( action.feeId );
			if ( index > -1 ) {
				ticketFees.splice( index, 1 );
			}

			return {
				...state,
				selectedFeesByClientId: {
					...state.selectedFeesByClientId,
					[ clientId ]: ticketFees,
				},
				selectedFeesByPostId: {
					...state.selectedFeesByPostId,
					[ ticketPostId ]: ticketFees,
				},
			};

		case 'SET_SELECTED_FEES':
			ticketPostId = getTicketIdFromCommonStore( action.clientId );

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

		case 'SET_SELECTED_FEES_BY_POST_ID':
			ticketPostId = getTicketIdFromCommonStore( clientId );

			const { selectedFeesByPostId, selectedFeesByClientId } = state;
			const feesSelected = selectedFeesByClientId[ clientId ] || selectedFeesByPostId[ ticketPostId ];

			delete selectedFeesByPostId[ clientId ];

			return {
				...state,
				selectedFeesByPostId: {
					...selectedFeesByPostId,
					[ ticketPostId ]: feesSelected,
				},
			};

		case 'SET_DISPLAYED_FEES':
			return {
				...state,
				displayedFeesByClientId: {
					...state.displayedFeesByClientId,
					[ clientId ]: action.fees,
				},
			};

		case 'ADD_DISPLAYED_FEE':
			const displayedFees = state.displayedFeesByClientId[ clientId ] || [];
			const availableFeeIndex = state.feesAvailable.findIndex(
				( fee ) => fee.id === action.feeId
			);

			if ( availableFeeIndex === -1 ) {
				console.log( 'Fee not found in available fees.' );
				return state;
			}

			displayedFees.push( state.feesAvailable[ availableFeeIndex ] );

			return {
				...state,
				displayedFeesByClientId: {
					...state.displayedFeesByClientId,
					[ clientId ]: displayedFees,
				},
			};

		default:
			return state;
	}
};
