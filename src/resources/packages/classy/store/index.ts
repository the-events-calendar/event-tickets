import { reducer } from './reducer';
import * as actions from './actions';
import * as selectors from './selectors';

export const STORE_NAME = 'tec/classy/tickets';

export const storeConfig = {
	reducer,
	actions,
	selectors,
};
