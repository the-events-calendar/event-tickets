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
		const ticketPostId = getTicketIdFromCommonStore( clientId );

		return (
			state?.selectedFeesByClientId?.[ clientId ] ||
			state?.selectedFeesByPostId?.[ ticketPostId ] ||
			[]
		);
	},
};
