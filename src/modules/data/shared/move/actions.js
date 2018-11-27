/* eslint-disable camelcase */
/**
 * Internal Dependencies
 */
import * as types from './types';

export const showModal = ( ticket_id ) => ( {
	type: types.SHOW_MODAL,
	payload: { ticket_id },
} );

export const hideModal = () => ( {
	type: types.HIDE_MODAL,
} );
