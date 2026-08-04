/**
 * External dependencies
 */
import { fork } from 'redux-saga/effects';

/**
 * Internal Dependencies
 */
import { sagas } from '../index';
import sharedWatchers from '../../rsvp-shared/sagas';
import headerImageWatchers from '../header-image-sagas';
import postSaveWatchers from '../post-save-sagas';

describe( 'RSVP block index', () => {
	describe( 'sagas', () => {
		it( 'should fork the shared, header image, and post-save watchers', () => {
			const gen = sagas();
			expect( gen.next().value ).toEqual( fork( sharedWatchers ) );
			expect( gen.next().value ).toEqual( fork( headerImageWatchers ) );
			expect( gen.next().value ).toEqual( fork( postSaveWatchers ) );
			expect( gen.next().done ).toEqual( true );
		} );
	} );
} );
