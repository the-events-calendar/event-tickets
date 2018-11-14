/**
 * External dependencies
 */
import reducer from './reducers';

import { actions, constants } from '@moderntribe/common/data/plugins';
import { store } from '@moderntribe/common/store';

export const initStore = () => {
	const { dispatch, injectReducers } = store;
	const { TICKETS } = constants;
	dispatch( actions.addPlugin( TICKETS ) );
	injectReducers( { tickets: reducer } );
};

export const getStore = () => store;
