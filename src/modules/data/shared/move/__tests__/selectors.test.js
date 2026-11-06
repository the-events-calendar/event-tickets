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

	test( 'getPostOptions uses the post ID as the value and the decoded title as the label', () => {
		state.tickets.move.posts.posts = {
			'Rain Date (November 1)': 12,
			'Bob&#8217;s Rain &amp; Shine': 34,
		};
		expect( selectors.getPostOptions( state ) ).toEqual( [
			{ value: '12', label: 'Rain Date (November 1)' },
			{ value: '34', label: 'Bob’s Rain & Shine' },
		] );
	} );

	test( 'getPostOptions gives a post without a title a label', () => {
		state.tickets.move.posts.posts = {
			'': 56,
		};
		expect( selectors.getPostOptions( state ) ).toEqual( [ { value: '56', label: '(no title)' } ] );
	} );

	test( 'getPostTypeOptionValue', () => {
		state.tickets.move.postTypes.posts = {
			all: 'All',
			cool: 'Cool',
		};
		state.tickets.move.modal.post_type = 'all';
		expect( selectors.getPostTypeOptionValue( state ) ).toMatchSnapshot();
	} );
	test( 'hasSelectedPost is true when the selected post ID is listed', () => {
		state.tickets.move.posts.posts = {
			'Rain Date (November 1)': 12,
		};
		state.tickets.move.modal.target_post_id = '12';
		expect( selectors.hasSelectedPost( state ) ).toBe( true );
	} );

	test( 'hasSelectedPost is false when the selected post ID is not listed', () => {
		state.tickets.move.posts.posts = {
			'Rain Date (November 1)': 12,
		};
		state.tickets.move.modal.target_post_id = '56';
		expect( selectors.hasSelectedPost( state ) ).toBe( false );
	} );

	test( 'getModalErrorMessage', () => {
		state.tickets.move.modal.errorMessage = 'The move failed.';
		expect( selectors.getModalErrorMessage( state ) ).toBe( 'The move failed.' );
	} );
} );
