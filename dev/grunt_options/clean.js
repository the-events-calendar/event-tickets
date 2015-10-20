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

	resourcescripts: [
		'<%= pkg._resourcepath %>/js/rsvp.processed.js',
		'<%= pkg._resourcepath %>/js/tickets.processed.js',
		'<%= pkg._resourcepath %>/js/tickets-attendees.processed.js'
	],

	resourcecss: [
		'<%= pkg._resourcepath %>/*.min.css'
	]
};
