import { reducer } from './reducer';
import * as actions from './actions';
import * as selectors from './selectors';
import { STORE_NAME } from './constants';

export { STORE_NAME };

export const storeConfig = {
	reducer,
	actions,
	selectors,
};
