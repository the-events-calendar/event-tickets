import { reducer } from './reducer';
import * as selectors from './selectors';
import { actions } from './actions';
import { default as resolvers } from './resolvers.ts';
import { StoreState } from '../types/Store';

const initialState: StoreState = {
	tickets: null,
	loading: true,
}

export const storeConfig = {
	reducer,
	selectors,
	actions,
	resolvers: resolvers,
	initialState: initialState,
};
