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
};
