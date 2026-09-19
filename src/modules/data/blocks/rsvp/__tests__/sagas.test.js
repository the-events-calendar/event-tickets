/**
 * External dependencies
 */
import { put, call, select, fork, take } from 'redux-saga/effects';
import { noop } from 'lodash';

/**
 * Internal Dependencies
 */
import * as types from '../../rsvp-shared/types';
import * as actions from '../../rsvp-shared/actions';
import * as selectors from '../../rsvp-shared/selectors';
import watchers from '../sagas';
import * as postSaveSagas from '../post-save-sagas';
import * as headerImageSagas from '../header-image-sagas';
import sharedWatchers from '../../rsvp-shared/sagas';
import headerImageWatchers from '../header-image-sagas';
import postSaveWatchers from '../post-save-sagas';
import {
	DEFAULT_STATE as RSVP_HEADER_IMAGE_DEFAULT_STATE,
} from '../reducers/header-image';
import * as ticketActions from '@moderntribe/tickets/data/blocks/ticket/actions';
import {
	DEFAULT_STATE as TICKET_HEADER_IMAGE_DEFAULT_STATE,
} from '@moderntribe/tickets/data/blocks/ticket/reducers/header-image';
import * as utils from '@moderntribe/tickets/data/utils';
import { api } from '@moderntribe/common/utils';
import {
	createWPEditorSavingChannel,
} from '@moderntribe/tickets/data/shared/sagas';

function mock() {
	return {
		select: ( key ) => {
			if ( key === 'core/editor' ) {
				return {
					getEditedPostAttribute: ( attr ) => {
						if ( attr === 'date' ) {
							return 'January 1, 2018';
						}
					},
					getCurrentPostId: () => 10,
					getCurrentPostType: () => 'tribe_events',
				};
			}
			if ( key === 'core' ) {
				return {
					getPostType: () => ( {
						rest_base: 'tribe_events',
					} ),
				};
			}
		},
		subscribe: jest.fn( () => noop ),
		dispatch: jest.fn( () => ( {
			removeBlocks: noop,
		} ) ),
	};
}
jest.mock( '@wordpress/data', () => mock() );

describe( 'RSVP block sagas', () => {
	describe( 'watchers', () => {
		it( 'should watch actions', () => {
			const gen = watchers();
			expect( gen.next().value ).toEqual( fork( sharedWatchers ) );
			expect( gen.next().value ).toEqual( fork( headerImageWatchers ) );
			expect( gen.next().value ).toEqual( fork( postSaveWatchers ) );
			expect( gen.next().done ).toEqual( true );
		} );
	} );

	describe( 'handlers', () => {
		let action;

		beforeEach( () => {
			action = { type: null };
		} );

		it( 'should fetch rsvp header image', () => {
			action.type = types.FETCH_RSVP_HEADER_IMAGE;
			const gen = headerImageSagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( headerImageSagas.fetchRSVPHeaderImage, action ),
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should update rsvp header image', () => {
			action.type = types.UPDATE_RSVP_HEADER_IMAGE;
			const gen = headerImageSagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( headerImageSagas.updateRSVPHeaderImage, action ),
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should delete rsvp header image', () => {
			action.type = types.DELETE_RSVP_HEADER_IMAGE;
			const gen = headerImageSagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( headerImageSagas.deleteRSVPHeaderImage ),
			);
			expect( gen.next().done ).toEqual( true );
		} );
	} );

	describe( 'saveRSVPWithPostSave', () => {
		let channel;

		beforeEach( () => {
			channel = { name, take: jest.fn(), close: jest.fn() };
		} );

		it( 'should update when channel saves', () => {
			const gen = postSaveSagas.saveRSVPWithPostSave();

			expect( gen.next().value ).toEqual(
				select( selectors.getRSVPCreated ),
			);

			expect( gen.next( true ).value ).toEqual(
				call( createWPEditorSavingChannel ),
			);

			expect( gen.next( channel ).value ).toEqual(
				take( channel ),
			);
			expect( gen.next( true ).value ).toMatchSnapshot();
			expect( gen.next( {} ).value ).toMatchSnapshot();

			expect( gen.next().value ).toEqual(
				call( [ channel, 'close' ] ),
			);

			expect( gen.next().done ).toEqual( true );
		} );
		it( 'should do nothing', () => {
			const gen = postSaveSagas.saveRSVPWithPostSave();

			expect( gen.next().value ).toEqual(
				select( selectors.getRSVPCreated ),
			);

			expect( gen.next( false ).done ).toEqual( true );
		} );
	} );

	describe( 'fetchRSVPHeaderImage', () => {
		it( 'should fetch rsvp header image', () => {
			const id = 10;
			const action = {
				payload: {
					id,
				},
			};
			const gen = headerImageSagas.fetchRSVPHeaderImage( action );
			expect( gen.next().value ).toEqual(
				put( actions.setRSVPIsSettingsLoading( true ) ),
			);
			expect( gen.next().value ).toEqual(
				call( api.wpREST, { path: `media/${ id }` } ),
			);

			const apiResponse = {
				response: {
					ok: true,
				},
				data: {
					id: 99,
					alt_text: 'tribe',
					media_details: {
						sizes: {
							medium: {
								source_url: '#',
							},
						},
					},
				},
			};
			expect( gen.next( apiResponse ).value ).toEqual(
				put( actions.setRSVPHeaderImage( {
					id: apiResponse.data.id,
					alt: apiResponse.data.alt_text,
					src: apiResponse.data.media_details.sizes.medium.source_url,
				} ) ),
			);
			expect( gen.next().value ).toEqual(
				put( actions.setRSVPIsSettingsLoading( false ) ),
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should not fetch rsvp header image', () => {
			const id = null;
			const action = {
				payload: {
					id,
				},
			};
			const gen = headerImageSagas.fetchRSVPHeaderImage( action );
			expect( gen.next().value ).toEqual(
				put( actions.setRSVPIsSettingsLoading( true ) ),
			);
			expect( gen.next().value ).toEqual(
				call( api.wpREST, { path: `media/${ id }` } ),
			);

			const apiResponse = {
				response: {
					ok: false,
				},
				data: {},
			};
			expect( gen.next( apiResponse ).value ).toEqual(
				put( actions.setRSVPIsSettingsLoading( false ) ),
			);
			expect( gen.next().done ).toEqual( true );
		} );
	} );

	describe( 'updateRSVPHeaderImage', () => {
		it( 'should update rsvp header image', () => {
			const postId = 10;
			const action = {
				payload: {
					image: {
						id: 99,
						alt: 'tribe',
						sizes: {
							medium: {
								url: '#',
							},
						},
					},
				},
			};
			const gen = headerImageSagas.updateRSVPHeaderImage( action );
			expect( gen.next().value ).toMatchSnapshot();
			expect( gen.next( postId ).value ).toEqual(
				put( actions.setRSVPIsSettingsLoading( true ) ),
			);
			expect( gen.next().value ).toEqual(
				put( ticketActions.setTicketsIsSettingsLoading( true ) ),
			);
			expect( gen.next().value ).toEqual(
				call( api.wpREST, {
					path: `tribe_events/${ postId }`,
					headers: {
						'Content-Type': 'application/json',
					},
					initParams: {
						method: 'PUT',
						body: JSON.stringify( {
							meta: {
								[ utils.KEY_TICKET_HEADER ]: `${ action.payload.image.id }`,
							},
						} ),
					},
				} ),
			);

			const apiResponse = {
				response: {
					ok: true,
				},
			};
			const headerImage = {
				id: action.payload.image.id,
				alt: action.payload.image.alt,
				src: action.payload.image.sizes.medium.url,
			};
			expect( gen.next( apiResponse ).value ).toEqual(
				put( actions.setRSVPHeaderImage( headerImage ) ),
			);
			expect( gen.next().value ).toEqual(
				put( ticketActions.setTicketsHeaderImage( headerImage ) ),
			);
			expect( gen.next().value ).toEqual(
				put( actions.setRSVPIsSettingsLoading( false ) ),
			);
			expect( gen.next().value ).toEqual(
				put( ticketActions.setTicketsIsSettingsLoading( false ) ),
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should not update rsvp header image', () => {
			const postId = 10;
			const action = {
				payload: {
					image: {
						id: 99,
						alt: 'tribe',
						sizes: {
							medium: {
								url: '#',
							},
						},
					},
				},
			};
			const gen = headerImageSagas.updateRSVPHeaderImage( action );
			expect( gen.next().value ).toMatchSnapshot();
			expect( gen.next( postId ).value ).toEqual(
				put( actions.setRSVPIsSettingsLoading( true ) ),
			);
			expect( gen.next().value ).toEqual(
				put( ticketActions.setTicketsIsSettingsLoading( true ) ),
			);
			expect( gen.next().value ).toEqual(
				call( api.wpREST, {
					path: `tribe_events/${ postId }`,
					headers: {
						'Content-Type': 'application/json',
					},
					initParams: {
						method: 'PUT',
						body: JSON.stringify( {
							meta: {
								[ utils.KEY_TICKET_HEADER ]: `${ action.payload.image.id }`,
							},
						} ),
					},
				} ),
			);

			const apiResponse = {
				response: {
					ok: false,
				},
			};
			expect( gen.next( apiResponse ).value ).toEqual(
				put( actions.setRSVPIsSettingsLoading( false ) ),
			);
			expect( gen.next().value ).toEqual(
				put( ticketActions.setTicketsIsSettingsLoading( false ) ),
			);
			expect( gen.next().done ).toEqual( true );
		} );
	} );

	describe( 'deleteRSVPHeaderImage', () => {
		it( 'should delete rsvp header image', () => {
			const postId = 10;
			const gen = headerImageSagas.deleteRSVPHeaderImage();
			expect( gen.next().value ).toMatchSnapshot();
			expect( gen.next( postId ).value ).toEqual(
				put( actions.setRSVPIsSettingsLoading( true ) ),
			);
			expect( gen.next().value ).toEqual(
				put( ticketActions.setTicketsIsSettingsLoading( true ) ),
			);
			expect( gen.next().value ).toEqual(
				call( api.wpREST, {
					path: `tribe_events/${ postId }`,
					headers: {
						'Content-Type': 'application/json',
					},
					initParams: {
						method: 'PUT',
						body: JSON.stringify( {
							meta: {
								[ utils.KEY_TICKET_HEADER ]: null,
							},
						} ),
					},
				} ),
			);

			const apiResponse = {
				response: {
					ok: true,
				},
			};
			expect( gen.next( apiResponse ).value ).toEqual(
				put( actions.setRSVPHeaderImage( RSVP_HEADER_IMAGE_DEFAULT_STATE ) ),
			);
			expect( gen.next().value ).toEqual(
				put( ticketActions.setTicketsHeaderImage( TICKET_HEADER_IMAGE_DEFAULT_STATE ) ),
			);
			expect( gen.next().value ).toEqual(
				put( actions.setRSVPIsSettingsLoading( false ) ),
			);
			expect( gen.next().value ).toEqual(
				put( ticketActions.setTicketsIsSettingsLoading( false ) ),
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should not delete rsvp header image', () => {
			const postId = 10;
			const gen = headerImageSagas.deleteRSVPHeaderImage();
			expect( gen.next().value ).toMatchSnapshot();
			expect( gen.next( postId ).value ).toEqual(
				put( actions.setRSVPIsSettingsLoading( true ) ),
			);
			expect( gen.next().value ).toEqual(
				put( ticketActions.setTicketsIsSettingsLoading( true ) ),
			);
			expect( gen.next().value ).toEqual(
				call( api.wpREST, {
					path: `tribe_events/${ postId }`,
					headers: {
						'Content-Type': 'application/json',
					},
					initParams: {
						method: 'PUT',
						body: JSON.stringify( {
							meta: {
								[ utils.KEY_TICKET_HEADER ]: null,
							},
						} ),
					},
				} ),
			);

			const apiResponse = {
				response: {
					ok: false,
				},
			};
			expect( gen.next( apiResponse ).value ).toEqual(
				put( actions.setRSVPIsSettingsLoading( false ) ),
			);
			expect( gen.next().value ).toEqual(
				put( ticketActions.setTicketsIsSettingsLoading( false ) ),
			);
			expect( gen.next().done ).toEqual( true );
		} );
	} );
} );
