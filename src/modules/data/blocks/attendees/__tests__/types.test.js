/**
 * Internal dependencies
 */
import { types } from '@moderntribe/tickets/data/blocks/attendees';
import { PREFIX_TICKETS_STORE } from '@moderntribe/tickets/data/utils';

describe( '[STORE] - Attendees types', () => {
	it( 'Attendees initial state', () => {
		expect( types.SET_ATTENDEES_INITIAL_STATE )
			.toBe( `${ PREFIX_TICKETS_STORE }/SET_ATTENDEES_INITIAL_STATE` );
	} );

	it( 'Should match the types values', () => {
		expect( types.SET_ATTENDEES_TITLE )
			.toBe( `${ PREFIX_TICKETS_STORE }/SET_ATTENDEES_TITLE` );
	} );

	it( 'Should match the types values', () => {
		expect( types.SET_ATTENDEES_DISPLAY_TITLE )
			.toBe( `${ PREFIX_TICKETS_STORE }/SET_ATTENDEES_DISPLAY_TITLE` );
	} );

	it( 'Should match the types values', () => {
		expect( types.SET_ATTENDEES_DISPLAY_SUBTITLE )
			.toBe( `${ PREFIX_TICKETS_STORE }/SET_ATTENDEES_DISPLAY_SUBTITLE` );
	} );
} );
