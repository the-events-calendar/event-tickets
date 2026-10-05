export const actions = {
	/**
	 * Set all the fees.
	 *
	 * @param {Object[]} feesAvailable The available fees.
	 * @param {Object[]} feesAutomatic The automatic fees.
	 * @return {{allFees, type: string}} The action object.
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
	 * @param {string}   clientId     The block client ID.
	 * @param {number[]} feesSelected The IDs of the selected fees.
	 * @return {{feesSelected, clientId, type: string}} The action object.
	 */
	setTicketFees( clientId, feesSelected ) {
		return {
			type: 'SET_SELECTED_FEES',
			clientId,
			feesSelected,
		};
	},

	/**
	 * Add a fee to a ticket.
	 *
	 * @param {string} clientId The block client ID.
	 * @param {number} feeId    The fee ID.
	 * @return {{clientId, type: string, feeId}} The action object.
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
	 * @param {string} clientId The block client ID.
	 * @param {number} feeId    The fee ID.
	 * @return {{clientId, type: string, feeId}} The action object.
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
	 * @param {Object[]} feesAutomatic The automatic fees.
	 * @return {{feesAutomatic, type: string}} The action object.
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
	 * @param {Object[]} feesAvailable The available fees.
	 * @return {{feesAvailable, type: string}} The action object.
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
	 * @return {{type: string}} The action object.
	 */
	fetchFeesFromAPI() {
		return {
			type: 'FETCH_FEES_FROM_API',
		};
	},

	/**
	 * Set the selected fees for the post ID.
	 *
	 * @param {string} clientId The block client ID.
	 * @return {{clientId, type: string}} The action object.
	 */
	setFeesByPostId( clientId ) {
		return {
			type: 'SET_SELECTED_FEES_BY_POST_ID',
			clientId,
		};
	},

	/**
	 * Set the fees to be displayed.
	 *
	 * @param {string}   clientId The block client ID.
	 * @param {Object[]} fees     The fees.
	 * @return {{fees, clientId, type: string}} The action object.
	 */
	setDisplayedFees( clientId, fees ) {
		return {
			type: 'SET_DISPLAYED_FEES',
			clientId,
			fees,
		};
	},

	/**
	 * Add a fee to the displayed fees.
	 *
	 * @param {string} clientId The block client ID.
	 * @param {number} feeId    The fee ID.
	 * @return {{clientId, feeId, type: string}} The action object.
	 */
	addDisplayedFee( clientId, feeId ) {
		return {
			type: 'ADD_DISPLAYED_FEE',
			clientId,
			feeId,
		};
	},
};
