/**
 * External dependencies
 */
import React from 'react';

import Title from './../template';

describe( 'Title', () => {
	test( 'Render the component with no errors', () => {
		const onTempTitleChange = jest.fn();
		const component = renderer.create( <Title onTempTitleChange={ onTempTitleChange } /> );
		expect( component.toJSON() ).toMatchSnapshot();
	} );

	test( 'Triggers the onTempTitleChange callback', () => {
		const onTempTitleChange = jest.fn();
		const component = mount( <Title onTempTitleChange={ onTempTitleChange } value={ 'tickets-title' } /> ); // eslint-disable-line max-len
		component.find( 'input' ).simulate( 'change' );
		expect( onTempTitleChange ).toHaveBeenCalled();
	} );
} );
