/**
 * External dependencies
 */
import React from 'react';

import IACSetting from './../template';

describe( 'IACSetting', () => {
	test( 'Render the component with no errors', () => {
		const onChange = jest.fn();
		const component = renderer.create( <IACSetting onChange={ onChange } /> );
		expect( component.toJSON() ).toMatchSnapshot();
	} );

	test( 'Triggers the onChange callback', () => {
		const onChange = jest.fn();
		const component = mount( <IACSetting onChange={ onChange } value={ 'allowed' } /> );
		component.find( 'input' ).simulate( 'change' );
		expect( onChange ).toHaveBeenCalled();
	} );
} );
