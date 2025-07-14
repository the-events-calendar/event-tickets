/**
 * Internal dependencies
 */
import reducer from './reducer';

import * as constants from './constants';
import * as options from './options';
import * as utils from './utils';
import * as types from './types';
import * as actions from './actions';
import * as selectors from './selectors';
import sagas from './sagas';

export default reducer;

export { constants, options, utils, types, actions, selectors, sagas };

window.tribe = window.tribe || {};
window.tribe.tickets = window.tribe.tickets || {};
window.tribe.tickets.data = window.tribe.tickets.data || {};
window.tribe.tickets.data.blocks = window.tribe.tickets.data.blocks || {};
window.tribe.tickets.data.blocks = {
	...window.tribe.tickets.data.blocks,
	...{
		actions,
		selectors,
	},
};
