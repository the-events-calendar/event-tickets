<?php

namespace TEC\Tickets\Commerce\Gateways\Square;

use Codeception\TestCase\WPTestCase;
use WP_Error;
use WP_Hook;

class Token_Refresher_Test extends WPTestCase {
	/**
	 * The bodies WhoDat will answer with, one per call.
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
	 * The tribe_log hook as it was before the test replaced it.
	 *
	 * @var mixed
	 */
	protected $previous_log_hook;

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

		self::$current = $this;
		add_filter( 'pre_http_request', [ __CLASS__, 'route_request' ], 10, 3 );

		// The suite bootstrap turns any error or warning log into an exception; these tests assert on
		// failure paths, which log on purpose.
		global $wp_filter;
		$this->previous_log_hook = $wp_filter['tribe_log'] ?? null;
		$wp_filter['tribe_log']  = new WP_Hook(); // phpcs:ignore
	}

	/**
	 * @after
	 */
	public function restore_state(): void {
		remove_filter( 'pre_http_request', [ __CLASS__, 'route_request' ], 10 );
		self::$current = null;

		global $wp_filter;
		if ( null === $this->previous_log_hook ) {
			unset( $wp_filter['tribe_log'] );
		} else {
			$wp_filter['tribe_log'] = $this->previous_log_hook; // phpcs:ignore
		}

		$merchant = tribe( Merchant::class );
		$merchant->delete_token_status();
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

		$parsed = [];
		parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $parsed );
		$this->whodat_calls[] = $parsed;

		$response = array_shift( $this->whodat_responses );

		if ( $response instanceof WP_Error ) {
			return $response;
		}

		return [
			'headers'  => [],
			'body'     => wp_json_encode( $response ),
			'response' => [ 'code' => 200, 'message' => 'OK' ],
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
		$merchant->delete_token_status();
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
		$credentials             = $this->fresh_credentials();
		$this->whodat_responses[] = $credentials;

		$this->assertTrue( $this->refresher()->should_refresh() );
		$this->assertTrue( $this->refresher()->refresh_if_needed() );

		$this->assertCount( 1, $this->whodat_calls );
		$this->assertSame( 'refresh_token', $this->whodat_calls[0]['grant_type'] );
		$this->assertSame( tec_tickets_tests_get_fake_merchant_data()['refresh_token'], $this->whodat_calls[0]['refresh_token'] );

		$this->assertSame( $credentials['access_token'], $merchant->get_access_token() );
		$this->assertSame( $credentials['refresh_token'], $merchant->get_refresh_token() );
		$this->assertSame( $credentials['expires_at'], get_option( $merchant->get_signup_data_key() )['expires_at'] );
		$this->assertFalse( $merchant->is_token_invalid() );
		$this->assertSame( 0, (int) $merchant->get_token_status()['failures'] );
	}

	/**
	 * @test
	 */
	public function it_should_refresh_a_token_that_has_already_expired(): void {
		$merchant                 = $this->connect( [ 'expires_at' => gmdate( 'Y-m-d\TH:i:s\Z', time() - DAY_IN_SECONDS ) ] );
		$this->whodat_responses[] = $this->fresh_credentials();

		$this->assertTrue( $this->refresher()->refresh_if_needed() );
		$this->assertSame( 'refreshed-access-token', $merchant->get_access_token() );
	}

	/**
	 * @test
	 */
	public function it_should_refresh_a_connection_with_no_recorded_expiration(): void {
		$merchant                 = $this->connect( [], [ 'expires_at' ] );
		$this->whodat_responses[] = $this->fresh_credentials();

		$this->assertTrue( $this->refresher()->refresh_if_needed() );
		$this->assertCount( 1, $this->whodat_calls );
		$this->assertNotNull( $merchant->get_token_expiration() );
	}

	/**
	 * @test
	 */
	public function it_should_throttle_a_connection_with_no_recorded_expiration(): void {
		$this->connect( [], [ 'expires_at' ] );
		// A response with neither credentials nor a recognised error is treated as a blip.
		$this->whodat_responses[] = [];

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
	 * @test
	 */
	public function it_should_mark_the_connection_unavailable_when_square_refuses_the_refresh(): void {
		$merchant                 = $this->connect( $this->expiring_soon() );
		$this->whodat_responses[] = [
			'error'             => 'invalid_grant',
			'error_description' => 'Authorization code is expired.',
		];

		$this->assertFalse( $this->refresher()->refresh_if_needed() );

		$this->assertTrue( $merchant->is_token_invalid() );
		$this->assertSame( 'invalid_grant', $merchant->get_token_status()['error'] );
		$this->assertFalse( $merchant->is_connected() );

		// The credentials stay put so support can still see what is stored.
		$this->assertSame( tec_tickets_tests_get_fake_merchant_data()['access_token'], $merchant->get_access_token() );
		$this->assertSame( tec_tickets_tests_get_fake_merchant_data()['refresh_token'], $merchant->get_refresh_token() );
	}

	/**
	 * @test
	 */
	public function it_should_treat_a_square_authentication_error_body_as_unrecoverable(): void {
		$merchant                 = $this->connect( $this->expiring_soon() );
		$this->whodat_responses[] = [
			'errors' => [
				[
					'category' => 'AUTHENTICATION_ERROR',
					'code'     => 'UNAUTHORIZED',
				],
			],
		];

		$this->assertFalse( $this->refresher()->refresh_if_needed() );
		$this->assertTrue( $merchant->is_token_invalid() );
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
		$this->assertSame( 1, (int) $merchant->get_token_status()['failures'] );
		$this->assertNotEmpty( $merchant->get_token_status()['last_attempt_at'] );
	}

	/**
	 * @test
	 */
	public function it_should_not_mark_the_connection_unavailable_on_a_square_server_error(): void {
		$merchant                 = $this->connect( $this->expiring_soon() );
		$this->whodat_responses[] = [
			'errors' => [
				[
					'category' => 'API_ERROR',
					'code'     => 'INTERNAL_SERVER_ERROR',
				],
			],
		];

		$this->assertFalse( $this->refresher()->refresh_if_needed() );
		$this->assertFalse( $merchant->is_token_invalid() );
		$this->assertTrue( $merchant->is_connected() );
	}

	/**
	 * @test
	 */
	public function it_should_back_off_after_a_transient_failure(): void {
		$merchant                 = $this->connect( $this->expiring_soon() );
		$this->whodat_responses[] = new WP_Error( 'http_request_failed', 'Connection timed out' );

		$this->refresher()->refresh_if_needed();
		$this->assertCount( 1, $this->whodat_calls );

		$this->refresher()->refresh_if_needed();
		$this->assertCount( 1, $this->whodat_calls );

		// Move the attempt far enough into the past for the backoff to lapse.
		$merchant->update_token_status( [ 'last_attempt_at' => gmdate( 'Y-m-d H:i:s', time() - 2 * DAY_IN_SECONDS ) ] );
		$this->whodat_responses[] = $this->fresh_credentials();

		$this->assertTrue( $this->refresher()->refresh_if_needed() );
		$this->assertCount( 2, $this->whodat_calls );
	}

	/**
	 * @test
	 */
	public function it_should_not_retry_once_the_connection_is_marked_unavailable(): void {
		$merchant = $this->connect( $this->expiring_soon() );
		$merchant->update_token_status( [ 'invalid_at' => gmdate( 'Y-m-d H:i:s' ) ] );

		$this->assertFalse( $this->refresher()->should_refresh() );
		$this->assertFalse( $this->refresher()->refresh_if_needed() );
		$this->assertFalse( $this->refresher()->refresh_now() );
		$this->assertCount( 0, $this->whodat_calls );
	}

	/**
	 * @test
	 */
	public function it_should_not_refresh_while_another_process_holds_the_lock(): void {
		$this->connect( $this->expiring_soon() );
		add_option( $this->get_lock_key(), (string) time(), '', 'no' );
		$this->whodat_responses[] = $this->fresh_credentials();

		$this->assertFalse( $this->refresher()->refresh_if_needed() );
		$this->assertCount( 0, $this->whodat_calls );
	}

	/**
	 * A process that died mid-refresh must not hold refreshes off forever.
	 *
	 * @test
	 */
	public function it_should_take_over_an_abandoned_lock(): void {
		$this->connect( $this->expiring_soon() );
		add_option( $this->get_lock_key(), (string) ( time() - 300 ), '', 'no' );
		$this->whodat_responses[] = $this->fresh_credentials();

		$this->assertTrue( $this->refresher()->refresh_if_needed() );
		$this->assertCount( 1, $this->whodat_calls );
	}

	/**
	 * @test
	 */
	public function it_should_release_the_lock_whatever_the_outcome(): void {
		$this->connect( $this->expiring_soon() );
		$this->whodat_responses[] = $this->fresh_credentials();

		$this->refresher()->refresh_if_needed();
		$this->assertFalse( get_option( $this->get_lock_key() ) );

		$this->connect( $this->expiring_soon() );
		$this->whodat_responses[] = [ 'error' => 'invalid_grant' ];

		$this->refresher()->refresh_if_needed();
		$this->assertFalse( get_option( $this->get_lock_key() ) );
	}

	/**
	 * @test
	 */
	public function it_should_refresh_on_demand_regardless_of_the_expiration(): void {
		$merchant                 = $this->connect( [ 'expires_at' => gmdate( 'Y-m-d\TH:i:s\Z', time() + 25 * DAY_IN_SECONDS ) ] );
		$this->whodat_responses[] = $this->fresh_credentials();

		$this->assertFalse( $this->refresher()->should_refresh() );
		$this->assertTrue( $this->refresher()->refresh_now( 'square_unauthorized' ) );
		$this->assertSame( 'refreshed-access-token', $merchant->get_access_token() );
	}
}
