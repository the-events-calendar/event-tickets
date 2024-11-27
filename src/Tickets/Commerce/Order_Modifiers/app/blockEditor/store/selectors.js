import { getTicketIdFromCommonStore } from './common-store-bridge';

export const selectors = {

	getAllFees( state ) {
		return {
			feesAvailable: state.feesAvailable,
			feesAutomatic: state.feesAutomatic,
		};
	},

	getSelectedFees( state, clientId ) {
		const ticketPostId = getTicketIdFromCommonStore( clientId );

		return (
			state?.selectedFeesByClientId?.[ clientId ] ||
			state?.selectedFeesByPostId?.[ ticketPostId ] ||
			[]
		);
	},

	getDisplayedFees( state, clientId ) {
		// Determine whether fees are already set for this client.
		const { displayedFeesByClientId } = state;
		if ( displayedFeesByClientId.hasOwnProperty( clientId ) ) {
			return displayedFeesByClientId[ clientId ];
		}

		// Use the state to determine the displayed fees from available and selected.
		const availableFees = state.feesAvailable;
		const ticketPostId = getTicketIdFromCommonStore( clientId );
		const selectedFees = state?.selectedFeesByClientId?.[ clientId ] ||
			state?.selectedFeesByPostId?.[ ticketPostId ] ||
			[];

		const displayedFees = [];

		selectedFees.forEach( ( feeId ) => {
			const fee = availableFees.find( ( fee ) => fee.id === feeId );
			if ( fee ) {
				displayedFees.push( fee );
			}
		} );

		return displayedFees;
	},
};
