import { reducer } from './reducer';
import { selectors } from './selectors';
import * as actions from './actions';
import { resolver as resolvers } from './resolver';
import { StoreState, StoreSelectors } from '../types/Store';

const initialState: StoreState = {
	tickets: null,
	isLoading: false,
	error: null,
}

export const storeConfig = {
	reducer,
	selectors: selectors,
	actions: actions,
	resolvers: resolvers,
	initialState: initialState,
};
