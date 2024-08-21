<?php
/**
 * Plugin Name: Events Assigned Seating Test
 */

use TEC\Tickets\Seating\Service\Layouts;
use TEC\Tickets\Seating\Service\Maps;
use TEC\Tickets\Seating\Service\Seat_Types;
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
			delete_transient( Seat_Types::update_transient_name() );
			\WP_CLI::success( 'Transients cleaned.' );
		}
	);
	\WP_CLI::add_command(
		'slr:regen-tables',
		function () {
			\WP_CLI::line( 'Regenerating tables ...' );
			tribe( \TEC\Tickets\Seating\Tables\Maps::class )->drop();
			tribe( \TEC\Tickets\Seating\Tables\Maps::class )->update();
			tribe( \TEC\Tickets\Seating\Tables\Layouts::class )->drop();
			tribe( \TEC\Tickets\Seating\Tables\Layouts::class )->update();
			tribe( \TEC\Tickets\Seating\Tables\Seat_Types::class )->drop();
			tribe( \TEC\Tickets\Seating\Tables\Seat_Types::class )->update();
			tribe( \TEC\Tickets\Seating\Tables\Sessions::class )->drop();
			tribe( \TEC\Tickets\Seating\Tables\Sessions::class )->update();
			\WP_CLI::success( 'Tables regenerated.' );
			delete_transient( Maps::update_transient_name() );
			delete_transient( Layouts::update_transient_name() );
			delete_transient( Seat_Types::update_transient_name() );
			\WP_CLI::success( 'Transients cleaned.' );
		}
	);
	
	\WP_CLI::add_command(
		'slr:auth-token',
		function() {
			\WP_CLI::line( 'Getting the auth token ...' );
			$token = tribe_get_option( Service::get_oauth_token_option_name(), null );
			\WP_CLI::success( 'Bearer ' . $token );
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
	// return 'http://localhost:3000'; // The site runs on localhost.
	return get_option( 'tec_tickets_seating_service_base_url' )
		?: 'http://host.docker.internal:3000'; // The site runs in a Docker container, e.g. on Lando.
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
	return get_option( 'tec_tickets_seating_service_frontend_url' )
		?: 'http://localhost:3000'; // Likely correct: it will work if you can access the service from your browser.
}

add_filter( 'tec_tickets_seating_service_frontend_url', 'slr_test_filter_service_frontend_url' );

//  __TEST__ Setup page.
add_action( 'admin_menu', static function () {
	add_submenu_page(
		'tec-tickets',
		__( '__TEST__ Setup', 'event-tickets' ),
		__( '__TEST__ Setup', 'event-tickets' ),
		'manage_options',
		'tec-tickets-seating-test-setup',
		'slr_test_render_test_setup_page'
	);

	add_action( 'admin_init', 'slr_test_register_test_setup_settings' );
}, 1000 );

// Before updating the option, check the connection.
// This will happen every time the user save the settings.
// Use the filter as an action.
add_filter( 'pre_update_option_tec_tickets_seating_service_base_url', static function ( $value ) {
	if ( ! has_action( 'shutdown', 'slr_test_connect_to_service' ) ) {
		add_action( 'shutdown', 'slr_test_connect_to_service', 20 );
	}

	if ( ! has_action( 'shutdown', 'slr_test_check_connection' ) ) {
		add_action( 'shutdown', 'slr_test_check_connection', 10 );
	}

	return $value;
} );

add_filter( 'pre_update_option_tec_tickets_seating_service_frontend_url', static function ( $value ) {
	if ( ! has_action( 'shutdown', 'slr_test_connect_to_service' ) ) {
		add_action( 'shutdown', 'slr_test_connect_to_service', 20 );
	}

	if ( ! has_action( 'shutdown', 'slr_test_check_connection' ) ) {
		add_action( 'shutdown', 'slr_test_check_connection', 10 );
	}

	return $value;
} );

function slr_test_register_test_setup_settings() {
	register_setting( 'tec-tickets-seating-test-settings', 'tec_tickets_seating_service_base_url' );
	register_setting( 'tec-tickets-seating-test-settings', 'tec_tickets_seating_service_frontend_url' );
}

function slr_test_render_test_setup_page() {
	$service      = tribe( Service::class );
	$backend_url  = get_option( 'tec_tickets_seating_service_base_url' ) ?: $service->get_backend_url();
	$frontend_url = get_option( 'tec_tickets_seating_service_frontend_url' ) ?: $service->get_frontend_url();
	$connection   = get_option( 'tec_tickets_seating_connection', [
		'status'       => 'not_connected',
		'message'      => 'Not connected to the service.',
		'access_token' => ''
	] );

	switch ( $connection['status'] ) {
		case 'error':
			$notice_class = 'notice-error';
			break;
		case 'success':
			$notice_class = 'notice-success';
			break;
		default:
			$notice_class = 'notice-warning';
	}
	?>
	<div class="wrap">
		<div class="notice <?php echo esc_attr( $notice_class ) ?>">
			<p><?php echo esc_html( $connection['message'] ?? 'Not connected to the service.' ); ?></p>
		</div>

		<h1>SLR Test Settings</h1>

		<p>
			<strong>This page allows you to set up the URLs used to connect to the Service.</strong>
			When you change either of these values, the plugin will try to connect to the service and will show you
			the result.
		</p>

		<p><strong>For local development using Lando (or any other Docker-based solution) you should use:</strong>
		<ul>
			<li>Service Backend URL: <code>http://host.docker.internal:3000</code></li>
			<li>Service Frontend URL: <code>http://localhost:3000</code></li>
		</ul>
		</p>

		<p><strong>To test using the Staging server use:</strong>
		<ul>
			<li>Service Backend URL: <code>https://seating-staging.theeventscalendar.com</code></li>
			<li>Service Frontend URL: <code>https://seating-staging.theeventscalendar.com</code></li>
		</ul>
		</p>

		<p><strong>To test using the Development server use:</strong>
		<ul>
			<li>Service Backend URL: <code>https://seating-dev.theeventscalendar.com</code></li>
			<li>Service Frontend URL: <code>https://seating-dev.theeventscalendar.com</code></li>
		</ul>
		</p>

		<form method="post" action="options.php">
			<?php settings_fields( 'tec-tickets-seating-test-settings' ); ?>
			<?php do_settings_sections( 'tec-tickets-seating-test-settings' ); ?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row">Service Backend URL</th>
					<td><input type="text" name="tec_tickets_seating_service_base_url"
							   class="regular-text wide"
							   value="<?php echo esc_attr( $backend_url ); ?>"/></td>
				</tr>

				<tr valign="top">
					<th scope="row">Service Frontend URL</th>
					<td><input type="text" name="tec_tickets_seating_service_frontend_url"
							   class="regular-text wide"
							   value="<?php echo esc_attr( $frontend_url ); ?>"/></td>
				</tr>

			</table>

			<?php submit_button( 'Save & Connect' ); ?>

		</form>
	</div>
	<?php
}

function slr_test_check_connection(): bool {
	$current_token = tribe_get_option( Service::get_oauth_token_option_name() );
	$service_url   = apply_filters( 'tec_tickets_seating_service_base_url',
		get_option( 'tec_tickets_seating_service_base_url' ) );

	// Try and validate the token first.
	if ( $current_token ) {
		$response = wp_remote_get( $service_url . '/api/v1/check', [
			'timeout' => 30,
			'headers' => [
				'Authorization' => 'Bearer ' . $current_token
			]
		] );

		if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
			update_option( 'tec_tickets_seating_connection', [
				'status'  => 'success',
				'message' => 'Connected to the service.'
			] );

			return true;
		}
	}

	return false;
}

function slr_test_connect_to_service() {
	$service_url = apply_filters( 'tec_tickets_seating_service_base_url',
		get_option( 'tec_tickets_seating_service_base_url' ) );

	if ( slr_test_check_connection() ) {
		return;
	}

	$response = wp_remote_post(
		add_query_arg( [ 'site' => urlencode( home_url() ) ], $service_url . '/api/alpha-connect' ),
		[ 'timeout' => 30 ]
	);

	if ( is_wp_error( $response ) ) {
		update_option( 'tec_tickets_seating_connection', [
			'status'  => 'error',
			'message' => $response->get_error_message()
		] );

		return;
	}

	$code = wp_remote_retrieve_response_code( $response );
	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, false );

	if ( $code !== 200 ) {
		update_option( 'tec_tickets_seating_connection', [
			'status'  => 'error',
			'message' => sprintf(
				'Connection failed with status code %d: %s',
				$code,
				$data->error ?? ( substr( $body, 0, 100 ) . '...' )
			)
		] );

		return;
	}

	$data = json_decode( $body, false, 512, JSON_THROW_ON_ERROR );

	if ( ! isset( $data->data ) ) {
		update_option( 'tec_tickets_seating_connection', [
			'status'  => 'error',
			'message' => 'The service did not return an access token.'
		] );

		return;
	}

	update_option( 'tec_tickets_seating_connection', [
		'status'  => 'success',
		'message' => 'Connected to the service.'
	] );

	tribe_update_option( Service::get_oauth_token_option_name(), $data->data );
}

/**
 * Bypass airplane mode when connecting to the service.
 */
function slr_test_bypass_airplane_mode( $allowed, $url, $args, $host ) {
	$service_url = apply_filters( 'tec_tickets_seating_service_base_url', get_option( 'tec_tickets_seating_service_base_url' ) );
	$parsed_url  = parse_url( $service_url );
	
	if ( $parsed_url['host'] === $host ) {
		return true;
	}
	
	return $allowed;
}

add_filter( 'airplane_mode_allow_http_api_request', 'slr_test_bypass_airplane_mode', 10, 4 );
