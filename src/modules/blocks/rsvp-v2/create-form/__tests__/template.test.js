jest.mock( '../../capacity/container', () => () => <div data-testid="v2-capacity" /> );
jest.mock( '../../duration-fields/container', () => () => <div data-testid="v2-duration" /> );
jest.mock( '../../attendee-registration-link/container', () => () => (
	<div data-testid="attendee-link">+ Collect attendee information</div>
) );

/**
 * External dependencies
 */
import React from 'react';
import renderer from 'react-test-renderer';

/**
 * Internal dependencies
 */
import RSVPCreateForm from '../template';

describe( 'RSVPCreateForm', () => {
	const defaultProps = {
		clientId: 'test-client-id',
	};

	it( 'should render Add RSVP title and form fields', () => {
		const component = renderer.create( <RSVPCreateForm { ...defaultProps } /> );
		const tree = component.toJSON();
		expect( tree ).toMatchSnapshot();
	} );

	it( 'should always render attendee registration link', () => {
		const component = renderer.create( <RSVPCreateForm { ...defaultProps } /> );
		const json = JSON.stringify( component.toJSON() );
		expect( json ).toContain( 'Collect attendee information' );
	} );
} );
