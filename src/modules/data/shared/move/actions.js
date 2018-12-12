/* eslint-disable camelcase */
/**
 * Internal Dependencies
 */
import * as types from './types';

export const showModal = ( ticketId, blockId, ) => ( {
	type: types.SHOW_MODAL,
	payload: { ticketId, blockId },
} );

export const hideModal = () => ( {
	type: types.HIDE_MODAL,
} );

export const setModalData = ( payload ) => ( {
	type: types.SET_MODAL_DATA,
	payload,
} );
