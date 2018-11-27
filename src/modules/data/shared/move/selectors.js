/**
 * External dependencies
 */
import { createSelector } from 'reselect';

export const getMove = ( state ) => state.tickets.move;
export const _getUI = createSelector( getMove, move => move.ui );
export const _getPostTypes = createSelector( getMove, move => move.postTypes );
export const _getPosts = createSelector( getMove, move => move.posts );

export const isModalShowing = createSelector( _getUI, ui => ui.showModal );
export const isFetchingPostTypes = createSelector( _getPostTypes, postTypes => postTypes.isFetching );
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
