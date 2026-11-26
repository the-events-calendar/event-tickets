/**
 * Tests for container-panel/style.pcss
 *
 * @since 5.31.2
 */

const fs = require( 'fs' );
const path = require( 'path' );

describe( 'container-panel style.pcss', () => {
	const source = fs.readFileSync( path.resolve( __dirname, '../style.pcss' ), 'utf8' );

	test( 'keeps the ticket panel content inside the block, padding included', () => {
		const rule = source.match( /\.tribe-editor__container-panel--ticket & \{([^}]*)\}/g ).find( ( block ) =>
			block.includes( 'width: 100%' ) && block.includes( 'padding' )
		);

		expect( rule ).toContain( 'box-sizing: border-box' );
	} );
} );
