/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import TicketDurationPicker from './../template';

describe( 'Ticket Duration picker and label', () => {
	test( 'default properties', () => {
		const component = renderer.create( <TicketDurationPicker /> );
		expect( component.toJSON() ).toMatchSnapshot();
	} );
} );
