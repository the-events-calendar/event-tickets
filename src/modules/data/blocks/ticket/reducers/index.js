/**
 * External dependencies
 */
import { combineReducers } from 'redux';

/**
 * Internal dependencies
 */
import ui from './ui';
import tickets from './tickets';
import settings from './settings/index';

export default combineReducers( {
	ui,
	tickets,
	settings,
} );
