/**
 * External dependencies
 */
import { createReduxStore, register } from '@wordpress/data';

/**
 * Internal dependencies
 */
import actions from './actions';
import reducer from './reducer';
import selectors from './selector';

// @see: https://developer.wordpress.org/block-editor/reference-guides/packages/packages-data/
const store = createReduxStore( 'tec-tickets/ticket-fees', {
	reducer: reducer,
	actions: actions,
	selectors: selectors,
	controls: {},
	resolvers: {},
} );
