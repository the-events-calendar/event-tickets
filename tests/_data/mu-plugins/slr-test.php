<?php
/**
 * Plugin Name: Events Assigned Seating Test
 */

use TEC\Common\StellarWP\Uplink\Auth\Token\Contracts\Token_Manager;
use TEC\Tickets\Seating\Service\Layouts;
use TEC\Tickets\Seating\Service\Maps;
use TEC\Tickets\Seating\Service\OAuth_Token;
use TEC\Tickets\Seating\Service\Seat_Types;
use TEC\Tickets\Seating\Service\Service;
use function TEC\Common\StellarWP\Uplink\get_resource;

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	// Run `wp slr:seed:test` to seed the test data.
	\WP_CLI::add_command(
		'slr:connect',
		static function ( array $args ) {
			\WP_CLI::line( 'Adding access token ...' );
			$connected = slr_test_connect_to_service();
			if ( $connected ) {
				\WP_CLI::success( 'Access token set.' );
			} else {
				\WP_CLI::error( 'Access token could not be set, check the __TEST__ Setup page.' );
			}
		},
		[
			'shortdesc' => 'Connects the site to the SLR service.'
		]
	);
	\WP_CLI::add_command(
		'slr:reset',
		static function ( array $args ) {
			\WP_CLI::line( 'Removing the access token ...' );
			$res = get_resource( 'tec-seating' );
			tribe( Token_Manager::class )->delete( $res->get_slug() );
			\WP_CLI::line( 'Resetting tables ...' );
			tribe( \TEC\Tickets\Seating\Tables\Maps::class )->drop();
			tribe( \TEC\Tickets\Seating\Tables\Maps::class )->update();
			tribe( \TEC\Tickets\Seating\Tables\Layouts::class )->drop();
			tribe( \TEC\Tickets\Seating\Tables\Layouts::class )->update();
			tribe( \TEC\Tickets\Seating\Tables\Seat_Types::class )->drop();
			tribe( \TEC\Tickets\Seating\Tables\Seat_Types::class )->update();
			tribe( \TEC\Tickets\Seating\Tables\Sessions::class )->drop();
			tribe( \TEC\Tickets\Seating\Tables\Sessions::class )->update();
			\WP_CLI::line( 'Cleaning transients ....' );
			delete_transient( Maps::update_transient_name() );
			delete_transient( Layouts::update_transient_name() );
			delete_transient( Seat_Types::update_transient_name() );
			tribe(Maps::class)->invalidate_cache();
			tribe(Layouts::class)->invalidate_cache();
			\WP_CLI::success( 'Done' );
		},
		[
			'shortdesc' => 'Connects the site to the SLR service.'
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
			tribe(Maps::class)->invalidate_cache();
			tribe(Layouts::class)->invalidate_cache();
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
			tribe(Maps::class)->invalidate_cache();
			tribe(Layouts::class)->invalidate_cache();
			\WP_CLI::success( 'Transients cleaned.' );
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

function slr_test_filter_service_auth_url() {
	return get_option( 'tec_tickets_seating_service_auth_url' )
		?: 'http://host.docker.internal:33445'; // Likely correct: it will work if you can access the service from your browser.
}

add_filter( 'tec_tickets_seating_service_auth_url', 'slr_test_filter_service_auth_url' );

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

add_filter( 'pre_update_option_tec_tickets_seating_service_auth_url', static function ( $value ) {
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
	$auth_url     = get_option( 'tec_tickets_seating_service_auth_url' ) ?: 'http://host.docker.internal:33445';
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
			<li>Auth Service URL: <code>http://host.docker.internal:33445</code></li>
		</ul>
		</p>

		<p><strong>To test using the Staging server use:</strong>
		<ul>
			<li>Service Backend URL: <code>https://seating-staging.theeventscalendar.com</code></li>
			<li>Service Frontend URL: <code>https://seating-staging.theeventscalendar.com</code></li>
			<li>Auth Service URL: <code>https://seating-auth-staging.theeventscalendar.com</code></li>
		</ul>
		</p>

		<p><strong>To test using the Development server use:</strong>
		<ul>
			<li>Service Backend URL: <code>https://seating-dev.theeventscalendar.com</code></li>
			<li>Service Frontend URL: <code>https://seating-dev.theeventscalendar.com</code></li>
			<li>Auth Service URL: <code>https://seating-auth-dev.theeventscalendar.com</code></li>
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

				<tr valign="top">
					<th scope="row">Auth service URL</th>
					<td><input type="text" name="tec_tickets_seating_service_auth_url"
							   class="regular-text wide"
							   value="<?php echo esc_attr( $auth_url ); ?>"/></td>
				</tr>

			</table>

			<?php submit_button( 'Save & Connect' ); ?>

		</form>
	</div>
	<?php
}

function slr_test_check_connection(): bool {
	$current_token = ( new class {
		use OAuth_Token;

		public function open_get_oauth_token(): ?string {
			return $this->get_oauth_token();
		}
	} )->open_get_oauth_token();

	$service_url = apply_filters( 'tec_tickets_seating_service_base_url',
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
	if ( slr_test_check_connection() ) {
		return true;
	}

	$auth_url     = apply_filters( 'tec_tickets_seating_service_auth_url',
		get_option( 'tec_tickets_seating_service_auth_url' ) );
	$auth_url     = untrailingslashit( $auth_url );
	$access_token = wp_generate_password( 36, false, false );

	$https_home_url = home_url( '', 'https' );

	if ( 'https' !== parse_url( $https_home_url, PHP_URL_SCHEME ) ) {
		$https_home_url = str_replace( 'http', 'https', $https_home_url );
	}

	$response = wp_remote_post(
		$auth_url . '/tokens/new',
		[
			'body'    => wp_json_encode(
				[
					'token'      => $access_token,
					'domain'     => $https_home_url,
					'user_id'    => time(),
					'expiration' => time() + YEAR_IN_SECONDS,
				]
			),
			'timeout' => 30
		]
	);

	if ( is_wp_error( $response ) ) {
		update_option( 'tec_tickets_seating_connection', [
			'status'  => 'error',
			'message' => $response->get_error_message()
		] );

		return false;
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

		return false;
	}

	update_option( 'tec_tickets_seating_connection', [
		'status'  => 'success',
		'message' => 'Connected to the service.'
	] );

	( new class {
		use OAuth_Token;

		public function open_set_oauth_token( string $token ): void {
			$this->set_oauth_token( $token );
		}
	} )->open_set_oauth_token( $access_token );

	return true;
}
