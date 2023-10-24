/**
 * External dependencies
 */
const { resolve } = require( 'path' );
const { reduce, zipObject } = require( 'lodash' );
const merge = require( 'webpack-merge' );
const common = require( '@the-events-calendar/product-taskmaster/webpack/common/webpack.config' );
const { getDirectoryNames, getDirectories } = require( '@the-events-calendar/product-taskmaster/webpack/utils/directories' );
const { getJSFileNames, getJSFiles } = require( '@the-events-calendar/product-taskmaster/webpack/utils/files' );
const MiniCssExtractPlugin = require ( 'mini-css-extract-plugin' );

const PLUGIN_SCOPE = 'tickets';

//
// ────────────────────────────────────────────────────────────────────────────────────── I ──────────
//   :::::: G E N E R A T E   E V E N T S   P L U G I N : :  :   :    :     :        :          :
// ──────────────────────────────────────────────────────────────────────────────────────────────
//

const compileContext = process.env.COMPILE_BLOCKS ? 'blocks' : 'default';
let config;
let miniCssExtractPlugin;
if ( compileContext === 'blocks' ) {
	const isProduction = process.env.NODE_ENV === 'production';
	const postfix = isProduction ? 'min.css' : 'css';

	const blocks = [
		{
			name: 'tickets-editor',
			entry: resolve ( __dirname, './src/Tickets/Blocks/Tickets/app/editor/index.js' ),
			outputScript: './build/Tickets/Blocks/Tickets/editor.min.js',
			outputStyle: `build/Tickets/Blocks/Tickets/editor.${ postfix }`
		},
		{
			name: 'ticket-editor',
			entry: resolve ( __dirname, './src/Tickets/Blocks/Ticket/app/editor/index.js' ),
			outputScript: './build/Tickets/Blocks/Ticket/editor.min.js',
			outputStyle: `build/Tickets/Blocks/Ticket/editor.${ postfix }`
		}
	];

	// Create the entry object from the entry of each block.
	config = merge ( common, {
		entry: reduce ( blocks, ( result, block ) => ( {
			...result,
			[ block.name ]: block.entry
		} ), {} ),
	} );

	// Instance the MiniCssExtractPlugin that will be used to extract the CSS.
	// Do this now to "hack" it by setting the `option.filename` at runtime (see below).
	// This is possible since the `MiniCssExtractPlugin` instance properties are
	// not read-only.
	miniCssExtractPlugin = new MiniCssExtractPlugin ( {
		// Start by configuring the plugin on the first entry.
		// This plugin will run **before** the output function below.
		filename: blocks[0].outputStyle
	} );

	// Leverage WebPack support for a function to provide the output filename.
	config.output = {
		path: __dirname,
		filename: function ( chunkData ) {
			const name = chunkData.chunk.name;
			const block = blocks.find ( block => block.name === name );

			// Hack: update the MiniCssExtractPlugin instance with the correct filename.
			miniCssExtractPlugin.options.filename = block.outputStyle;

			return block.outputScript;
		}
	};
} else {
	config = merge ( common, {
		entry: {
			main: resolve ( __dirname, './src/modules/index.js' ),
		},
		output: {
			path: __dirname,
			library: [ 'tribe', PLUGIN_SCOPE ],
		},
	} );
}

// If COMPILE_SOURCE_MAPS env var is set, then set devtool=eval-source-map
if ( process.env.COMPILE_SOURCE_MAPS ) {
	config.devtool = 'eval-source-map';
}

//
// ──────────────────────────────────────────────────────────────────────────────────────────── II ──────────
//   :::::: G E N E R A T E   S T Y L E S   F R O M   V I E W S : :  :   :    :     :        :          :
// ──────────────────────────────────────────────────────────────────────────────────────────────────────
//

const stylePath = resolve( __dirname, './src/styles' );
const styleDirectories = getDirectories( stylePath );
const styleDirectoryNames = getDirectoryNames( stylePath );
const styleEntries = zipObject( styleDirectoryNames, styleDirectories );

const removeExtension = ( str ) => str.slice( 0, str.lastIndexOf( '.' ) );

const entries = reduce( styleEntries, ( result, dirPath, dirName ) => {
	const jsFiles = getJSFiles( dirPath );
	const jsFileNames = getJSFileNames( dirPath );
	const entryNames = jsFileNames.map(
		filename => `${ dirName }/${ removeExtension( filename ) }`
	);
	return {
		...result,
		...zipObject( entryNames, jsFiles ),
	};
}, { } );

const styleConfig = merge ( common, {
	entry: entries,
	output: {
		path: __dirname,
	},
} );

if ( compileContext === 'blocks' ) {
	// Find the index of the MiniCssExtractPlugin in the config.plugins array.
	const miniCssExtractPluginIndex = config.plugins.findIndex ( plugin => plugin instanceof MiniCssExtractPlugin );
	if ( miniCssExtractPluginIndex >= 0 ) {
		// Replace the MiniCssExtractPlugin with a new instance that has a different filename.
		config.plugins[ miniCssExtractPluginIndex ] = miniCssExtractPlugin;
	}
}

//
// ─── EXPORT CONFIGS ─────────────────────────────────────────────────────────────
//

module.exports = [
	config,
	styleConfig,
];
