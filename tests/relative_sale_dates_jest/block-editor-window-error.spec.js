import { getLocalizedData } from '@tec/tickets/relative-sale-dates/block-editor/localized-data';
import { getBoundaryErrors } from '@tec/tickets/relative-sale-dates/block-editor/window-error';
import {
	BLOCK_SALES_WINDOW,
	BLOCK_SALE_PRICE_WINDOW,
} from '@tec/tickets/relative-sale-dates/block-editor/window-kinds';
import {
	ENDS_BEFORE_START,
	OUTSIDE_PARENT,
	RELATIVE_VALUE_OUT_OF_RANGE,
} from '@tec/tickets/relative-sale-dates/window-errors';
import { clearBlockEditorGlobals, setBlockEditorData } from './block-editor-event-state';

jest.mock( '@wordpress/data', () => require( './wordpress-data-registry' ) );

// `@wordpress/element` is not installed: the block editor provides it at runtime, as a re-export of React.
jest.mock( '@wordpress/element', () => require( 'react' ) );

// The shared manual mock of `@wordpress/i18n` has no `_x`; the real package translates nothing without data.
jest.mock( '@wordpress/i18n', () => jest.requireActual( '@wordpress/i18n' ) );

// A start whose number no kind takes, so a range error names the start.
const RULE = { start: { mode: 'relative', value: 99, unit: 86400 }, end: { mode: 'relative', value: 1, unit: 86400 } };

describe( 'getBoundaryErrors', () => {
	beforeEach( () => {
		setBlockEditorData();
	} );

	afterEach( () => {
		clearBlockEditorGlobals();
	} );

	it.each( [
		[ ENDS_BEFORE_START, 'the sales window', BLOCK_SALES_WINDOW, 'endsBeforeStart' ],
		[ ENDS_BEFORE_START, 'the sale price window', BLOCK_SALE_PRICE_WINDOW, 'endsBeforeStart' ],
		[ OUTSIDE_PARENT, 'the sale price window', BLOCK_SALE_PRICE_WINDOW, 'outsideParent' ],
	] )(
		'should word the %s error of %s under its end with the text the server localizes for the kind',
		( key, title, kind, textKey ) => {
			const expected = getLocalizedData().windowErrorTexts[ kind.id ][ textKey ];

			expect( getBoundaryErrors( key, RULE, kind ) ).toStrictEqual( {
				start: { errorMessage: '', valueErrorMessage: '' },
				end: { errorMessage: expected, valueErrorMessage: '' },
			} );
		}
	);

	it.each( [
		[ 'the sales window', BLOCK_SALES_WINDOW ],
		[ 'the sale price window', BLOCK_SALE_PRICE_WINDOW ],
	] )( 'should word the range error of %s on the number out of range', ( title, kind ) => {
		const expected = getLocalizedData().windowErrorTexts[ kind.id ].valueOutOfRange;

		expect( getBoundaryErrors( RELATIVE_VALUE_OUT_OF_RANGE, RULE, kind ) ).toStrictEqual( {
			start: { errorMessage: '', valueErrorMessage: expected },
			end: { errorMessage: '', valueErrorMessage: '' },
		} );
	} );

	it( 'should give no message for an error a kind cannot have', () => {
		expect( getBoundaryErrors( OUTSIDE_PARENT, RULE, BLOCK_SALES_WINDOW ).end.errorMessage ).toBe( '' );
	} );

	it( 'should give no message without an error', () => {
		expect( getBoundaryErrors( null, RULE, BLOCK_SALE_PRICE_WINDOW ) ).toStrictEqual( {
			start: { errorMessage: '', valueErrorMessage: '' },
			end: { errorMessage: '', valueErrorMessage: '' },
		} );
	} );
} );
