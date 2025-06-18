/**
 * External dependencies
 */
import React from 'react';
import renderer from 'react-test-renderer';

/**
 * Internal dependencies
 */
import { WarningButton } from '@moderntribe/tickets/elements';
import { Button } from '@moderntribe/common/elements';

describe( 'WarningButton', () => {
	test( 'component rendered', () => {
		const component = renderer.create(
			<WarningButton icon="no">Warning</WarningButton>,
		);
		expect( component.toJSON() ).toMatchSnapshot();
	} );
} );
