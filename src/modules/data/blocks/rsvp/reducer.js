/**
 * Internal dependencies
 */
import sharedReducer, { DEFAULT_STATE as SHARED_DEFAULT_STATE } from '../rsvp-shared/reducer';
import * as types from '../rsvp-shared/types';
import headerImage, { DEFAULT_STATE as HEADER_IMAGE_DEFAULT_STATE } from './reducers/header-image';

export const DEFAULT_STATE = {
	...SHARED_DEFAULT_STATE,
	iac: 'none',
	headerImage: HEADER_IMAGE_DEFAULT_STATE,
};

export default ( state = DEFAULT_STATE, action ) => {
	const nextState = sharedReducer( state, action );

	switch ( action.type ) {
		case types.SET_RSVP_IAC:
			return {
				...nextState,
				iac: action.payload.iac,
			};
		case types.SET_RSVP_HEADER_IMAGE:
			return {
				...nextState,
				headerImage: headerImage( nextState.headerImage, action ),
			};
		default:
			return nextState;
	}
};
