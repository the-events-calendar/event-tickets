/**
 * External dependencies
 */
import React from 'react';

jest.mock( 'react-redux', () => {
	const React = require( 'react' );

	return {
		connect: ( mapStateToProps, mapDispatchToProps, mergeProps ) => ( Component ) => ( ownProps ) =>
			React.createElement(
				Component,
				mergeProps( mapStateToProps( {} ), { dispatch: jest.fn() }, ownProps )
			),
	};
} );

jest.mock( '@moderntribe/common/hoc', () => ( {
	withStore: () => ( Component ) => Component,
} ) );

jest.mock( '../../../../data/blocks/rsvp-v2', () => ( {
	actions: {
		deleteRSVP: jest.fn( () => ( { type: 'DELETE_RSVP' } ) ),
		setRSVPIsInitializing: jest.fn( ( isInitializing ) => ( {
			type: 'SET_RSVP_IS_INITIALIZING',
			payload: { isInitializing },
		} ) ),
	},
	selectors: {
		getRSVPCreated: jest.fn(),
		getRSVPIsLoading: jest.fn(),
		getRSVPSettingsOpen: jest.fn(),
		getRSVPId: jest.fn(),
	},
	thunks: {
		deleteRSVP: jest.fn( ( id ) => ( { type: 'DELETE_RSVP_THUNK', id } ) ),
	},
} ) );

/**
 * Internal dependencies
 */
import RSVPRemoveRsvpContainer, { mapStateToProps, mergeProps } from '../container';
import { actions, selectors, thunks } from '../../../../data/blocks/rsvp-v2';

describe( 'RSVP V2 Remove RSVP', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		window.confirm = jest.fn();
	} );

	describe( 'mapStateToProps', () => {
		it( 'should map the RSVP state to props', () => {
			selectors.getRSVPCreated.mockReturnValue( true );
			selectors.getRSVPSettingsOpen.mockReturnValue( false );
			selectors.getRSVPIsLoading.mockReturnValue( true );
			selectors.getRSVPId.mockReturnValue( 42 );

			expect( mapStateToProps( {} ) ).toEqual( {
				created: true,
				isDisabled: false,
				isLoading: true,
				rsvpId: 42,
			} );
		} );
	} );

	describe( 'mergeProps onRemove', () => {
		const createOnRemove = ( { created = false, rsvpId = null } = {} ) => {
			selectors.getRSVPCreated.mockReturnValue( created );
			selectors.getRSVPId.mockReturnValue( rsvpId );

			const dispatch = jest.fn();
			const { onRemove } = mergeProps(
				{ created, isDisabled: false, isLoading: false, rsvpId },
				{ dispatch },
				{}
			);

			return { dispatch, onRemove };
		};

		it( 'should not dispatch anything when the user declines the confirmation', async () => {
			window.confirm.mockReturnValue( false );
			const { dispatch, onRemove } = createOnRemove( { created: true, rsvpId: 42 } );

			await onRemove();

			expect( thunks.deleteRSVP ).not.toHaveBeenCalled();
			expect( dispatch ).not.toHaveBeenCalled();
		} );

		it( 'should delete the RSVP via thunk when created and then reset the state', async () => {
			window.confirm.mockReturnValue( true );
			const { dispatch, onRemove } = createOnRemove( { created: true, rsvpId: 42 } );

			await onRemove();

			expect( thunks.deleteRSVP ).toHaveBeenCalledWith( 42 );
			expect( dispatch.mock.calls.map( ( [ action ] ) => action.type ) ).toEqual( [
				'DELETE_RSVP_THUNK',
				'DELETE_RSVP',
				'SET_RSVP_IS_INITIALIZING',
			] );
		} );

		it( 'should reset the state without deleting when the RSVP is not created yet', async () => {
			window.confirm.mockReturnValue( true );
			const { dispatch, onRemove } = createOnRemove();

			await onRemove();

			expect( thunks.deleteRSVP ).not.toHaveBeenCalled();
			expect( dispatch ).toHaveBeenCalledWith( actions.deleteRSVP() );
			expect( dispatch ).toHaveBeenCalledWith( actions.setRSVPIsInitializing( false ) );
		} );

		it( 'should reset the state without deleting when there is no RSVP ID', async () => {
			window.confirm.mockReturnValue( true );
			const { dispatch, onRemove } = createOnRemove( { created: true, rsvpId: null } );

			await onRemove();

			expect( thunks.deleteRSVP ).not.toHaveBeenCalled();
			expect( dispatch ).toHaveBeenCalledWith( actions.deleteRSVP() );
			expect( dispatch ).toHaveBeenCalledWith( actions.setRSVPIsInitializing( false ) );
		} );
	} );

	describe( 'RSVPRemoveRsvpContainer', () => {
		const render = ( props ) =>
			renderer.create( <RSVPRemoveRsvpContainer clientId="client-1" { ...props } /> );

		it( 'should render nothing when the RSVP is not created', () => {
			expect( render( { created: false, isSelected: true } ).toJSON() ).toBeNull();
		} );

		it( 'should render nothing when the block is not selected', () => {
			expect( render( { created: true, isSelected: false } ).toJSON() ).toBeNull();
		} );

		it( 'should render the remove button when created and selected', () => {
			selectors.getRSVPSettingsOpen.mockReturnValue( false );
			selectors.getRSVPIsLoading.mockReturnValue( false );

			expect( render( { created: true, isSelected: true } ).toJSON() ).not.toBeNull();
		} );
	} );
} );
