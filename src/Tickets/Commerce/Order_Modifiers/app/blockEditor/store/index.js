/**
 * External dependencies
 */
import { createReduxStore, register } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { controls } from './controls';
import { actions } from './actions';
import { reducer } from './reducer';
import { selectors } from './selectors';

const storeName = 'tec-tickets-fees';

const resolvers = {

	* getAllFees() {
		const { feesAvailable, feesAutomatic } = yield actions.fetchFeesFromAPI();
		return actions.setAllFees( feesAvailable, feesAutomatic );
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
