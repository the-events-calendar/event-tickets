<?php
/**
 * Plugin Name: Events Assigned Controller Test
 */

use TEC\Tickets\Seating\Service\Layouts;
use TEC\Tickets\Seating\Service\Maps;
use TEC\Tickets\Seating\Service\Service;

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	// Run `wp slr:seed:test` to seed the test data.
	\WP_CLI::add_command(
		'slr:set-access-token',
		function ( array $args ) {
			\WP_CLI::line( 'Adding access token for the Events Assigned Controller plugin ...' );
			// This same value should be in the initial dump of the database on the service side.
			tribe_update_option( Service::get_oauth_token_option_name(), $args[0] );
			\WP_CLI::success( 'Access token set.' );
			// Call the slr-test:check-connection command to make sure the connection is working.
			\WP_CLI::runcommand( 'slr:check-connection' );
		},
		[
			'shortdesc' => 'Sets the access token for the Events Assigned Controller plugin.',
			'args'      => [
				[
					'name'        => 'access-token',
					'description' => 'The access token to use for the Events Assigned Controller plugin.',
					'type'        => 'string',
					'required'    => true,
				],
			]
		]
	);
	\WP_CLI::add_command(
		'slr:check-connection',
		function () {
			\WP_CLI::line( 'Checking connection to the Events Assigned Controller service ...' );
			$service = tribe( Service::class );
			if ( ! $service->check_connection() ) {
				\WP_CLI::error( 'The site cannot reach the service at ' . $service->get_backend_url() );
			}
			\WP_CLI::success( 'The site can reach the service at ' . $service->get_backend_url() );

			if ( ! $service->is_access_token_valid() ) {
				\WP_CLI::error( 'The the access token is not valid: did you create and set it on the service?' );
			}
			\WP_CLI::success( 'The access token is valid.' );
		}
	);
	\WP_CLI::add_command(
		'slr:clean-transients',
		function () {
			\WP_CLI::line( 'Cleaning transients ...' );
			delete_transient( Maps::update_transient_name() );
			delete_transient( Layouts::update_transient_name() );
			\WP_CLI::success( 'Transients cleaned.' );
		}
	);
	\WP_CLI::add_command(
		'slr:regen-tables',
		function () {
			\WP_CLI::line( 'Regenerating tables ...' );
			tribe(\TEC\Tickets\Seating\Tables\Maps::class)->drop();
			tribe(\TEC\Tickets\Seating\Tables\Maps::class)->update();
			tribe(\TEC\Tickets\Seating\Tables\Layouts::class)->drop();
			tribe(\TEC\Tickets\Seating\Tables\Layouts::class)->update();
			\WP_CLI::success( 'Tables regenerated.' );
		}
	);
}

/**
 * Filter the base URL of the service to point to the local instance of the service.
 * This URL is the one used from within the plugin to make requests to the service on the PHP side.
 * The one used here is from a Lando site running the plugin.
 *
 * @view https://github.com/the-events-calendar/event-tickets-seating-service/blob/master/README.md
 *
 * @return string The base URL of the service for PHP requests.
 */
function slr_test_filter_service_base_url() {
//	return 'http://localhost:3000'; // The site runs on localhost.
	return 'http://host.docker.internal:3000'; // The site runs in a Docker container, e.g. on Lando.
}

add_filter( 'tec_tickets_seating_service_base_url', 'slr_test_filter_service_base_url' );

/**
 * Filter the frontend URL of the service to point to the local instance of the service.
 * This URL is the one used from within the plugin to make requests to the service on the Browser/client side.
 * You should be able to access the same URL using a browser on your machine.
 *
 * @since TBD
 *
 * @return string The base URL of the service for frontend requests.
 */
function slr_test_filter_service_frontend_url() {
	return 'http://localhost:3000'; // Likely correct: it will work if you can access the service from your browser.
}

add_filter( 'tec_tickets_seating_service_frontend_url', 'slr_test_filter_service_frontend_url' );
