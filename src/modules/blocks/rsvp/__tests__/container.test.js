/**
 * Internal dependencies
 */
import { mapDispatchToProps } from '../container';
import { actions, thunks } from '../../../data/blocks/rsvp';

jest.mock( '../../../data/blocks/rsvp', () => ( {
	...jest.requireActual( '../../../data/blocks/rsvp' ),
	thunks: {
		...jest.requireActual( '../../../data/blocks/rsvp' ).thunks,
		deleteRSVP: jest.fn( ( id ) => ( { type: 'REST_DELETE', id } ) ),
	},
} ) );

describe( 'RSVP block container', () => {
	const props = { clientId: 'block-1', created: true, rsvpId: 12 };
	const restDelete = { type: 'REST_DELETE', id: props.rsvpId };
	let dispatch;

	beforeEach( () => {
		dispatch = jest.fn();
		thunks.deleteRSVP.mockClear();
		global.wp.data = { select: () => ( { getBlock: () => null } ) };
	} );

	afterEach( () => {
		global.wp.data = {};
	} );

	it( 'should delete the RSVP when its block is removed', () => {
		mapDispatchToProps( dispatch, {} ).onBlockRemoved( { ...props, isModalShowing: false } );

		expect( dispatch ).toHaveBeenCalledWith( restDelete );
		expect( dispatch ).toHaveBeenCalledWith( actions.deleteRSVP() );
	} );

	it( 'should not delete an RSVP that was moved to another post', () => {
		mapDispatchToProps( dispatch, {} ).onBlockRemoved( { ...props, isModalShowing: true } );

		expect( dispatch ).not.toHaveBeenCalledWith( restDelete );
		expect( dispatch ).toHaveBeenCalledWith( actions.deleteRSVP() );
	} );
} );
