/**
 * Set webpack public path dynamically.
 *
 * This file should be imported FIRST in any entry point that uses dynamic imports
 * or loads assets (images, fonts, etc.) at runtime.
 *
 * Usage in your entry point:
 *   import '../webpack-public-path';
 *
 * @since 5.26.6
 */

// Check if the public path was set by PHP (via admin_head hook).
// Use our namespaced variable to avoid conflicts.
if ( typeof window.etWebpackPublicPath !== 'undefined' ) {
	// eslint-disable-next-line camelcase, no-undef
	__webpack_public_path__ = window.etWebpackPublicPath;
}
