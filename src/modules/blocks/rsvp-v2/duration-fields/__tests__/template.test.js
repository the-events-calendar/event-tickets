/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import RSVPV2DurationFields from '../template';

jest.mock( '../../duration-picker/container', () => () => <div data-testid="duration-picker" /> );

describe( 'RSVPV2DurationFields', () => {
	it( 'should render duration picker with Open and Close RSVP labels', () => {
		const component = renderer.create( <RSVPV2DurationFields /> );
		expect( component.toJSON() ).toMatchSnapshot();
	} );

	it( 'should show error when hasDurationError is true', () => {
		const component = renderer.create( <RSVPV2DurationFields hasDurationError={ true } /> );
		const json = JSON.stringify( component.toJSON() );
		expect( json ).toContain( 'error with the selected sales duration' );
	} );
} );
