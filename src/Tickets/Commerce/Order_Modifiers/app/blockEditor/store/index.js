/**
 * External dependencies
 */
import { createReduxStore, register } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { getTicketIdFromCommonStore } from './common-store-bridge';
import { defaultState } from './default-state';
import { controls } from './controls';
import { actions } from './actions';
import { reducer } from './reducer';
import { selectors } from './selectors';
import { localizedData } from './localized-data';

const storeName = 'tec-tickets-fees';

const resolvers = {

	* getAutomaticFees() {
		const allFees = yield actions.fetchFeesFromAPI();
		return actions.setAutomaticFees( allFees.feesAutomatic );
	},

	* getAvailableFees() {
		const allFees = yield actions.fetchFeesFromAPI();
		return actions.setAvailableFees( allFees.feesAvailable );
	},
};


// @see: https://developer.wordpress.org/block-editor/reference-guides/packages/packages-data/
const store = createReduxStore( storeName, {
	reducer,
	actions: actions,
	selectors: selectors,
	controls: controls,
	resolvers: resolvers,
} );

register( store );

export { store, storeName };
