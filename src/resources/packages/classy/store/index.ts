import { reducer } from './reducer';
import * as selectors from './selectors';
import { actions } from './actions';
import { default as resolvers } from './resolvers.ts';
import { StoreState } from '../types/Store';

const initialState: StoreState = {
	eventCapacity: undefined,
	eventHasSharedCapacity: false,
	loading: true,
	tickets: null,
}

export const storeConfig = {
	reducer,
	selectors,
	actions,
	resolvers: resolvers,
	initialState: initialState,
};
