/**
 * External dependencies
 */
import { combineReducers } from 'redux';

/**
 * Internal dependencies
 */
import blocks from './blocks';
import move from './shared/move/reducers';

export default combineReducers( {
	blocks,
	move,
} );
