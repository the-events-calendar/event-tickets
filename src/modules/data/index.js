/**
 * External dependencies
 */
import reducer from './reducers';

import { actions } from '@moderntribe/common/data/plugins';
import { store } from '@moderntribe/common/store';

export const initStore = () => {
	const { dispatch, injectReducers } = store;
	// TODO: use `constants` from `recurrence` branch to add the plugin name into the constants.
	dispatch( actions.addPlugin( 'tickets' ) );
	injectReducers( { tickets: reducer } );
};

export const getStore = () => store;
