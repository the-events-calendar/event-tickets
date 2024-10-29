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

/*
 * @type {Object.<string, Function>}
 */
const defaultActionsMap = {
	default: defaultMessageHandler,
	[MAP_CREATED_UPDATED]: onMapCreatedUpdated,
	[LAYOUT_CREATED_UPDATED]: onLayoutCreatedUpdated,
	[SEAT_TYPE_CREATED_UPDATED]: onSeatTypeCreatedUpdated,
	[GO_TO_MAPS_HOME]: onGoToMapsHome,
	[GO_TO_LAYOUTS_HOME]: onGoToLayoutsHome,
};

/**
 * @type {State}
 */
export const state = {
	ready: false,
	establishingReadiness: false,
	actionsMap: defaultActionsMap,
	token: null,
};

/**
 * Returns the handler for a given action, or the default handler if none is found.
 *
 * @since 5.16.0
 *
 * @param {string} action The action to get the handler for.
 *
 * @return {Function|null} The handler for the action, or the default handler if none is found.
 */
export function getHandlerForAction(action) {
	return (
		state.actionsMap[action] ||
		state.actionsMap.default ||
		defaultMessageHandler
	);
}

/**
 * Registers an action and its callback.
 *
 * @since 5.16.0
 *
 * @param {string}   action   The action to register the callback for.
 * @param {Function} callback The callback to register for the action.
 */
export function registerAction(action, callback) {
	state.actionsMap[action] = callback;
}

/**
 * Removes an action and its callback form the actions map.
 *
 * @since 5.16.0
 *
 * @param {string} action The action to remove form the actions map.
 */
export function removeAction(action) {
	delete state.actionsMap[action];
}

/**
 * Returns the actions map.
 *
 * @since 5.16.0
 *
 * @return {Object} The actions map.
 */
export function getRegisteredActions() {
	return state.actionsMap;
}

/**
 * Sets the ready state of the Service.
 *
 * @since 5.16.0
 *
 * @param {boolean} isReady Whether the Service is ready or not.
 */
export function setIsReady(isReady) {
	state.ready = isReady;
}

/**
 * Returns whether the Service is ready or not.
 *
 * @since 5.16.0
 *
 * @return {boolean} Whether the Service is ready or not.
 */
export function getIsReady() {
	return state.ready;
}

/**
 * Sets whether the Service is establishing readiness or not.
 *
 * @since 5.16.0
 *
 * @param {boolean} establishingReadiness Whether the Service is establishing or not.
 */
export function setEstablishingReadiness(establishingReadiness) {
	state.establishingReadiness = establishingReadiness;
}

/**
 * Returns whether the Service is establishing readiness or not.
 *
 * @since 5.16.0
 *
 * @return {boolean} Whether the Service is establishing readiness or not.
 */
export function getEstablishingReadiness() {
	return state.establishingReadiness;
}

/**
 * Sets the token used to communicate with the service.
 *
 * @since 5.16.0
 *
 * @param {string} token The token to set.
 */
export function setToken(token) {
	state.token = token;
}

/**
 * Returns the current ephemeral token used to communicate with the service.
 *
 * @since 5.16.0
 *
 * @return {string} The current ephemeral token.
 */
export function getToken() {
	return state.token;
}

/**
 * Resets the state to its default values.
 *
 * This is useful for testing and should not be used in production.
 *
 * @since 5.16.0
 *
 * @return {void}
 */
export function reset() {
	state.ready = false;
	state.establishingReadiness = false;
	state.actionsMap = defaultActionsMap;
	state.token = null;
}
