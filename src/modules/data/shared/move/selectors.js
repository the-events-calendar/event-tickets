/**
 * External dependencies
 */
import { createSelector } from 'reselect';
import { find } from 'lodash';

export const getMove = ( state ) => state.tickets.move;
export const _getUI = createSelector( getMove, move => move.ui );
export const _getPostTypes = createSelector( getMove, move => move.postTypes );
export const _getPosts = createSelector( getMove, move => move.posts );
export const _getModal = createSelector( getMove, move => move.modal );

export const isModalShowing = createSelector( _getUI, ui => ui.showModal );
export const isFetchingPostTypes = createSelector(
	_getPostTypes,
	postTypes => postTypes.isFetching,
);
export const isFetchingPosts = createSelector( _getPosts, posts => posts.isFetching );

export const getPostTypes = createSelector( _getPostTypes, postTypes => postTypes.posts );
export const getPosts = createSelector( _getPosts, posts => posts.posts );

export const getPostTypeOptions = createSelector( getPostTypes, types => (
	Object.keys( types ).map( type => ( {
		value: type,
		label: types[ type ],
	} ) )
) );
export const getPostOptions = createSelector( getPosts, posts => (
	Object.keys( posts ).map( post => ( {
		value: post,
		label: posts[ post ],
	} ) )
) );

export const getModalPostType = createSelector( _getModal, modal => modal.post_type );
export const getModalSearch = createSelector( _getModal, modal => modal.search_terms );
export const getModalTarget = createSelector( _getModal, modal => modal.target_post_id );
export const getModalTicketId = createSelector( _getModal, modal => modal.ticketId );
export const getModalClientId = createSelector( _getModal, modal => modal.clientId );
export const isModalSubmitting = createSelector( _getModal, modal => modal.isSubmitting );

export const getPostTypeOptionValue = createSelector(
	[ getPostTypeOptions, getModalPostType ],
	( postTypeOptions, postType ) => find( postTypeOptions, [ 'value', postType ] ),
);

export const hasSelectedPost = createSelector(
	[ getPostOptions, getModalTarget ],
	( posts, target ) => !! ( target && find( posts, [ 'value', target ] ) ),
);
