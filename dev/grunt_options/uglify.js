/**
 *
 * Module: grunt-contrib-uglify
 * Documentation: https://npmjs.org/package/grunt-contrib-uglify
 * Example:
 *
 	my_target: {
      files: {
        'dest/output.min.js': ['src/input1.js', 'src/input2.js']
      }
    }
 *
 */

module.exports = {

	resourcescripts: {

		files: {
			'<%= pkg._resourcepath %>/js/rsvp.min.js' : '<%= pkg._resourcepath %>/js/rsvp.processed.js',
			'<%= pkg._resourcepath %>/js/tickets.min.js' : '<%= pkg._resourcepath %>/js/tickets.processed.js',
			'<%= pkg._resourcepath %>/js/tickets-attendees.min.js' : '<%= pkg._resourcepath %>/js/tickets-attendees.processed.js'
		}
	}

};
