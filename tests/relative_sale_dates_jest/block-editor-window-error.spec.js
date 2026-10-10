import { getWindowErrorMessage } from '@tec/tickets/relative-sale-dates/block-editor/window-error';
import {
	BLOCK_SALES_WINDOW,
	BLOCK_SALE_PRICE_WINDOW,
} from '@tec/tickets/relative-sale-dates/block-editor/window-kinds';
import {
	ENDS_BEFORE_START,
	OUTSIDE_PARENT,
	RELATIVE_VALUE_OUT_OF_RANGE,
} from '@tec/tickets/relative-sale-dates/window-errors';

jest.mock( '@wordpress/data', () => require( './wordpress-data-registry' ) );

// `@wordpress/element` is not installed: the block editor provides it at runtime, as a re-export of React.
jest.mock( '@wordpress/element', () => require( 'react' ) );

// The shared manual mock of `@wordpress/i18n` has no `_x`; the real package translates nothing without data.
jest.mock( '@wordpress/i18n', () => jest.requireActual( '@wordpress/i18n' ) );

describe( 'getWindowErrorMessage', () => {
	it.each( [
		[
			ENDS_BEFORE_START,
			'the sales window',
			BLOCK_SALES_WINDOW,
			'Ticket sales cannot end before they start. Please adjust the sales window.',
		],
		[ RELATIVE_VALUE_OUT_OF_RANGE, 'the sales window', BLOCK_SALES_WINDOW, 'Enter a number from 1 to 60.' ],
		[
			ENDS_BEFORE_START,
			'the sale price window',
			BLOCK_SALE_PRICE_WINDOW,
			'The sale price cannot end before it starts. Please adjust the sale price window.',
		],
		[
			RELATIVE_VALUE_OUT_OF_RANGE,
			'the sale price window',
			BLOCK_SALE_PRICE_WINDOW,
			'Enter a number from 1 to 30.',
		],
		[
			OUTSIDE_PARENT,
			'the sale price window',
			BLOCK_SALE_PRICE_WINDOW,
			'The sale price window falls outside the ticket sales window. Please adjust the dates.',
		],
	] )( 'should word the %s error of %s as the kind words it', ( key, title, kind, expected ) => {
		expect( getWindowErrorMessage( key, kind ) ).toBe( expected );
	} );

	it( 'should give no message for an error a kind cannot have', () => {
		expect( getWindowErrorMessage( OUTSIDE_PARENT, BLOCK_SALES_WINDOW ) ).toBe( '' );
	} );

	it( 'should give no message without an error', () => {
		expect( getWindowErrorMessage( null, BLOCK_SALE_PRICE_WINDOW ) ).toBe( '' );
	} );
} );
