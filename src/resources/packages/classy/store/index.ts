import { reducer } from './reducer';
import { selectors } from './selectors';
import * as actions from './actions';
import * as resolvers from './resolver';
import { StoreState, StoreSelectors } from '../types/Store';

const initialState: StoreState = {
	allTickets: [], // Todo: remove this when not needed.
	tickets: [],
	isLoading: false,
	error: null,
}

export const storeConfig = {
	reducer,
	selectors: selectors as unknown as StoreSelectors,
	actions: actions,
	resolvers: resolvers,
	initialState: initialState,
};
