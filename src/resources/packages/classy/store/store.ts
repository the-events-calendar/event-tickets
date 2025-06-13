import { registerStore } from '@wordpress/data';
import { STORE_NAME } from './constants';
import reducer from './reducer';
import * as selectors from './selectors';
import * as actions from './actions';
import { StoreState, StoreSelectors, StoreActions } from '../types/store';

export const storeConfig = {
	reducer,
	selectors: selectors as unknown as StoreSelectors,
	actions: actions as unknown as StoreActions,
	initialState: {
		ticket: {
			price: 0,
			stock: 0,
			startDate: '',
			endDate: '',
			isFree: false,
			quantity: 0,
		},
	} as StoreState,
};
