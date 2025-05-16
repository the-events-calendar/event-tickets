/**
 * Internal dependencies
 */
import * as types from '../types';

/**
 * Full payload from gutenberg media upload is not used,
 * only id, alt, and src are used for this specific case.
 */
export const DEFAULT_STATE = {
	id: 0,
	src: '',
	alt: '',
};

export default ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case types.SET_TICKETS_HEADER_IMAGE:
			return {
				id: action.payload.id,
				src: action.payload.src,
				alt: action.payload.alt,
			};
		default:
			return state;
	}
};
