jest.mock( '../../container-content/container', () => ( { clientId } ) => (
	<div data-testid="rsvp-container-content">{ clientId }</div>
) );
jest.mock( '../../saved-summary/container', () => ( { clientId, isSelected } ) => (
	<div data-testid="rsvp-saved-summary">{ `${ clientId }-${ isSelected }` }</div>
) );
jest.mock( '../../attendee-information-section/container', () => ( { clientId, isSelected } ) => (
	<div data-testid="rsvp-attendee-information-section">{ `${ clientId }-${ isSelected }` }</div>
) );
jest.mock( '../../remove-rsvp/container', () => ( { clientId, created, isSelected } ) => (
	<div data-testid="rsvp-remove-rsvp">{ `${ clientId }-${ created }-${ isSelected }` }</div>
) );

/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import RSVPContainer from '../template';

describe( 'RSVPContainer', () => {
	const defaultProps = {
		clientId: 'test-client-id',
		created: false,
		isAddEditOpen: true,
		isDisabled: false,
		isSelected: true,
	};

	it( 'renders the ContainerPanel with create form content when not a saved summary', () => {
		const component = renderer.create( <RSVPContainer { ...defaultProps } /> );
		const json = JSON.stringify( component.toJSON() );

		expect( json ).toContain( 'rsvp-container-content' );
		expect( json ).not.toContain( 'rsvp-saved-summary' );
	} );

	it( 'renders the saved summary once the RSVP is created and the create form is closed', () => {
		const component = renderer.create(
			<RSVPContainer { ...defaultProps } created={ true } isAddEditOpen={ false } />
		);
		const tree = component.toJSON();
		const json = JSON.stringify( tree );

		expect( tree.props.className ).toContain( 'tribe-editor__rsvp-container--saved-summary' );
		expect( json ).toContain( 'rsvp-saved-summary' );
		expect( json ).toContain( 'rsvp-attendee-information-section' );
		expect( json ).toContain( 'rsvp-remove-rsvp' );
		expect( json ).not.toContain( 'rsvp-container-content' );
	} );

	it( 'passes clientId, created, and isSelected through to the saved summary components', () => {
		const component = renderer.create(
			<RSVPContainer
				{ ...defaultProps }
				clientId="rsvp-42"
				created={ true }
				isAddEditOpen={ false }
				isSelected={ false }
			/>
		);
		const json = JSON.stringify( component.toJSON() );

		expect( json ).toContain( 'rsvp-42-false' );
		expect( json ).toContain( 'rsvp-42-true-false' );
	} );

	it( 'applies the disabled modifier class when isDisabled is true', () => {
		const component = renderer.create(
			<RSVPContainer { ...defaultProps } created={ true } isAddEditOpen={ false } isDisabled={ true } />
		);
		const tree = component.toJSON();

		expect( tree.props.className ).toContain( 'tribe-editor__rsvp-container--disabled' );
	} );
} );
