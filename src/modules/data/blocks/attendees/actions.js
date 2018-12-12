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

export const setDisplayTitle = ( displayTitle ) => ( {
	type: types.SET_ATTENDEES_DISPLAY_TITLE,
	payload: {
		displayTitle,
	},
} );

export const setDisplaySubtitle = ( displaySubtitle ) => ( {
	type: types.SET_ATTENDEES_DISPLAY_SUBTITLE,
	payload: {
		displaySubtitle,
	},
} );

export const setInitialState = ( payload ) => ( {
	type: types.SET_ATTENDEES_INITIAL_STATE,
	payload,
} );
