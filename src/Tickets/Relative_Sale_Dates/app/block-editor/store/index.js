/**
 * The store of the sales window and sale price rules of each ticket in the block editor.
 *
 * @since TBD
 */

/**
 * External dependencies
 */
import { createReduxStore, register } from '@wordpress/data';

/**
 * Internal dependencies
 */
import * as actions from './actions';
import * as selectors from './selectors';
import reducer from './reducer';
import { STORE_NAME } from './constants';

export const store = createReduxStore( STORE_NAME, { reducer, actions, selectors } );

register( store );
