/**
 * WordPress dependencies
 */
import { dispatch as wpDispatch, select as wpSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { KEY_TICKET_GOING_COUNT, KEY_TICKET_NOT_GOING_COUNT } from '../../../utils';

/**
 * Clears RSVP attendance counts from the edited post meta.
 *
 * @return {Function} Redux thunk.
 */
export const clearRsvpEventMeta = () => () => {
	const currentMeta = wpSelect( 'core/editor' )?.getEditedPostAttribute( 'meta' ) || {};

	wpDispatch( 'core/editor' ).editPost( {
		meta: {
			...currentMeta,
			[ KEY_TICKET_GOING_COUNT ]: '',
			[ KEY_TICKET_NOT_GOING_COUNT ]: '',
		},
	} );
};
