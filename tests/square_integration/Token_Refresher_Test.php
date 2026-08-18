<?php

namespace TEC\Tickets\Commerce\Gateways\Square;

use Codeception\TestCase\WPTestCase;
use WP_Error;
use WP_Hook;

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
	 * What the token status endpoint reports about the stored access token.
	 *
	 * @var array
	 */
	protected array $token_status = [ 'scopes' => [ 'PAYMENTS_WRITE' ] ];

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
		$this->token_status     = [ 'scopes' => [ 'PAYMENTS_WRITE' ] ];

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

		$this->whodat_calls[] = array_merge( $parsed, is_array( $body ) ? $body : [] );

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
		$this->assertSame( 0, (int) $merchant->get_token_status()['failures'] );
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
		$this->assertSame( '500', $merchant->get_token_status()['error'] );
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
		$this->whodat_responses[] = $this->refresh_error_page( 422 );

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
		$this->assertSame( 1, (int) $merchant->get_token_status()['failures'] );
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
		$merchant->update_token_status( [ 'last_attempt_at' => gmdate( 'Y-m-d H:i:s', time() - 2 * DAY_IN_SECONDS ) ] );
		$this->whodat_responses[] = $this->refresh_ok();

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
		$this->whodat_responses[] = $this->refresh_ok();

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
		$this->whodat_responses[] = $this->refresh_ok();

		$this->assertTrue( $this->refresher()->refresh_if_needed() );
		$this->assertCount( 1, $this->whodat_calls );
	}

	/**
	 * @test
	 */
	public function it_should_release_the_lock_whatever_the_outcome(): void {
		$this->connect( $this->expiring_soon() );
		$this->whodat_responses[] = $this->refresh_ok();

		$this->refresher()->refresh_if_needed();
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
