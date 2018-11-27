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

export default combineReducers( {
	posts,
	postTypes,
	ui,
} );
