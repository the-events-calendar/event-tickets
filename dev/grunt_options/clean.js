/**
 *
 * Module: grunt-contrib-clean
 * Documentation: https://npmjs.org/package/grunt-contrib-clean
 * Example:
 *
 build: ["path/to/dir/one", "path/to/dir/two"],
 release: ["path/to/another/dir/one", "path/to/another/dir/two"]
 *
 */

module.exports = {

	dist: [
		'<%= pkg._zipfoldername %>/**'
	],

	resourcecss: [
		'<%= pkg._resourcepath %>/*.min.css'
	]
};