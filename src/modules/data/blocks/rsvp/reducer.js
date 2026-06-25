/**
 * Internal dependencies
 */
import sharedReducer, { DEFAULT_STATE as SHARED_DEFAULT_STATE } from '../rsvp-shared/reducer';
import headerImage, { DEFAULT_STATE as HEADER_IMAGE_DEFAULT_STATE } from './reducers/header-image';
import * as types from '../rsvp-shared/types';

export const DEFAULT_STATE = {
	...SHARED_DEFAULT_STATE,
	headerImage: HEADER_IMAGE_DEFAULT_STATE,
};

export default ( state = DEFAULT_STATE, action ) => {
	const nextState = sharedReducer( state, action );

	if ( action.type === types.SET_RSVP_HEADER_IMAGE ) {
		return {
			...nextState,
			headerImage: headerImage( nextState.headerImage, action ),
		};
	}

	return nextState;
};
