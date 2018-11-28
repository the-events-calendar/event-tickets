/**
 * External dependencies
 */
import { combineReducers } from 'redux';

/**
 * Internal dependencies
 */
import posts from './posts';
import postTypes from './postTypes';
import ui from './ui';
import modal from './modal';

export default combineReducers( {
	posts,
	postTypes,
	ui,
	modal,
} );
