/**
 * External Dependencies
 */
import * as selectors from '../selectors';
import { DEFAULT_STATE as MODAL_DEFAULT_STATE } from '../reducers/modal';
import { DEFAULT_STATE as POSTS_DEFAULT_STATE } from '../reducers/posts';
import { DEFAULT_STATE as POST_TYPES_DEFAULT_STATE } from '../reducers/postTypes';
import { DEFAULT_STATE as UI_DEFAULT_STATE } from '../reducers/ui';

describe( 'Move Selectors', () => {
	let state;

	beforeEach( () => {
		state = {
			tickets: {
				move: {
					ui: Object.assign( {}, UI_DEFAULT_STATE ),
					posts: Object.assign( {}, POSTS_DEFAULT_STATE ),
					modal: Object.assign( {}, MODAL_DEFAULT_STATE ),
					postTypes: Object.assign( {}, POST_TYPES_DEFAULT_STATE ),
				},
			},
		};
	} );

	Object.keys( selectors ).forEach( ( key ) => {
		test( `Default - ${ key }`, () => {
			expect( selectors[ key ]( state ) ).toMatchSnapshot();
		} );
	} );

	test( 'getPostTypeOptions', () => {
		state.tickets.move.postTypes.posts = {
			all: 'All',
			cool: 'Cool',
		};
		expect( selectors.getPostTypeOptions( state ) ).toMatchSnapshot();
	} );

	test( 'getPostOptions', () => {
		state.tickets.move.postTypes.posts = {
			all: 'All',
			cool: 'Cool',
		};
		expect( selectors.getPostOptions( state ) ).toMatchSnapshot();
	} );

	test( 'getPostTypeOptionValue', () => {
		state.tickets.move.postTypes.posts = {
			all: 'All',
			cool: 'Cool',
		};
		state.tickets.move.modal.post_type = 'all';
		expect( selectors.getPostTypeOptionValue( state ) ).toMatchSnapshot();
	} );
	test( 'hasSelectedPost', () => {
		state.tickets.move.posts.posts = {
			all: 'All',
			cool: 'Cool',
		};
		state.tickets.move.modal.target_post_id = 'all';
		expect( selectors.hasSelectedPost( state ) ).toMatchSnapshot();
	} );
} );
