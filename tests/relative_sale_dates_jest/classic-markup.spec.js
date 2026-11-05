/**
 * External dependencies
 */
import fs from 'fs';
import path from 'path';

/**
 * Internal dependencies
 */
import { writeRule } from '@tec/tickets/relative-sale-dates/classic/rule';

/*
 * The other classic specs build the form from markup written by hand, so they keep passing when the template renames
 * a field. These read the ticket panels the PHP integration suite renders and snapshots instead: regenerating those
 * snapshots after a rename in `sales-window.php` fails here until the classic script follows.
 */
const SNAPSHOTS_DIR = path.resolve( __dirname, '../integration/Tribe/Tickets/__snapshots__' );

const SNAPSHOTS = fs
	.readdirSync( SNAPSHOTS_DIR )
	.filter( ( file ) => file.startsWith( 'MetaboxTest__test_get_panels__event with ticket' ) );

const ENDS = [ 'start', 'end' ];

/*
 * The ids `rule.js` reads each end of the window from, the hidden field it writes, and the date inputs `date-limits.js`
 * limits.
 */
const FIELD_IDS = [
	...ENDS.flatMap( ( end ) =>
		[ 'mode', 'value', 'unit', 'anchor' ].map( ( name ) => `ticket_sales_${ end }_${ name }` )
	),
	'ticket_relative_sale_dates',
	'ticket_start_date',
	'ticket_end_date',
];

/**
 * Renders the ticket panels of a snapshot as the document.
 *
 * @param {string} file The snapshot file name.
 */
function renderSnapshot( file ) {
	document.body.innerHTML = fs.readFileSync( path.join( SNAPSHOTS_DIR, file ), 'utf8' );
}

describe( 'classic sales window markup', () => {
	afterEach( () => {
		document.body.innerHTML = '';
	} );

	it( 'should find the ticket panel snapshots', () => {
		expect( SNAPSHOTS.length ).toBeGreaterThan( 0 );
	} );

	describe.each( SNAPSHOTS )( '%s', ( file ) => {
		beforeEach( () => renderSnapshot( file ) );

		it.each( FIELD_IDS )( 'should render the #%s field the classic script reads', ( id ) => {
			expect( document.getElementById( id ) ).not.toBeNull();
		} );

		it.each( ENDS )(
			'should render the %s mode as a select offering the modes the classic script reads',
			( end ) => {
				const select = document.getElementById( `ticket_sales_${ end }_mode` );
				const modes = Array.from( select.options, ( option ) => option.value );

				// `index.js` delegates the mode changes from the metabox to `select` elements.
				expect( select.tagName ).toBe( 'SELECT' );
				expect( modes ).toEqual( expect.arrayContaining( [ 'relative', 'specific' ] ) );
			}
		);

		it( 'should write the rule from the rendered fields', () => {
			writeRule( document );

			const rule = JSON.parse( document.getElementById( 'ticket_relative_sale_dates' ).value );

			expect( rule.start.mode ).toBe( document.getElementById( 'ticket_sales_start_mode' ).value );
			expect( rule.end.mode ).toBe( document.getElementById( 'ticket_sales_end_mode' ).value );
		} );
	} );
} );
