/**
 * External Dependencies
 */
import { call } from 'redux-saga/effects';

/**
 * Internal dependencies
 */
import * as types from '../types';
import watchers, * as sagas from '../sagas';

jest.mock( '@wordpress/data', () => ( {
	select() {
		return { getCurrentPostId: () => 1 };
	},
} ) );

describe( 'Move Sagas', () => {
	test( 'createBody', () => {
		expect( sagas.createBody( { a: 1, b: 2, c: 'okay&something' } ) ).toMatchSnapshot();
	} );

	describe( '_fetch', () => {
		it( 'should fetch successfully', () => {
			const gen = sagas._fetch( { a: 1 } );

			expect( gen.next().value ).toEqual( call(
				sagas.createBody, {
					a: 1,
					check: undefined,
				} ),
			);

			expect( gen.next( 'a=1&check=undefined' ).value ).toMatchSnapshot();
			expect( gen.next( { json() {} } ).value ).toMatchSnapshot();
			expect( gen.next().done ).toBeTruthy();
		} );
		it( 'should error', () => {
			const gen = sagas._fetch( { a: 1 } );
			expect( gen.next().value ).toMatchSnapshot();
			expect( gen.throw( 'error' ).value ).toMatchSnapshot();
			expect( gen.next().done ).toBeTruthy();
		} );
	} );

	describe( 'fetchPostTypes', () => {
		it( 'should fetch successfully', () => {
			const gen = sagas.fetchPostTypes();
			expect( gen.next().value ).toMatchSnapshot();
			expect( gen.next().value ).toMatchSnapshot();
			expect( gen.next( { data: {} } ).value ).toMatchSnapshot();
			expect( gen.next().done ).toBeTruthy();
		} );
		it( 'should error', () => {
			const gen = sagas.fetchPostTypes();
			expect( gen.next().value ).toMatchSnapshot();
			expect( gen.throw( 'error' ).value ).toMatchSnapshot();
			expect( gen.next().done ).toBeTruthy();
		} );
	} );

	describe( 'fetchPostChoices', () => {
		const params = {
			ignore: 1,
			post_type: 'all',
			search_terms: 'cool',
		};
		it( 'should fetch successfully', () => {
			const gen = sagas.fetchPostChoices( params );
			expect( gen.next().value ).toMatchSnapshot();
			expect( gen.next().value ).toMatchSnapshot();
			expect( gen.next( { data: {} } ).value ).toMatchSnapshot();
			expect( gen.next().done ).toBeTruthy();
		} );
		it( 'should error', () => {
			const gen = sagas.fetchPostChoices( params );
			expect( gen.next().value ).toMatchSnapshot();
			expect( gen.throw( 'error' ).value ).toMatchSnapshot();
			expect( gen.next().done ).toBeTruthy();
		} );
	} );

	describe( 'moveTicket', () => {
		const params = {
			src_post_id: 1,
			ticket_type_id: 2,
			target_post_id: 3,
		};
		it( 'should fetch successfully', () => {
			const gen = sagas.moveTicket( params );
			expect( gen.next().value ).toMatchSnapshot();
			expect( gen.next().value ).toMatchSnapshot();
			expect( gen.next( { data: {} } ).value ).toMatchSnapshot();
			expect( gen.next().done ).toBeTruthy();
		} );
		it( 'should error', () => {
			const gen = sagas.moveTicket( params );
			expect( gen.next().value ).toMatchSnapshot();
			expect( gen.throw( 'error' ).value ).toMatchSnapshot();
			expect( gen.next().done ).toBeTruthy();
		} );
	} );

	test( 'getCurrentPostId', () => {
		const gen = sagas.getCurrentPostId();
		expect( gen.next().value ).toMatchSnapshot();
	} );

	test( 'getPostChoices', () => {
		const gen = sagas.getPostChoices();
		expect( gen.next().value ).toMatchSnapshot();
		expect( gen.next( { post_type: 1, search_terms: '', ignore: 2 } ).value ).toMatchSnapshot();
		expect( gen.next().done ).toBeTruthy();
	} );

	describe( 'onModalChange', () => {
		let action;
		beforeEach( () => {
			action = {
				payload: {

				},
			};
		} );
		it( 'should get post choices', () => {
			const gen = sagas.onModalChange( action );
			expect( gen.next().value ).toMatchSnapshot();
			expect( gen.next().value ).toMatchSnapshot();
			expect( gen.next().done ).toBeTruthy();
		} );
		it( 'should not get post choices', () => {
			action.payload.ticketId = 1;
			const gen = sagas.onModalChange( action );
			expect( gen.next().done ).toBeTruthy();
		} );
	} );

	describe( 'onModalSubmit', () => {
		it( 'should hide modal on success', () => {
			const gen = sagas.onModalSubmit();
			expect( gen.next().value ).toMatchSnapshot();
			expect( gen.next( {
				src_post_id: 1,
				ticket_type_id: 2,
				target_post_id: 3,
			} ).value ).toMatchSnapshot();
			expect( gen.next().value ).toMatchSnapshot();
			expect( gen.next( { type: types.MOVE_TICKET_SUCCESS } ).value ).toMatchSnapshot();
			expect( gen.next().done ).toBeTruthy();
		} );
		it( 'should not hide modal on error', () => {
			const gen = sagas.onModalSubmit();
			expect( gen.next().value ).toMatchSnapshot();
			expect( gen.next( {
				src_post_id: 1,
				ticket_type_id: 2,
				target_post_id: 3,
			} ).value ).toMatchSnapshot();
			expect( gen.next().value ).toMatchSnapshot();
			expect( gen.next( { type: types.MOVE_TICKET_ERROR } ).value ).toMatchSnapshot();
			expect( gen.next().done ).toBeTruthy();
		} );
	} );

	test( 'onModalShow', () => {
		const gen = sagas.onModalShow( { payload: {} } );
		expect( gen.next().value ).toMatchSnapshot();
		expect( gen.next().done ).toBeTruthy();
	} );

	test( 'onModalHide', () => {
		const gen = sagas.onModalHide();
		expect( gen.next().value ).toMatchSnapshot();
		expect( gen.next().done ).toBeTruthy();
	} );

	test( 'initialize', () => {
		const gen = sagas.initialize();
		expect( gen.next().value ).toMatchSnapshot();
		expect( gen.next().done ).toBeTruthy();
	} );

	test( 'watchers', () => {
		const gen = watchers();
		expect( gen.next().value ).toMatchSnapshot();
		expect( gen.next().value ).toMatchSnapshot();
		expect( gen.next().value ).toMatchSnapshot();
		expect( gen.next().value ).toMatchSnapshot();
		expect( gen.next().value ).toMatchSnapshot();
		expect( gen.next().done ).toBeTruthy();
	} );
} );
