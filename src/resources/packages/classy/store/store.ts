import { reducer } from './reducer';
import { selectors } from './selectors';
import * as actions from './actions';
import { StoreState } from '../types/StoreState';
import { StoreSelectors } from '../types/StoreSelectors';

export const storeConfig = {
	reducer,
	selectors: selectors as unknown as StoreSelectors,
	actions: actions,
	initialState: {
		tickets: [],
		currentPostId: null,
		isLoading: false,
		error: null,
	} as StoreState,
};
