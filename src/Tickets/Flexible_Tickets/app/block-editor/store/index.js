import { createReduxStore, register } from '@wordpress/data';

import actions from './actions';
import reducer from './reducer';
import selectors from './selectors';

const store = createReduxStore('tec-tickets/flexible-tickets', {
	reducer,
	actions,
	selectors,
	controls: {},
	resolvers: {},
});

register(store);
