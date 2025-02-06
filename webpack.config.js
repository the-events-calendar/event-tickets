/**
 * External dependencies
 */
const { resolve } = require('path');
const { reduce, zipObject } = require('lodash');
const merge = require('webpack-merge');
const common = require('@the-events-calendar/product-taskmaster/webpack/common/webpack.config');
const {
	getDirectoryNames,
	getDirectories,
} = require('@the-events-calendar/product-taskmaster/webpack/utils/directories');
const {
	getJSFileNames,
	getJSFiles,
} = require('@the-events-calendar/product-taskmaster/webpack/utils/files');

// Do we need to expose this as a variable?
const PLUGIN_SCOPE = 'tickets';

//
// ────────────────────────────────────────────────────────────────────────────────────── I ──────────
//   :::::: G E N E R A T E   E V E N T S   P L U G I N : :  :   :    :     :        :          :
// ──────────────────────────────────────────────────────────────────────────────────────────────
//

/**
 * By default, the optimization would break all modules from the `node_modules` directory
 * in a `src/resources/js/app/vendor.js` file. That file would include React and block-editor
 * dependencies that are not always required on the frontend. This modification of the default
 * optimization will create two files: one (`src/resources/js/app/vendor-babel.js`) that contains
 * only the Babel transpilers and one (`src/resources/js/app/vendor.js`) that contains all the
 * other dependencies. The second file (`src/resources/js/app/vendor.js`) MUST require the first
 * (`src/resources/js/app/vendor-babel.js`) file as a dependency.
 */
common.optimization.splitChunks.cacheGroups['vendor-babel-runtime'] = {
	name: 'vendor-babel',
	chunks: 'all',
	test: /[\\/]node_modules[\\/]@babel[\\/]/,
	priority: 20,
};
common.optimization.splitChunks.cacheGroups.vendor.priority = 10;

const isProduction = process.env.NODE_ENV === 'production';
const postfix = isProduction ? 'min.css' : 'css';

// The targets we would like to compile.
// The `moveFromTo` property is used to move the files in place after the build completed using the
// `MoveTargetsInPlace` plugin; see below.
const targets = [
	{
		name: 'main',
		entry: './src/modules/index.js',
		outputScript: './src/resources/js/app/main.min.js',
		outputStyle: `src/resources/css/app/[name].${postfix}`,
	},
	{
		name: 'tickets-editor',
		entry: './src/Tickets/Blocks/Tickets/app/editor/index.js',
		outputScript: './build/Tickets/Blocks/Tickets/editor.min.js',
		outputStyle: `build/Tickets/Blocks/Tickets/editor.${postfix}`,
		moveFromTo: {
			'src/resources/js/app/tickets-editor.js':
				'build/Tickets/Blocks/Tickets/editor.js',
			'src/resources/css/app/tickets-editor.css':
				'build/Tickets/Blocks/Tickets/editor.css',
		},
	},
	{
		name: 'ticket-editor',
		entry: './src/Tickets/Blocks/Ticket/app/editor/index.js',
		outputScript: './build/Tickets/Blocks/Ticket/editor.min.js',
		outputStyle: `build/Tickets/Blocks/Ticket/editor.${postfix}`,
		moveFromTo: {
			'src/resources/js/app/ticket-editor.js':
				'build/Tickets/Blocks/Ticket/editor.js',
			'src/resources/css/app/ticket-editor.css':
				'build/Tickets/Blocks/Ticket/editor.css',
		},
	},
	{
		name: 'flexible-tickets-block-editor',
		entry: './src/Tickets/Flexible_Tickets/app/block-editor/index.js',
		outputScript: './build/FlexibleTickets/block-editor.min.js',
		outputStyle: `build/FlexibleTickets/block-editor.${postfix}`,
		moveFromTo: {
			'src/resources/js/app/flexible-tickets-block-editor.js':
				'build/FlexibleTickets/block-editor.js',
			'src/resources/css/app/flexible-tickets-block-editor.css':
				'build/FlexibleTickets/block-editor.css',
		},
	},
	{
		name: 'flexible-tickets-classic-editor',
		entry: './src/Tickets/Flexible_Tickets/app/classic-editor/index.js',
		outputScript: './build/FlexibleTickets/classic-editor.min.js',
		outputStyle: `build/FlexibleTickets/classic-editor.${postfix}`,
		moveFromTo: {
			'src/resources/js/app/flexible-tickets-classic-editor.js':
				'build/FlexibleTickets/classic-editor.js',
			'src/resources/css/app/flexible-tickets-classic-editor.css':
				'build/FlexibleTickets/classic-editor.css',
		},
	},
	{
		name: 'seating-utils',
		entry: './src/Tickets/Seating/app/utils/index.js',
		outputScript: './build/Seating/utils.min.js',
		outputStyle: `build/Seating/utils.${postfix}`,
		moveFromTo: {
			'src/resources/js/app/seating-utils.js': 'build/Seating/utils.js',
			'src`/resources/css/app/seating-utils.css`':
				'build/Seating/utils.css',
		},
	},
	{
		name: 'seating-ajax',
		entry: './src/Tickets/Seating/app/ajax/index.js',
		outputScript: './build/Seating/ajax.min.js',
		outputStyle: `build/Seating/ajax.${postfix}`,
		moveFromTo: {
			'src/resources/js/app/seating-ajax.js': 'build/Seating/ajax.js',
			'src/resources/css/app/seating-ajax.css': 'build/Seating/ajax.css',
		},
	},
	{
		name: 'seating-currency',
		entry: './src/Tickets/Seating/app/currency/index.js',
		outputScript: './build/Seating/currency.min.js',
		outputStyle: `build/Seating/currency.${postfix}`,
		moveFromTo: {
			'src/resources/js/app/seating-currency.js':
				'build/Seating/currency.js',
			'src/resources/css/app/seating-currency.css':
				'build/Seating/currency.css',
		},
	},
	{
		name: 'seating-service-bundle',
		entry: './src/Tickets/Seating/app/service/index.js',
		outputScript: './build/Seating/service.min.js',
		outputStyle: `build/Seating/service.${postfix}`,
		moveFromTo: {
			'src/resources/js/app/seating-service-bundle.js':
				'build/Seating/service.js',
			'src/resources/css/app/seating-service-bundle.css':
				'build/Seating/service.css',
		},
	},
	{
		name: 'seating-maps-bundle',
		entry: './src/Tickets/Seating/app/admin/maps/index.js',
		outputScript: './build/Seating/admin/maps.min.js',
		outputStyle: `build/Seating/admin/maps.${postfix}`,
		moveFromTo: {
			'src/resources/js/app/seating-maps-bundle.js':
				'build/Seating/admin/maps.js',
			'src/resources/css/app/seating-maps-bundle.css':
				'build/Seating/admin/maps.css',
		},
	},
	{
		name: 'seating-layouts-bundle',
		entry: './src/Tickets/Seating/app/admin/layouts/index.js',
		outputScript: './build/Seating/admin/layouts.min.js',
		outputStyle: `build/Seating/admin/layouts.${postfix}`,
		moveFromTo: {
			'src/resources/js/app/seating-layouts-bundle.js':
				'build/Seating/admin/layouts.js',
			'src/resources/css/app/seating-layouts-bundle.css':
				'build/Seating/admin/layouts.css',
		},
	},
	{
		name: 'seating-map-edit-bundle',
		entry: './src/Tickets/Seating/app/admin/mapEdit/index.js',
		outputScript: './build/Seating/admin/mapEdit.min.js',
		outputStyle: `build/Seating/admin/mapEdit.${postfix}`,
		moveFromTo: {
			'src/resources/js/app/seating-map-edit-bundle.js':
				'build/Seating/admin/mapEdit.js',
			'src/resources/css/app/seating-map-edit-bundle.css':
				'build/Seating/admin/mapEdit.css',
		},
	},
	{
		name: 'seating-layout-edit-bundle',
		entry: './src/Tickets/Seating/app/admin/layoutEdit/index.js',
		outputScript: './build/Seating/admin/layoutEdit.min.js',
		outputStyle: `build/Seating/admin/layoutEdit.${postfix}`,
		moveFromTo: {
			'src/resources/js/app/seating-layout-edit-bundle.js':
				'build/Seating/admin/layoutEdit.js',
			'src/resources/css/app/seating-layout-edit-bundle.css':
				'build/Seating/admin/layoutEdit.css',
		},
	},
	{
		name: 'seating-seats-report-bundle',
		entry: './src/Tickets/Seating/app/admin/seatsReport/index.js',
		outputScript: './build/Seating/admin/seatsReport.min.js',
		outputStyle: `build/Seating/admin/seatsReport.${postfix}`,
		moveFromTo: {
			'src/resources/js/app/seating-seats-report-bundle.js':
				'build/Seating/admin/seatsReport.js',
			'src/resources/css/app/seating-seats-report-bundle.css':
				'build/Seating/admin/seatsReport.css',
		},
	},
	{
		name: 'seating-block-editor-bundle',
		entry: './src/Tickets/Seating/app/blockEditor/index.js',
		outputScript: './build/Seating/block-editor.min.js',
		outputStyle: `build/Seating/blockEditor.${postfix}`,
		moveFromTo: {
			'src/resources/js/app/seating-block-editor-bundle.js':
				'build/Seating/blockEditor.js',
			'src/resources/css/app/seating-block-editor-bundle.css':
				'build/Seating/blockEditor.css',
		},
	},
	{
		name: 'seating-frontend-ticketsBlock-bundle',
		entry: './src/Tickets/Seating/app/frontend/ticketsBlock/index.js',
		outputScript: './build/Seating/frontend/ticketsBlock.min.js',
		outputStyle: `build/Seating/frontend/ticketsBlock.${postfix}`,
		moveFromTo: {
			'src/resources/js/app/seating-frontend-ticketsBlock-bundle.js':
				'build/Seating/frontend/ticketsBlock.js',
			'src/resources/css/app/seating-frontend-ticketsBlock-bundle.css':
				'build/Seating/frontend/ticketsBlock.css',
		},
	},
	{
		name: 'seating-frontend-session-bundle',
		entry: './src/Tickets/Seating/app/frontend/session/index.js',
		outputScript: './build/Seating/frontend/session.min.js',
		outputStyle: `build/Seating/frontend/session.${postfix}`,
		moveFromTo: {
			'src/resources/js/app/seating-frontend-session-bundle.js':
				'build/Seating/frontend/session.js',
			'src/resources/css/app/seating-frontend-session-bundle.css':
				'build/Seating/frontend/session.css',
		},
	},
	{
		name: 'order-modifiers-rest',
		entry: './src/Tickets/Commerce/Order_Modifiers/app/rest/index.js',
		outputScript: './build/OrderModifiers/rest.min.js',
		outputStyle: `build/OrderModifiers/rest.${postfix}`,
		moveFromTo: {
			'src/resources/js/app/order-modifiers-rest.js': 'build/OrderModifiers/rest.js',
			'src/resources/css/app/order-modifiers-rest.css': 'build/OrderModifiers/rest.css',
		},
	},
	{
		name: 'order-modifiers-block-editor-bundle',
		entry: './src/Tickets/Commerce/Order_Modifiers/app/blockEditor/index.js',
		outputScript: './build/OrderModifiers/block-editor.min.js',
		outputStyle: `build/OrderModifiers/block-editor.${postfix}`,
		moveFromTo: {
			'src/resources/js/app/order-modifiers-block-editor-bundle.js':
				'build/OrderModifiers/block-editor.js',
			'src/resources/css/app/order-modifiers-block-editor-bundle.css':
				'build/OrderModifiers/block-editor.css',
		},
	},
];

// A function cannot be spread directly, we need this temporary variable.
const targetEntries = reduce(
	targets,
	(carry, target) => ({
		...carry,
		[target.name]: resolve(__dirname, target.entry),
	}),
	{}
);

const config = merge(common, {
	// Add externals missing from products-taskmaster.
	externals: [
		{
			'@wordpress/core-data': 'wp.coreData',
			'@tec/tickets/seating/service/iframe':
				'tec.tickets.seating.service.iframe',
			'@tec/tickets/seating/service/errors':
				'tec.tickets.seating.service.errors',
			'@tec/tickets/seating/service/notices':
				'tec.tickets.seating.service.notices',
			'@tec/tickets/seating/service': 'tec.tickets.seating.service',
			'@tec/tickets/seating/service/api':
				'tec.tickets.seating.service.api',
			'@tec/tickets/seating/utils': 'tec.tickets.seating.utils',
			'@tec/tickets/seating/ajax': 'tec.tickets.seating.ajax',
			'@tec/tickets/seating/currency': 'tec.tickets.seating.currency',
			'@tec/tickets/seating/frontend/session':
				'tec.tickets.seating.frontend.session',
			'@tec/tickets/order-modifiers/rest': 'tec.tickets.orderModifiers.rest',
		},
	],
	// Configure multiple entry points.
	entry: targetEntries,
	resolve: {
	  ...common.resolve,
	  alias: {
		...common.resolve?.alias,
		'react-day-picker/moment': resolve(
			__dirname,
			'node_modules/moment'
		),
	  },
	},
});

// WebPack 4 does support multiple entry and output points, but the plugins used by the build do not.
// For this reason we're setting the output target to a string template.
// The files will be moved to the correct location after the build completed, by the `MoveTargetsInPlace` plugin.
// See below.
config.output = {
	path: __dirname,
	filename: './src/resources/js/app/[name].min.js',
};

// Define, build and add to the stack of plugins a plugin that will move the files in place after they are built.
const fs = require('node:fs');
const normalize = require('path').normalize;

class MoveTargetsInPlace {
	constructor(moveTargets) {
		// Add, to each move target, the minified version of the file.
		Object.keys(moveTargets).forEach((file) => {
			const minFile = file.replace(/\.(js|css)/g, '.min.$1');
			moveTargets[minFile] = moveTargets[file].replace(
				/\.(js|css)/i,
				'.min.$1'
			);
		});
		this.moveTargetsObject = moveTargets;
		this.sourceFiles = Object.keys(moveTargets).map((file) =>
			normalize(file)
		);
		this.moveFile = this.moveFile.bind(this);
	}

	moveFile(file) {
		const normalizedFile = normalize(file);

		if (this.sourceFiles.indexOf(normalizedFile) === -1) {
			return;
		}

		const destination = this.moveTargetsObject[normalizedFile];
		console.log(`Moving ${normalizedFile} to ${destination}...`);

		// Recursively create the directory for the target.
		fs.mkdirSync(destination.replace(/\/[^/]+$/, ''), { recursive: true });

		// Move the target.
		fs.renameSync(normalizedFile, destination);
	}

	apply(compiler) {
		// compiler.hooks.done.tap ( 'MoveTargetsIntoPlace', this.moveTargets );
		compiler.hooks.assetEmitted.tap('MoveTargetsIntoPlace', this.moveFile);
	}
}

const moveTargets = targets.reduce((carry, target) => {
	return {
		...carry,
		...target.moveFromTo,
	};
}, {});
config.plugins.push(new MoveTargetsInPlace(moveTargets));

// If COMPILE_SOURCE_MAPS env var is set, then set devtool=eval-source-map
if (process.env.COMPILE_SOURCE_MAPS) {
	config.devtool = 'eval-source-map';
}

//
// ──────────────────────────────────────────────────────────────────────────────────────────── II ──────────
//   :::::: G E N E R A T E   S T Y L E S   F R O M   V I E W S : :  :   :    :     :        :          :
// ──────────────────────────────────────────────────────────────────────────────────────────────────────
//

const stylePath = resolve(__dirname, './src/styles');
const styleDirectories = getDirectories(stylePath);
const styleDirectoryNames = getDirectoryNames(stylePath);
const styleEntries = zipObject(styleDirectoryNames, styleDirectories);

const removeExtension = (str) => str.slice(0, str.lastIndexOf('.'));

const entries = reduce(
	styleEntries,
	(result, dirPath, dirName) => {
		const jsFiles = getJSFiles(dirPath);
		const jsFileNames = getJSFileNames(dirPath);
		const entryNames = jsFileNames.map(
			(filename) => `${dirName}/${removeExtension(filename)}`
		);
		return {
			...result,
			...zipObject(entryNames, jsFiles),
		};
	},
	{}
);

const styleConfig = merge(common, {
	entry: entries,
	output: {
		path: __dirname,
	},
});

//
// ─── EXPORT CONFIGS ─────────────────────────────────────────────────────────────
//

module.exports = [config, styleConfig];
