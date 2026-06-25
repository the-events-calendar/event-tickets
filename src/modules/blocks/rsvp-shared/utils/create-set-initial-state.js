/**
 * WordPress dependencies
 */
import { select } from '@wordpress/data';

/**
 * Creates the setInitialState handler for RSVP block containers.
 *
 * @param {Object}   options                    Factory options.
 * @param {Object}   options.actions              RSVP action creators.
 * @param {Object}   options.thunks               RSVP thunks.
 * @param {boolean}  options.hydrateHeaderImage         Whether to hydrate header image from attributes.
 * @param {boolean}  options.hydrateCountsFromAttributes Whether to seed counts from block attributes.
 * @return {Function} setInitialState factory bound to dispatch and ownProps.
 */
export const createSetInitialState = ( {
	actions,
	thunks,
	hydrateHeaderImage = false,
	hydrateCountsFromAttributes = true,
} ) => ( dispatch, ownProps ) => () => {
	const postId = select( 'core/editor' ).getCurrentPostId();
	dispatch( thunks.getRSVP( postId ) );

	const { attributes = {} } = ownProps;

	if ( hydrateHeaderImage && parseInt( attributes.headerImageId, 10 ) ) {
		dispatch( actions.fetchRSVPHeaderImage( attributes.headerImageId ) );
	}

	if ( ! hydrateCountsFromAttributes ) {
		return;
	}

	if ( attributes.goingCount !== undefined && attributes.goingCount !== null ) {
		dispatch( actions.setRSVPGoingCount( parseInt( attributes.goingCount, 10 ) || 0 ) );
	}

	if ( attributes.notGoingCount !== undefined && attributes.notGoingCount !== null ) {
		dispatch( actions.setRSVPNotGoingCount( parseInt( attributes.notGoingCount, 10 ) || 0 ) );
	}
};
