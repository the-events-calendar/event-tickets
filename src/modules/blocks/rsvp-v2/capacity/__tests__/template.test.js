/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import RSVPV2Capacity from '../template';

describe( 'RSVPV2Capacity', () => {
	const defaultProps = {
		onTempCapacityChange: jest.fn(),
		tempCapacity: '',
	};

	it( 'should render Limit label and help text', () => {
		const component = renderer.create( <RSVPV2Capacity { ...defaultProps } /> );
		expect( component.toJSON() ).toMatchSnapshot();
	} );
} );
