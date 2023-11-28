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
		entry: './src/Tickets/Blocks/app/flexible-tickets/block-editor/index.js',
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
		entry: './src/Tickets/Blocks/app/flexible-tickets/classic-editor/index.js',
		outputScript: './build/FlexibleTickets/classic-editor.min.js',
		outputStyle: `build/FlexibleTickets/classic-editor.${postfix}`,
		moveFromTo: {
			'src/resources/js/app/flexible-tickets-classic-editor.js':
				'build/FlexibleTickets/classic-editor.js',
			'src/resources/css/app/flexible-tickets-classic-editor.css':
				'build/FlexibleTickets/classic-editor.css',
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

// Configure multiple entry points.
const config = merge(common, {
	entry: targetEntries,
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
