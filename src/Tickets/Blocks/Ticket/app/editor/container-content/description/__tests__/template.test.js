/**
 * External dependencies
 */
import React from 'react';

import Description from './../template';

/* eslint-disable max-len */
describe( 'Description', () => {
	test( 'Render the component with no errors', () => {
		const onTempDescriptionChange = jest.fn();
		const component = renderer.create( <Description onTempDescriptionChange={ onTempDescriptionChange } /> );
		expect( component.toJSON() ).toMatchSnapshot();
	} );

	test( 'Triggers the onTempDescriptionChange callback', () => {
		const onTempDescriptionChange = jest.fn();
		const component = mount( <Description onTempDescriptionChange={ onTempDescriptionChange } value={ 'tickets-description' } /> );
		component.find( 'input' ).simulate( 'change' );
		expect( onTempDescriptionChange ).toHaveBeenCalled();
	} );
} );
/* eslint-enable max-len */
