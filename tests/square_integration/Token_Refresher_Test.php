<?php

namespace TEC\Tickets\Commerce\Gateways\Square;

use Codeception\TestCase\WPTestCase;
use WP_Error;

class Token_Refresher_Test extends WPTestCase {
	/**
	 * The answers WhoDat will give, one per call, each [ code, body ] or a WP_Error.
	 *
	 * @var array
	 */
	protected array $whodat_responses = [];

	/**
	 * The query args WhoDat was called with, one entry per call.
	 *
	 * @var array
	 */
	protected array $whodat_calls = [];

	/**
	 * What the token status endpoint reports about the stored access token, decoded or as a raw body.
	 *
	 * @var mixed
	 */
	protected $token_status = [ 'scopes' => [ 'PAYMENTS_WRITE' ] ];

	/**
	 * The instance the shared filter callback should route to.
	 *
	 * WordPress restores $wp_filter after the @\after methods run, which puts an instance callback back
	 * once the instance is gone. A static callback keeps a single, stable registration instead.
	 *
	 * @var ?self
	 */
	protected static $current;

	/**
	 * @before
	 */
	public function intercept_requests(): void {
		$this->whodat_responses = [];
		$this->whodat_calls     = [];
		$this->token_status     = [ 'scopes' => [ 'PAYMENTS_WRITE' ] ];

		self::$current = $this;
		add_filter( 'pre_http_request', [ __CLASS__, 'route_request' ], 10, 3 );

		// The suite bootstrap turns any error or warning log into an exception; these tests assert on
		// failure paths, which log on purpose. Lifted by name so the rest of $wp_filter stays untouched.
		remove_action( 'tribe_log', $GLOBALS['tec_tickets_square_log_guard'], 10 );
	}

	/**
	 * @after
	 */
	public function restore_state(): void {
		remove_filter( 'pre_http_request', [ __CLASS__, 'route_request' ], 10 );
		self::$current = null;

		add_action( 'tribe_log', $GLOBALS['tec_tickets_square_log_guard'], 10, 3 );

		$merchant = tribe( Merchant::class );
		$merchant->delete_refresh_status();
		$merchant->save_signup_data( tec_tickets_tests_get_fake_merchant_data() );
		delete_option( $this->get_lock_key() );
	}

	/**
	 * Answers WhoDat calls from the queued responses.
	 *
	 * @param mixed  $pre  The short-circuit value.
	 * @param array  $args The request arguments.
	 * @param string $url  The request URL.
	 *
	 * @return mixed
	 */
	public static function route_request( $pre, $args, $url ) {
		return self::$current ? self::$current->answer_whodat( $pre, $args, $url ) : $pre;
	}

	/**
	 * Answers WhoDat calls from the queued responses.
	 *
	 * @param mixed  $pre  The short-circuit value.
	 * @param array  $args The request arguments.
	 * @param string $url  The request URL.
	 *
	 * @return mixed
	 */
	public function answer_whodat( $pre, $args, $url ) {
		if ( false === strpos( $url, 'whodat' ) ) {
			return $pre;
		}

		if ( false !== strpos( $url, 'oauth/token/status' ) ) {
			return $this->build_response( 200, $this->token_status );
		}

		$parsed = [];
		parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $parsed );

		$body = $args['body'] ?? [];

		if ( is_string( $body ) ) {
			$parsed_body = [];
			parse_str( $body, $parsed_body );
			$body = $parsed_body;
		}

		$this->whodat_calls[] = array_merge(
			$parsed,
			is_array( $body ) ? $body : [],
			[ '_method' => $args['method'] ?? '' ]
		);

		$response = array_shift( $this->whodat_responses );

		if ( $response instanceof WP_Error ) {
			return $response;
		}

		[ $code, $payload ] = $response;

		return $this->build_response( $code, $payload );
	}

	/**
	 * Builds an HTTP response array.
	 *
	 * @param int   $code The response code.
	 * @param mixed $body The response body; a string is sent through as-is.
	 *
	 * @return array
	 */
	protected function build_response( int $code, $body ): array {
		return [
			'headers'  => [],
			'body'     => is_string( $body ) ? $body : wp_json_encode( $body ),
			'response' => [
				'code'    => $code,
				'message' => 200 === $code ? 'OK' : 'Error',
			],
			'cookies'  => [],
			'filename' => null,
		];
	}

	protected function get_lock_key(): string {
		return 'tec_tickets_commerce_square_token_refresh_lock_' . tribe( Merchant::class )->get_mode();
	}

	protected function refresher(): Token_Refresher {
		return tribe( Token_Refresher::class );
	}

	/**
	 * Puts the connection into a known state.
	 *
	 * @param array    $overrides Signup data overrides.
	 * @param string[] $remove    Signup data keys to drop.
	 */
	protected function connect( array $overrides = [], array $remove = [] ): Merchant {
		$merchant = tribe( Merchant::class );
		$merchant->delete_refresh_status();
		delete_option( $this->get_lock_key() );

		$data = array_merge( tec_tickets_tests_get_fake_merchant_data(), $overrides );

		foreach ( $remove as $key ) {
			unset( $data[ $key ] );
		}

		$merchant->save_signup_data( $data );

		return $merchant;
	}

	protected function fresh_credentials(): array {
		return [
			'access_token'  => 'refreshed-access-token',
			'refresh_token' => 'refreshed-refresh-token',
			'expires_at'    => gmdate( 'Y-m-d\TH:i:s\Z', time() + 30 * DAY_IN_SECONDS ),
		];
	}

	/**
	 * A successful refresh answer.
	 *
	 * @return array
	 */
	protected function refresh_ok(): array {
		return [ 200, $this->fresh_credentials() ];
	}

	/**
	 * The HTML error page the endpoint really answers a rejected refresh token with.
	 *
	 * @param int $code The response code.
	 *
	 * @return array
	 */
	protected function refresh_error_page( int $code ): array {
		return [ $code, '<!DOCTYPE html><html><head><title>Server Error</title></head><body></body></html>' ];
	}

	protected function expiring_soon(): array {
		return [ 'expires_at' => gmdate( 'Y-m-d\TH:i:s\Z', time() + HOUR_IN_SECONDS ) ];
	}

	/**
	 * @test
	 */
	public function it_should_not_refresh_a_token_that_is_not_due(): void {
		$merchant = $this->connect( [ 'expires_at' => gmdate( 'Y-m-d\TH:i:s\Z', time() + 25 * DAY_IN_SECONDS ) ] );

		$this->assertFalse( $this->refresher()->should_refresh() );
		$this->assertFalse( $this->refresher()->refresh_if_needed() );
		$this->assertCount( 0, $this->whodat_calls );
		$this->assertSame( tec_tickets_tests_get_fake_merchant_data()['access_token'], $merchant->get_access_token() );
	}

	/**
	 * @test
	 */
	public function it_should_refresh_a_token_that_is_about_to_expire(): void {
		$merchant                = $this->connect( $this->expiring_soon() );
		$credentials              = $this->fresh_credentials();
		$this->whodat_responses[] = [ 200, $credentials ];

		$this->assertTrue( $this->refresher()->should_refresh() );
		$this->assertTrue( $this->refresher()->refresh_if_needed() );

		$this->assertCount( 1, $this->whodat_calls );
		$this->assertSame( 'refresh_token', $this->whodat_calls[0]['grant_type'] );
		$this->assertSame( tribe( Merchant::class )->get_mode(), $this->whodat_calls[0]['mode'] );
		$this->assertSame( tec_tickets_tests_get_fake_merchant_data()['refresh_token'], $this->whodat_calls[0]['refresh_token'] );

		$this->assertSame( $credentials['access_token'], $merchant->get_access_token() );
		$this->assertSame( $credentials['refresh_token'], $merchant->get_refresh_token() );
		$this->assertSame( $credentials['expires_at'], get_option( $merchant->get_signup_data_key() )['expires_at'] );
		$this->assertFalse( $merchant->is_token_invalid() );
		$this->assertSame( 0, (int) $merchant->get_refresh_status()['failures'] );
	}

	/**
	 * @test
	 */
	public function it_should_refresh_a_token_that_has_already_expired(): void {
		$merchant                 = $this->connect( [ 'expires_at' => gmdate( 'Y-m-d\TH:i:s\Z', time() - DAY_IN_SECONDS ) ] );
		$this->whodat_responses[] = $this->refresh_ok();

		$this->assertTrue( $this->refresher()->refresh_if_needed() );
		$this->assertSame( 'refreshed-access-token', $merchant->get_access_token() );
	}

	/**
	 * @test
	 */
	public function it_should_refresh_a_connection_with_no_recorded_expiration(): void {
		$merchant                 = $this->connect( [], [ 'expires_at' ] );
		$this->whodat_responses[] = $this->refresh_ok();

		$this->assertTrue( $this->refresher()->refresh_if_needed() );
		$this->assertCount( 1, $this->whodat_calls );
		$this->assertNotNull( $merchant->get_token_expiration() );
	}

	/**
	 * @test
	 */
	public function it_should_throttle_a_connection_with_no_recorded_expiration(): void {
		$this->connect( [], [ 'expires_at' ] );
		// A 200 that carries no credentials is treated as a blip.
		$this->whodat_responses[] = [ 200, [] ];

		$this->assertFalse( $this->refresher()->refresh_if_needed() );
		$this->assertCount( 1, $this->whodat_calls );

		$this->assertFalse( $this->refresher()->refresh_if_needed() );
		$this->assertCount( 1, $this->whodat_calls );
	}

	/**
	 * @test
	 */
	public function it_should_not_refresh_without_a_refresh_token(): void {
		$this->connect( array_merge( $this->expiring_soon(), [ 'refresh_token' => '' ] ) );

		$this->assertFalse( $this->refresher()->should_refresh() );
		$this->assertFalse( $this->refresher()->refresh_now() );
		$this->assertCount( 0, $this->whodat_calls );
	}

	/**
	 * The endpoint answers a rejected refresh token with an HTML 500, so the only way to tell a rejection
	 * from an outage is to ask Square whether it still accepts the stored token.
	 *
	 * @test
	 */
	public function it_should_mark_the_connection_unavailable_when_the_token_is_also_rejected(): void {
		$merchant                 = $this->connect( $this->expiring_soon() );
		$this->whodat_responses[] = $this->refresh_error_page( 500 );
		$this->token_status       = [
			'type'    => 'UNAUTHORIZED',
			'message' => 'This request could not be authorized.',
		];

		$this->assertFalse( $this->refresher()->refresh_if_needed() );

		$this->assertTrue( $merchant->is_token_invalid() );
		$this->assertSame( '500', $merchant->get_refresh_status()['error'] );
		$this->assertFalse( $merchant->is_connected() );

		// The credentials stay put so support can still see what is stored.
		$this->assertSame( tec_tickets_tests_get_fake_merchant_data()['access_token'], $merchant->get_access_token() );
		$this->assertSame( tec_tickets_tests_get_fake_merchant_data()['refresh_token'], $merchant->get_refresh_token() );
	}

	/**
	 * @test
	 */
	public function it_should_mark_the_connection_unavailable_when_the_request_is_turned_down(): void {
		$merchant                 = $this->connect( $this->expiring_soon() );
		$this->whodat_responses[] = $this->refresh_error_page( 401 );

		$this->assertFalse( $this->refresher()->refresh_if_needed() );

		$this->assertTrue( $merchant->is_token_invalid() );
		$this->assertFalse( $merchant->is_connected() );
	}

	/**
	 * A rate limit says nothing about the credentials, and the endpoint answers a genuine rejection with
	 * a 500 anyway. Disconnecting on a 429 would take out every site behind the same limiter at once.
	 *
	 * @test
	 */
	public function it_should_not_mark_the_connection_unavailable_on_a_rate_limit(): void {
		$merchant                 = $this->connect( $this->expiring_soon() );
		$this->whodat_responses[] = $this->refresh_error_page( 429 );

		$this->assertFalse( $this->refresher()->refresh_if_needed() );

		$this->assertFalse( $merchant->is_token_invalid() );
		$this->assertTrue( $merchant->is_connected() );
		$this->assertSame( 1, $merchant->get_refresh_failure_count() );
	}

	/**
	 * The status endpoint reports on the access token, which has expired by definition on this path, so
	 * it says UNAUTHORIZED whether the grant is gone or WhoDat is merely down. Only failures that keep
	 * repeating over more than a day settle it.
	 *
	 * @test
	 */
	public function it_should_not_disconnect_an_expired_token_on_a_single_outage(): void {
		$merchant                 = $this->connect( [ 'expires_at' => gmdate( 'c', time() - HOUR_IN_SECONDS ) ] );
		$this->whodat_responses[] = $this->refresh_error_page( 500 );
		$this->token_status       = [ 'type' => 'UNAUTHORIZED' ];

		$this->assertFalse( $this->refresher()->refresh_if_needed() );

		$this->assertFalse( $merchant->is_token_invalid() );
		$this->assertTrue( $merchant->is_connected() );
	}

	/**
	 * A JSON scalar decodes to a scalar, and the status reader is typed to an array. Reading one has to
	 * come back as unknown rather than throw or count as a refusal.
	 *
	 * @test
	 */
	public function it_should_read_a_scalar_status_body_as_unknown(): void {
		$merchant                 = $this->connect( [ 'expires_at' => gmdate( 'c', time() - HOUR_IN_SECONDS ) ] );
		$this->whodat_responses[] = $this->refresh_error_page( 500 );
		$this->token_status       = '"UNAUTHORIZED"';

		$this->assertNull( tribe( WhoDat::class )->is_token_accepted( true ) );

		$this->assertFalse( $this->refresher()->refresh_if_needed() );

		$this->assertFalse( $merchant->is_token_invalid() );
		$this->assertTrue( $merchant->is_connected() );
	}

	/**
	 * @test
	 */
	public function it_should_disconnect_an_expired_token_once_the_failures_persist(): void {
		$merchant                 = $this->connect( [ 'expires_at' => gmdate( 'c', time() - HOUR_IN_SECONDS ) ] );
		$this->whodat_responses[] = $this->refresh_error_page( 500 );
		$this->token_status       = [ 'type' => 'UNAUTHORIZED' ];

		$merchant->update_refresh_status(
			[
				'failures'         => 5,
				'first_failure_at' => gmdate( 'Y-m-d H:i:s', time() - 2 * DAY_IN_SECONDS ),
			]
		);

		$this->assertFalse( $this->refresher()->refresh_if_needed() );

		$this->assertTrue( $merchant->is_token_invalid() );
		$this->assertFalse( $merchant->is_connected() );
	}

	/**
	 * A failing refresh while Square still honours the token is a blip, not a disconnection.
	 *
	 * @test
	 */
	public function it_should_not_mark_the_connection_unavailable_while_the_token_still_works(): void {
		$merchant                 = $this->connect( $this->expiring_soon() );
		$this->whodat_responses[] = $this->refresh_error_page( 500 );

		$this->assertFalse( $this->refresher()->refresh_if_needed() );

		$this->assertFalse( $merchant->is_token_invalid() );
		$this->assertTrue( $merchant->is_connected() );
		$this->assertSame( 1, (int) $merchant->get_refresh_status()['failures'] );
	}

	/**
	 * @test
	 */
	public function it_should_not_mark_the_connection_unavailable_on_a_transport_failure(): void {
		$merchant                 = $this->connect( $this->expiring_soon() );
		$this->whodat_responses[] = new WP_Error( 'http_request_failed', 'Connection timed out' );

		$this->assertFalse( $this->refresher()->refresh_if_needed() );

		$this->assertFalse( $merchant->is_token_invalid() );
		$this->assertTrue( $merchant->is_connected() );
		$this->assertSame( 1, (int) $merchant->get_refresh_status()['failures'] );
		$this->assertNotEmpty( $merchant->get_refresh_status()['last_attempt_at'] );
	}

	/**
	 * A transport failure says nothing about the credentials, even when the token is also unreachable.
	 *
	 * @test
	 */
	public function it_should_not_mark_the_connection_unavailable_when_nothing_can_be_reached(): void {
		$merchant                 = $this->connect( $this->expiring_soon() );
		$this->whodat_responses[] = new WP_Error( 'http_request_failed', 'Connection timed out' );
		$this->token_status       = [ 'type' => 'UNAUTHORIZED' ];

		$this->assertFalse( $this->refresher()->refresh_if_needed() );
		$this->assertFalse( $merchant->is_token_invalid() );
		$this->assertTrue( $merchant->is_connected() );
	}

	/**
	 * The endpoint answers 405 to a GET, so this is not a detail of how the request is built.
	 *
	 * @test
	 */
	public function it_should_send_the_refresh_as_a_post(): void {
		$this->connect( $this->expiring_soon() );
		$this->whodat_responses[] = $this->refresh_ok();

		$this->refresher()->refresh_if_needed();

		$this->assertSame( 'POST', $this->whodat_calls[0]['_method'] );
	}

	/**
	 * The timestamps are written by the refresher and read back with strtotime(), which WordPress pins
	 * to UTC. Building them in the site timezone instead skews every interval by the site's offset:
	 * west of UTC the backoff never engages, east of it a single blip suppresses refreshes for a day.
	 *
	 * @test
	 * @dataProvider timezone_provider
	 *
	 * @param string $timezone The site timezone to run under.
	 */
	public function it_should_back_off_whatever_the_site_timezone( string $timezone ): void {
		$original_timezone = get_option( 'timezone_string' );

		try {
			update_option( 'timezone_string', $timezone );

			$merchant                 = $this->connect( $this->expiring_soon() );
			$this->whodat_responses[] = $this->refresh_error_page( 500 );

			$this->refresher()->refresh_if_needed();
			$this->assertCount( 1, $this->whodat_calls );

			// The invariant the intervals rest on: what was written reads back as now, not as now +/- offset.
			$this->assertEqualsWithDelta( time(), strtotime( $merchant->get_refresh_status()['last_attempt_at'] ), 5 );

			// Well inside the first backoff step, so nothing may go out.
			$this->refresher()->refresh_if_needed();
			$this->assertCount( 1, $this->whodat_calls );

			$merchant->update_refresh_status( [ 'last_attempt_at' => gmdate( 'Y-m-d H:i:s', time() - 2 * HOUR_IN_SECONDS ) ] );
			$this->whodat_responses[] = $this->refresh_ok();

			$this->assertTrue( $this->refresher()->refresh_if_needed() );
			$this->assertCount( 2, $this->whodat_calls );
		} finally {
			update_option( 'timezone_string', $original_timezone );
		}
	}

	/**
	 * @return array<string, string[]>
	 */
	public function timezone_provider(): array {
		return [
			'UTC'               => [ 'UTC' ],
			'west of UTC'       => [ 'America/Los_Angeles' ],
			'east of UTC'       => [ 'Pacific/Auckland' ],
			'half hour offset'  => [ 'Asia/Kolkata' ],
		];
	}

	/**
	 * @test
	 */
	public function it_should_count_every_consecutive_failure(): void {
		$merchant = $this->connect( $this->expiring_soon() );

		foreach ( range( 1, 3 ) as $attempt ) {
			$this->whodat_responses[] = $this->refresh_error_page( 500 );
			$merchant->update_refresh_status( [ 'last_attempt_at' => gmdate( 'Y-m-d H:i:s', time() - 2 * DAY_IN_SECONDS ) ] );

			$this->refresher()->refresh_if_needed();

			$this->assertSame( $attempt, $merchant->get_refresh_failure_count() );
		}

		// The window the persistence check measures starts at the first failure, not the latest.
		$this->assertNotEmpty( $merchant->get_refresh_status()['first_failure_at'] );
	}

	/**
	 * A response with no usable expiration leaves the connection in the "unknown expiration" state. If a
	 * success also wiped the attempt timestamp, that state would refresh once per Square API call.
	 *
	 * @test
	 */
	public function it_should_not_refresh_repeatedly_when_the_response_carries_no_expiration(): void {
		$this->connect( $this->expiring_soon() );

		$credentials = $this->fresh_credentials();
		unset( $credentials['expires_at'] );
		$this->whodat_responses[] = [ 200, $credentials ];

		$this->assertTrue( $this->refresher()->refresh_if_needed() );
		$this->assertCount( 1, $this->whodat_calls );

		$this->assertFalse( $this->refresher()->should_refresh() );
		$this->refresher()->refresh_if_needed();
		$this->assertCount( 1, $this->whodat_calls );
	}

	/**
	 * @test
	 */
	public function it_should_back_off_after_a_transient_failure(): void {
		$merchant                 = $this->connect( $this->expiring_soon() );
		$this->whodat_responses[] = $this->refresh_error_page( 500 );

		$this->refresher()->refresh_if_needed();
		$this->assertCount( 1, $this->whodat_calls );

		$this->refresher()->refresh_if_needed();
		$this->assertCount( 1, $this->whodat_calls );

		// Move the attempt far enough into the past for the backoff to lapse.
		$merchant->update_refresh_status( [ 'last_attempt_at' => gmdate( 'Y-m-d H:i:s', time() - 2 * DAY_IN_SECONDS ) ] );
		$this->whodat_responses[] = $this->refresh_ok();

		$this->assertTrue( $this->refresher()->refresh_if_needed() );
		$this->assertCount( 2, $this->whodat_calls );
	}

	/**
	 * @test
	 */
	public function it_should_not_retry_a_rejected_connection_straight_away(): void {
		$merchant = $this->connect( $this->expiring_soon() );
		$merchant->update_refresh_status(
			[
				'invalid_at'      => gmdate( 'Y-m-d H:i:s' ),
				'last_attempt_at' => gmdate( 'Y-m-d H:i:s' ),
			]
		);

		$this->assertFalse( $this->refresher()->should_refresh() );
		$this->assertFalse( $this->refresher()->refresh_if_needed() );
		$this->assertFalse( $this->refresher()->refresh_now() );
		$this->assertCount( 0, $this->whodat_calls );
	}

	/**
	 * Being marked unavailable has to be reversible without database surgery: the verdict can be wrong,
	 * and a merchant can re-authorize on Square's side without ever touching WordPress.
	 *
	 * @test
	 */
	public function it_should_recheck_a_rejected_connection_and_recover(): void {
		$merchant = $this->connect( $this->expiring_soon() );
		$merchant->update_refresh_status(
			[
				'invalid_at'      => gmdate( 'Y-m-d H:i:s', time() - 2 * DAY_IN_SECONDS ),
				'last_attempt_at' => gmdate( 'Y-m-d H:i:s', time() - 2 * DAY_IN_SECONDS ),
				'failures'        => 6,
			]
		);

		$this->assertFalse( $merchant->is_connected() );

		$this->whodat_responses[] = $this->refresh_ok();

		$this->assertTrue( $this->refresher()->refresh_if_needed() );
		$this->assertCount( 1, $this->whodat_calls );

		$this->assertFalse( $merchant->is_token_invalid() );
		$this->assertTrue( $merchant->is_connected() );
		$this->assertSame( 0, $merchant->get_refresh_failure_count() );
	}

	/**
	 * @test
	 */
	public function it_should_not_refresh_while_another_process_holds_the_lock(): void {
		$this->connect( $this->expiring_soon() );
		add_option( $this->get_lock_key(), (string) time(), '', 'no' );
		$this->whodat_responses[] = $this->refresh_ok();

		$this->assertFalse( $this->refresher()->refresh_if_needed() );
		$this->assertCount( 0, $this->whodat_calls );
	}

	/**
	 * Under load every concurrent request finds the same expired token. If losing the lock race were an
	 * immediate failure, one shopper would get the refresh and the rest a failed checkout.
	 *
	 * @test
	 */
	public function it_should_wait_for_the_lock_holder_before_failing_a_forced_refresh(): void {
		$merchant = $this->connect( $this->expiring_soon() );
		$waiter   = new class( $merchant, tribe( WhoDat::class ) ) extends Token_Refresher {
			public function wait( string $previous_token ): bool {
				return $this->wait_for_lock_holder( $previous_token );
			}
		};

		$option_key = 'tec_tickets_commerce_square_signup_data_' . $merchant->get_mode();

		// Stands in for the process holding the lock committing its refreshed credentials.
		add_filter(
			"option_{$option_key}",
			static function ( $data ) {
				return array_merge( (array) $data, [ 'access_token' => 'renewed-by-the-winner' ] );
			}
		);

		$this->assertTrue( $waiter->wait( tec_tickets_tests_get_fake_merchant_data()['access_token'] ) );
		$this->assertCount( 0, $this->whodat_calls );
	}

	/**
	 * @test
	 */
	public function it_should_give_up_waiting_on_a_lock_holder_that_never_finishes(): void {
		$merchant = $this->connect( $this->expiring_soon() );
		$waiter   = new class( $merchant, tribe( WhoDat::class ) ) extends Token_Refresher {
			public function wait( string $previous_token ): bool {
				return $this->wait_for_lock_holder( $previous_token );
			}
		};

		$this->assertFalse( $waiter->wait( $merchant->get_access_token() ) );
	}

	/**
	 * A process that died mid-refresh must not hold refreshes off forever.
	 *
	 * @test
	 */
	public function it_should_take_over_an_abandoned_lock(): void {
		$this->connect( $this->expiring_soon() );
		add_option( $this->get_lock_key(), ( time() - 300 ) . ':abandoned', '', 'no' );
		$this->whodat_responses[] = $this->refresh_ok();

		$this->assertTrue( $this->refresher()->refresh_if_needed() );
		$this->assertCount( 1, $this->whodat_calls );
	}

	/**
	 * A process whose lock was taken over must not delete the row the new holder is working under, or a
	 * third process walks straight in while the second is still mid-refresh.
	 *
	 * @test
	 */
	public function it_should_not_release_a_lock_it_no_longer_holds(): void {
		$this->connect( $this->expiring_soon() );

		$refresher = new class( tribe( Merchant::class ), tribe( WhoDat::class ) ) extends Token_Refresher {
			public function take_lock(): bool {
				return $this->create_lock();
			}

			public function give_lock_back(): void {
				$this->release_lock();
			}
		};

		$this->assertTrue( $refresher->take_lock() );

		// Whoever took over stamped its own value over this process's.
		$takeover_time = time();
		update_option( $this->get_lock_key(), $takeover_time . ':somebody-else' );

		$refresher->give_lock_back();

		$this->assertSame( $takeover_time . ':somebody-else', get_option( $this->get_lock_key() ) );
	}

	/**
	 * @test
	 */
	public function it_should_release_the_lock_whatever_the_outcome(): void {
		$this->connect( $this->expiring_soon() );
		$this->whodat_responses[] = $this->refresh_ok();

		$this->assertTrue( $this->refresher()->refresh_if_needed() );
		$this->assertCount( 1, $this->whodat_calls );
		$this->assertFalse( get_option( $this->get_lock_key() ) );

		$this->connect( $this->expiring_soon() );
		$this->whodat_responses[] = $this->refresh_error_page( 422 );

		$this->refresher()->refresh_if_needed();
		$this->assertFalse( get_option( $this->get_lock_key() ) );
	}

	/**
	 * @test
	 */
	public function it_should_refresh_on_demand_regardless_of_the_expiration(): void {
		$merchant                 = $this->connect( [ 'expires_at' => gmdate( 'Y-m-d\TH:i:s\Z', time() + 25 * DAY_IN_SECONDS ) ] );
		$this->whodat_responses[] = $this->refresh_ok();

		$this->assertFalse( $this->refresher()->should_refresh() );
		$this->assertTrue( $this->refresher()->refresh_now( 'square_unauthorized' ) );
		$this->assertSame( 'refreshed-access-token', $merchant->get_access_token() );
	}
}
