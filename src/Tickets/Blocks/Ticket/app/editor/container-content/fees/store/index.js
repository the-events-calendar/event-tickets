/**
 * External dependencies
 */
import { createReduxStore, register } from '@wordpress/data';

/**
 * Internal dependencies
 */
// import actions from './actions';
import reducer from './reducer';
// import selectors from './selector';



const actions = {
	/**
	 * Adds a new fee to the list of fees.
	 *
	 * @param {Object} fee Fee to add.
	 * @return {Object} Action object.
	 */
	addFee( fee ) {
		return {
			type: 'ADD_FEE',
			fee,
		};
	},

	/**
	 * Removes a fee from the list of fees.
	 *
	 * @param {number} feeId ID of the fee to remove.
	 * @return {Object} Action object.
	 */
	removeFee( feeId ) {
		return {
			type: 'REMOVE_FEE',
			feeId,
		};
	},

	fetchFromAPI( path ) {
		return {
			type: 'FETCH_FROM_API',
			path,
		}
	},
};

const selectors = {
	/**
	 * Retrieves the list of fees.
	 *
	 * @param {Object} state Global state.
	 * @return {Array} List of fees by ID.
	 */
	getFees( state ) {
		return state.fees;
	},
};


// @see: https://developer.wordpress.org/block-editor/reference-guides/packages/packages-data/
const store = createReduxStore( 'tec-tickets/ticket-fees', {
	reducer: reducer,
	actions: actions,
	selectors: selectors,
	controls: {

	},
	resolvers: {},
} );
