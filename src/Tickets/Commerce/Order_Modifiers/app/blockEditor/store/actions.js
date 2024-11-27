export const actions = {

	/**
	 * Set all the fees.
	 *
	 * @param {Fee[]} feesAvailable
	 * @param {Fee[]} feesAutomatic
	 * @return {{allFees, type: string}}
	 */
	setAllFees( feesAvailable, feesAutomatic ) {
		return {
			type: 'SET_ALL_FEES',
			feesAvailable,
			feesAutomatic,
		};
	},

	/**
	 * Set the selected fees for a ticket.
	 *
	 * @param {string} clientId
	 * @param {int[]} feesSelected
	 * @return {{feesSelected, clientId, type: string}}
	 */
	setTicketFees( clientId, feesSelected ) {
		return {
			type: 'SET_SELECTED_FEES',
			clientId,
			feesSelected
		};
	},

	/**
	 * Add a fee to a ticket.
	 *
	 * @param {string} clientId
	 * @param {int} feeId
	 * @return {{clientId, type: string, feeId}}
	 */
	addFeeToTicket( clientId, feeId ) {
		return {
			type: 'ADD_FEE_TO_TICKET',
			clientId,
			feeId,
		};
	},

	/**
	 * Remove a fee from a ticket.
	 *
	 * @param {string} clientId
	 * @param {int} feeId
	 * @return {{clientId, type: string, feeId}}
	 */
	removeFeeFromTicket( clientId, feeId ) {
		return {
			type: 'REMOVE_FEE_FROM_TICKET',
			clientId,
			feeId,
		};
	},

	/**
	 * Set the automatic fees.
	 *
	 * @param {Fee[]} feesAutomatic
	 * @return {{feesAutomatic, type: string}}
	 */
	setAutomaticFees( feesAutomatic ) {
		return {
			type: 'SET_AUTOMATIC_FEES',
			feesAutomatic,
		};
	},

	/**
	 * Set the available fees.
	 *
	 * @param {Fee[]} feesAvailable
	 * @return {{feesAvailable, type: string}}
	 */
	setAvailableFees( feesAvailable ) {
		return {
			type: 'SET_AVAILABLE_FEES',
			feesAvailable,
		};
	},

	/**
	 * Fetch the fees from the API.
	 *
	 * @return {{type: string}}
	 */
	fetchFeesFromAPI() {
		return {
			type: 'FETCH_FEES_FROM_API',
		};
	},

	/**
	 * Set the selected fees for the post ID.
	 *
	 * @param {string} clientId
	 * @return {{clientId, type: string}}
	 */
	setFeesByPostId( clientId ) {
		return {
			type: 'SET_SELECTED_FEES_BY_POST_ID',
			clientId,
		}
	},

	/**
	 * Set the fees to be displayed.
	 *
	 * @param {string} clientId
	 * @param {Fee[]} fees
	 * @return {{fees, clientId, type: string}}
	 */
	setDisplayedFees( clientId, fees ) {
		return {
			type: 'SET_DISPLAYED_FEES',
			clientId,
			fees,
		}
	},

	/**
	 * Add a fee to the displayed fees.
	 *
	 * @param {string} clientId
	 * @param {int} feeId
	 * @return {{clientId, feeId, type: string}}
	 */
	addDisplayedFee( clientId, feeId ) {
		return {
			type: 'ADD_DISPLAYED_FEE',
			clientId,
			feeId,
		}
	}
};
