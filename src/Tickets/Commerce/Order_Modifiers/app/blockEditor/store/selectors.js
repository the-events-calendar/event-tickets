import { getTicketIdFromCommonStore } from './common-store-bridge';
import { currentProviderSupportsFees } from './compatibility';


export const selectors = {

	shouldShowFees( state ) {
		return currentProviderSupportsFees();
	},

	getAvailableFees( state ) {
		return state.feesAvailable;
	},

	getAutomaticFees( state ) {
		return state.feesAutomatic;
	},

	getSelectedFees( state, clientId ) {
		if ( ! state.shouldShowFees() ) {
			return [];
		}

		const ticketPostId = getTicketIdFromCommonStore( clientId );

		return (
			state?.selectedFeesByPostId?.[ ticketPostId ] ||
			state?.selectedFeesByClientId?.[ clientId ] ||
			[]
		);
	},
};
