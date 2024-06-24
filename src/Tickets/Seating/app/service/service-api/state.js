import {
	GO_TO_LAYOUTS_HOME,
	GO_TO_MAPS_HOME,
	LAYOUT_CREATED_UPDATED,
	MAP_CREATED_UPDATED,
	SEAT_TYPE_CREATED_UPDATED,
} from './service-actions.js';
import {
	defaultMessageHandler,
	onGoToLayoutsHome,
	onGoToMapsHome,
	onLayoutCreatedUpdated,
	onMapCreatedUpdated,
	onSeatTypeCreatedUpdated,
} from './message-handlers.js';

/**
 * @typedef {Object} State
 * @property {boolean}                   ready                 Whether the connection is established.
 * @property {boolean}                   establishingReadiness Whether the connection is being established.
 * @property {Object.<string, Function>} actionsMap            The map of actions and their callbacks.
 * @property {string}                    token                 The token used to authenticate the connection.
 */

/**
 * @type {State}
 */
export const state = {
	ready: false,
	establishingReadiness: false,
	actionsMap: {
		default: defaultMessageHandler,
		[MAP_CREATED_UPDATED]: onMapCreatedUpdated,
		[LAYOUT_CREATED_UPDATED]: onLayoutCreatedUpdated,
		[SEAT_TYPE_CREATED_UPDATED]: onSeatTypeCreatedUpdated,
		[GO_TO_MAPS_HOME]: onGoToMapsHome,
		[GO_TO_LAYOUTS_HOME]: onGoToLayoutsHome,
	},
	token: null,
};
