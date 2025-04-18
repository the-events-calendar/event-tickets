const {dirname, basename, extname} = require('path');
const {readdirSync, statSync, existsSync} = require('fs');

/**
 * The default configuration coming from the @wordpress/scripts package.
 * Customized following the "Advanced Usage" section of the documentation:
 * See: https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/#advanced-usage
 */
const defaultConfig = require('@wordpress/scripts/config/webpack.config');

const {
  createTECLegacyJs,
  createTECPostCss,
  createTECLegacyBlocksFrontendPostCss,
  createTECPackage,
  compileCustomEntryPoints,
  exposeEntry,
  doNotPrefixSVGIdsClasses,
  WindowAssignPropertiesPlugin,
} = require('@stellarwp/tyson');

/**
 * Compile a list of entry points to be compiled to the format used by WebPack to define multiple entry points.
 * This is akin to the compilation system used for multi-page applications.
 * See: https://webpack.js.org/concepts/entry-points/#multi-page-application
 */
const customEntryPoints = compileCustomEntryPoints({
  /**
   * All existing Javascript files will be compiled to ES6, most will not be changed at all,
   * minified and cleaned up.
   * This is mostly a pass-thru with the additional benefit that the compiled packages will be
   * exposed on the `window.tec.tickets` object.
   * E.g. the `src/resources/js/admin-ignored-events.js` file will be compiled to
   * `/build/js/admin-ignored-events.js` and exposed on `window.tec.tickets.adminIgnoredEvents`.
   */
  '/src/resources/js': createTECLegacyJs('tec.tickets'),

  /**
   * Compile, recursively, the PostCSS file using PostCSS nesting rules.
   * By default, the `@wordpress/scripts` configuration would compile files using the CSS
   * nesting syntax (https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_nesting) where
   * the `&` symbol indicates the parent element.
   * The PostCSS syntax followed in TEC files will instead use the `&` symbol to mean "this element".
   * Handling this correctly requires adding a PostCSS processor specific to the PostCSS files that
   * will handle the nesting correctly.
   * Note the plugin will need to specify the following development dependencies: postcss-nested, postcss-preset-env,
   * postcss-mixins, postcss-import, postcss-custom-media.
   */
  '/src/resources/postcss': createTECPostCss('tec.tickets'),

  /**
   * This deals with existing Blocks frontend styles being compiled separately.
   * The main function of this configuration schema is to ensure they are placed correctly.
   */
  '/src/styles': createTECLegacyBlocksFrontendPostCss('tec.tickets'),

  /**
   * This deals with packages written following modern module-based approaches.
   * These packages are usually not Blocks and require `@wordpress/scripts` to be explicitly
   * instructed about them to compile correctly.
   * To avoid having to list each package, here the configuration schema is used to recursively
   * pick them up and namespace them.
   */
  '/src/resources/packages': createTECPackage('tec.tickets'),
}, defaultConfig);

/**
 * Following are static entry points, to be included in the build non-recursively.
 * These are built following a modern module approach where the root `index.js` file
 * will include the whole module.
 */

/**
 * Blocks from `/src/modules/index.js` are built to `/build/app/main.js`.
 * The existing Block Editor code does not follow the `block.json` based convention expected by
 * `@wordpress/scripts` so here we explicitly point out the root index.
 */
customEntryPoints['app/main'] = exposeEntry('tec.tickets.app.main', __dirname + '/src/modules/index.js');

customEntryPoints['tickets/Blocks/Tickets/editor'] = exposeEntry('tec.tickets.blocks.tickets.editor', __dirname + '/src/Tickets/Blocks/Tickets/app/editor/index.js');
customEntryPoints['tickets/Blocks/Ticket/editor'] = exposeEntry('tec.tickets.blocks.ticket.editor', __dirname + '/src/Tickets/Blocks/Ticket/app/editor/index.js');
customEntryPoints['FlexibleTickets/block-editor'] = exposeEntry('tec.tickets.flexibleTickets.blockEditor', __dirname + '/src/Tickets/Flexible_Tickets/app/block-editor/index.js');
customEntryPoints['FlexibleTickets/classic-editor'] = exposeEntry('tec.tickets.flexibleTickets.classicEditor', __dirname + '/src/Tickets/Flexible_Tickets/app/classic-editor/index.js');
customEntryPoints['Seating/utils'] = exposeEntry('tec.tickets.seating.utils', __dirname + '/src/Tickets/Seating/app/utils/index.js');
customEntryPoints['Seating/ajax'] = exposeEntry('tec.tickets.seating.ajax', __dirname + '/src/Tickets/Seating/app/ajax/index.js');
customEntryPoints['Seating/currency'] = exposeEntry('tec.tickets.seating.currency', __dirname + '/src/Tickets/Seating/app/currency/index.js');
customEntryPoints['Seating/service'] = exposeEntry('tec.tickets.seating.service', __dirname + '/src/Tickets/Seating/app/service/index.js');
customEntryPoints['Seating/admin/maps'] = exposeEntry('tec.tickets.seating.admin.maps', __dirname + '/src/Tickets/Seating/app/admin/maps/index.js');
customEntryPoints['Seating/admin/layouts'] = exposeEntry('tec.tickets.seating.admin.layouts', __dirname + '/src/Tickets/Seating/app/admin/layouts/index.js');
customEntryPoints['Seating/admin/mapEdit'] = exposeEntry('tec.tickets.seating.admin.mapEdit', __dirname + '/src/Tickets/Seating/app/admin/mapEdit/index.js');
customEntryPoints['Seating/admin/layoutEdit'] = exposeEntry('tec.tickets.seating.admin.layoutEdit', __dirname + '/src/Tickets/Seating/app/admin/layoutEdit/index.js');
customEntryPoints['Seating/admin/seatsReport'] = exposeEntry('tec.tickets.seating.admin.seatsReport', __dirname + '/src/Tickets/Seating/app/admin/seatsReport/index.js');
customEntryPoints['Seating/blockEditor'] = exposeEntry('tec.tickets.seating.blockEditor', __dirname + '/src/Tickets/Seating/app/blockEditor/index.js');
customEntryPoints['Seating/frontend/session'] = exposeEntry('tec.tickets.seating.frontend.session', __dirname + '/src/Tickets/Seating/app/frontend/session/index.js');
customEntryPoints['Seating/frontend/ticketsBlock'] = exposeEntry('tec.tickets.seating.frontend.ticketsBlock', __dirname + '/src/Tickets/Seating/app/frontend/ticketsBlock/index.js');
customEntryPoints['OrderModifiers/rest'] = exposeEntry('tec.tickets.orderModifiers.rest', __dirname + '/src/Tickets/Commerce/Order_Modifiers/app/rest/index.js');
customEntryPoints['OrderModifiers/blockEditor'] = exposeEntry('tec.tickets.orderModifiers.blockEditor', __dirname + '/src/Tickets/Commerce/Order_Modifiers/app/blockEditor/index.js');

// Remove wizard package from Tyson compilation
Object.keys(customEntryPoints).forEach(key => {
  if (key.includes('wizard')) {
    delete customEntryPoints[key];
  }
});
// Add wizard package manually
customEntryPoints['wizard/wizard'] = {
  import: __dirname + '/src/resources/packages/wizard/index.tsx',
  library: {
    name: ['tec', 'tickets', 'wizard'],
    type: 'window',
  }
};

/**
 * Prepends a loader for SVG files that will be applied after the default one. Loaders are applied
 * in a LIFO queue in WebPack.
 * By default, `@wordpress/scripts` uses `@svgr/webpack` to handle SVG files and, together with it,
 * the default SVGO (package `svgo/svgo-loader`) configuration that includes the `prefixIds` plugin.
 * To avoid `id` and `class` attribute conflicts, the `prefixIds` plugin would prefix all `id` and
 * `class` attributes in SVG tags with a generated prefix. This would break TEC classes (already
 * namespaced) so here we prepend a rule to handle SVG files in the `src/modules` directory by
 * disabling the `prefixIds` plugin.
 */
doNotPrefixSVGIdsClasses(defaultConfig);

/**
 * Finally the customizations are merged with the default WebPack configuration.
 */
module.exports = {
  ...defaultConfig,
  ...{
    entry: (buildType) => {
      const defaultEntryPoints = defaultConfig.entry(buildType);
      return {
        ...defaultEntryPoints, ...customEntryPoints,
      };
    },
    optimization: {
      ...defaultConfig.optimization,
      ...{
				moduleIds: 'hashed',
        splitChunks: {
          ...defaultConfig.optimization.splitChunks,
					minSize: 50,
					cacheGroups: {
						...defaultConfig.optimization.splitChunks.cacheGroups,
						vendor: {
							test: /[\\/]node_modules[\\/]/,
							name: 'vendor',
							chunks: 'all',
							priority: 10,
						},
						'vendor-babel-runtime': {
							test: /[\\/]node_modules[\\/]@babel[\\/]/,
							name: 'vendor-babel',
							chunks: 'all',
							priority: 20,
						},
					},
        },
      },
    },
    output: {
      ...defaultConfig.output,
      ...{
        enabledLibraryTypes: ['window'],
        publicPath: '/wp-content/plugins/event-tickets/build/',
      },
    },
    module: {
      ...defaultConfig.module,
      rules: [
        ...defaultConfig.module.rules,
        {
          test: /\.(png|jpg|jpeg|gif|svg)$/i,
          include: /src\/resources\/packages/,
          type: 'asset/resource',
          generator: {
            filename: 'images/[name].[contenthash][ext]'
          }
        }
      ]
    },
    plugins: [
      ...defaultConfig.plugins,
      new WindowAssignPropertiesPlugin(),
    ],
  },
};
