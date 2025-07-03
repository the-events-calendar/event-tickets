import { reducer } from './reducer';
import { selectors } from './selectors';
import { default as actions } from './actions';
import { default as resolvers } from './resolvers.ts';
import { StoreState, StoreSelectors } from '../types/Store';

const initialState: StoreState = {
	tickets: null,
	isLoading: false,
	error: null,
}

export const storeConfig = {
	reducer,
	selectors,
	actions,
	resolvers: resolvers,
	initialState: initialState,
};
