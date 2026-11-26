/**
 * Tests for the RSVP card in rsvp/style.pcss and rsvp-shared/style.pcss
 *
 * @since 5.31.2
 */

const fs = require( 'fs' );
const path = require( 'path' );

describe( 'RSVP card styles', () => {
	test.each( [ '../style.pcss', '../../rsvp-shared/style.pcss' ] )(
		'keeps the card inside the block, padding and border included, in %s',
		( file ) => {
			const source = fs.readFileSync( path.resolve( __dirname, file ), 'utf8' );
			// The card's own declarations, up to its first nested rule.
			const rule = source.match( /^\.tribe-editor__inactive-block--rsvp \{[^{}]*/m )[ 0 ];

			expect( rule ).toContain( 'width: 100%' );
			expect( rule ).toContain( 'box-sizing: border-box' );
		}
	);
} );
