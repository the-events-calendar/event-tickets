/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import Header from './../template';

jest.mock( 'react-input-autosize', () => ( props ) => ( <span { ...props }>Auto Size Input</span> ) );

describe( 'Ticket Form Header', () => {
	test( 'Header with no expiration', () => {
		const component = renderer.create( <Header /> );
		expect( component.toJSON() ).toMatchSnapshot();
	} );

	test( 'Header with expiration', () => {
		const component = renderer.create( <Header expires={ true } /> );
		expect( component.toJSON() ).toMatchSnapshot();
	} );

	test( 'Header with default values', () => {
		const component = renderer.create(
			<Header price={ 20 } title="VIP" description="Includes backstage" />
		);
		expect( component.toJSON() ).toMatchSnapshot();
	} );
} );
