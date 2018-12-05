/**
 * Internal dependencies
 */
import * as types from './types';

export const setTitle = ( title ) => ( {
	type: types.SET_ATTENDEES_TITLE,
	payload: {
		title,
	},
} );

export const setInitialState = ( payload ) => ( {
	type: types.SET_ATTENDEES_INITIAL_STATE,
	payload,
} );
