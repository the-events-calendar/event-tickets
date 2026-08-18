<?php

namespace TEC\Tickets\Commerce\Gateways\Square;

use Codeception\TestCase\WPTestCase;
use WP_Hook;

class Requests_Test extends WPTestCase {
	/**
	 * The Authorization header of every Square API call made during the test.
	 *
	 * @var string[]
	 */
	protected array $square_calls = [];

	/**
	 * How many times WhoDat was asked for a new token.
	 *
	 * @var int
	 */
	protected int $whodat_calls = 0;

	/**
	 * The access token Square will accept.
	 *
	 * @var string
	 */
	protected string $accepted_token = 'refreshed-access-token';

	/**
	 * The body WhoDat answers a refresh with.
	 *
	 * @var mixed
	 */
	protected $whodat_response;

	/**
	 * The response Square should answer with instead of the default, when set.
	 *
	 * @var ?array
	 */
	protected ?array $square_response_override = null;

	/**
	 * The tribe_log hook as it was before the test replaced it.
	 *
	 * @var mixed
	 */
	protected $previous_log_hook;

	/**
	 * The instance the shared filter callback should route to.
	 *
	 * WordPress restores $wp_filter after the @fter methods run, which puts an instance callback back
	 * once the instance is gone. A static callback keeps a single, stable registration instead.
	 *
	 * @var ?self
	 */
	protected static $current;

	/**
	 * @before
	 */
	public function intercept_requests(): void {
		$this->square_calls    = [];
		$this->whodat_calls    = 0;
		$this->accepted_token  = 'refreshed-access-token';
		$this->whodat_response = [
			'access_token'  => 'refreshed-access-token',
			'refresh_token' => 'refreshed-refresh-token',
			'expires_at'    => gmdate( 'Y-m-d\TH:i:s\Z', time() + 30 * DAY_IN_SECONDS ),
		];

		$this->square_response_override = null;

		self::$current = $this;
		add_filter( 'pre_http_request', [ __CLASS__, 'route_request' ], 10, 3 );

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
		delete_option( 'tec_tickets_commerce_square_token_refresh_lock_' . $merchant->get_mode() );
		tribe_cache()->reset();
	}

	/**
	 * Stands in for both WhoDat and the Square API.
	 *
	 * @param mixed  $pre  The short-circuit value.
	 * @param array  $args The request arguments.
	 * @param string $url  The request URL.
	 *
	 * @return mixed
	 */
	public static function route_request( $pre, $args, $url ) {
		return self::$current ? self::$current->answer_request( $pre, $args, $url ) : $pre;
	}

	/**
	 * Stands in for both WhoDat and the Square API.
	 *
	 * @param mixed  $pre  The short-circuit value.
	 * @param array  $args The request arguments.
	 * @param string $url  The request URL.
	 *
	 * @return mixed
	 */
	public function answer_request( $pre, $args, $url ) {
		if ( false !== strpos( $url, 'whodat' ) ) {
			++$this->whodat_calls;

			return $this->build_response( $this->whodat_response );
		}

		if ( false === strpos( $url, 'squareup' ) ) {
			return $pre;
		}

		$authorization        = (string) ( $args['headers']['Authorization'] ?? '' );
		$this->square_calls[] = $authorization;

		if ( null !== $this->square_response_override ) {
			return $this->build_response( $this->square_response_override, 400 );
		}

		if ( 'Bearer ' . $this->accepted_token !== $authorization ) {
			return $this->build_response(
				[
					'errors' => [
						[
							'category' => 'AUTHENTICATION_ERROR',
							'code'     => 'UNAUTHORIZED',
							'detail'   => 'This request could not be authorized.',
						],
					],
				],
				401
			);
		}

		return $this->build_response( [ 'locations' => [ [ 'id' => 'L1', 'name' => 'HQ' ] ] ] );
	}

	protected function build_response( $body, int $code = 200 ): array {
		return [
			'headers'  => [],
			'body'     => wp_json_encode( $body ),
			'response' => [
				'code'    => $code,
				'message' => 200 === $code ? 'OK' : 'Unauthorized',
			],
			'cookies'  => [],
			'filename' => null,
		];
	}

	/**
	 * Puts the connection into a known state.
	 *
	 * @param array $overrides Signup data overrides.
	 */
	protected function connect( array $overrides = [] ): Merchant {
		$merchant = tribe( Merchant::class );
		$merchant->delete_token_status();
		delete_option( 'tec_tickets_commerce_square_token_refresh_lock_' . $merchant->get_mode() );
		$merchant->save_signup_data( array_merge( tec_tickets_tests_get_fake_merchant_data(), $overrides ) );
		tribe_cache()->reset();

		return $merchant;
	}

	protected function not_due(): array {
		return [ 'expires_at' => gmdate( 'Y-m-d\TH:i:s\Z', time() + 25 * DAY_IN_SECONDS ) ];
	}

	/**
	 * @test
	 */
	public function it_should_refresh_an_expired_token_before_signing_the_request(): void {
		$merchant = $this->connect( [ 'expires_at' => gmdate( 'Y-m-d\TH:i:s\Z', time() - HOUR_IN_SECONDS ) ] );

		$response = Requests::get( 'locations' );

		$this->assertSame( 1, $this->whodat_calls );
		$this->assertSame( [ 'Bearer refreshed-access-token' ], $this->square_calls );
		$this->assertArrayHasKey( 'locations', $response );
		$this->assertSame( 'refreshed-access-token', $merchant->get_access_token() );
	}

	/**
	 * Square can reject a token before its recorded expiration, so the 401 has to be handled too.
	 *
	 * @test
	 */
	public function it_should_retry_once_after_square_rejects_the_token(): void {
		$merchant = $this->connect( $this->not_due() );

		$response = Requests::get( 'locations' );

		$this->assertSame(
			[
				'Bearer ' . tec_tickets_tests_get_fake_merchant_data()['access_token'],
				'Bearer refreshed-access-token',
			],
			$this->square_calls
		);
		$this->assertArrayHasKey( 'locations', $response );
		$this->assertSame( 'refreshed-access-token', $merchant->get_access_token() );
		$this->assertFalse( $merchant->is_token_invalid() );
	}

	/**
	 * @test
	 */
	public function it_should_never_retry_more_than_once(): void {
		$this->connect( $this->not_due() );
		// Nothing satisfies Square, so the retry gets rejected too.
		$this->accepted_token = 'a-token-square-will-never-see';

		$response = Requests::get( 'locations' );

		$this->assertCount( 2, $this->square_calls );
		$this->assertSame( 1, $this->whodat_calls );
		$this->assertSame( 'AUTHENTICATION_ERROR', $response['errors'][0]['category'] );
	}

	/**
	 * @test
	 */
	public function it_should_not_retry_when_the_refresh_is_refused(): void {
		$merchant              = $this->connect( $this->not_due() );
		$this->whodat_response = [ 'error' => 'invalid_grant' ];

		$response = Requests::get( 'locations' );

		$this->assertCount( 1, $this->square_calls );
		$this->assertSame( 'AUTHENTICATION_ERROR', $response['errors'][0]['category'] );
		$this->assertTrue( $merchant->is_token_invalid() );
	}

	/**
	 * @test
	 */
	public function it_should_leave_errors_that_are_not_about_authentication_alone(): void {
		$this->connect( $this->not_due() );

		$this->square_response_override = [ 'errors' => [ [ 'category' => 'INVALID_REQUEST_ERROR', 'code' => 'BAD_REQUEST' ] ] ];

		$response = Requests::get( 'locations' );

		$this->assertCount( 1, $this->square_calls );
		$this->assertSame( 0, $this->whodat_calls );
		$this->assertSame( 'INVALID_REQUEST_ERROR', $response['errors'][0]['category'] );
	}

	/**
	 * @test
	 */
	public function it_should_detect_an_unauthorized_raw_response(): void {
		$merchant = $this->connect( $this->not_due() );

		$response = Requests::get( 'locations', [], [], true );

		$this->assertSame(
			[
				'Bearer ' . tec_tickets_tests_get_fake_merchant_data()['access_token'],
				'Bearer refreshed-access-token',
			],
			$this->square_calls
		);
		$this->assertSame( 200, wp_remote_retrieve_response_code( $response ) );
		$this->assertSame( 'refreshed-access-token', $merchant->get_access_token() );
	}

	/**
	 * A rotated token must not be answered from the cache the old one filled.
	 *
	 * @test
	 */
	public function it_should_key_the_request_cache_by_access_token(): void {
		$merchant             = $this->connect( $this->not_due() );
		$this->accepted_token = tec_tickets_tests_get_fake_merchant_data()['access_token'];

		$first = Requests::get_with_cache( 'locations' );
		$this->assertArrayHasKey( 'locations', $first );
		$this->assertCount( 1, $this->square_calls );

		// Same token: served from cache.
		Requests::get_with_cache( 'locations' );
		$this->assertCount( 1, $this->square_calls );

		// Rotated token: the cache key changes, so Square is asked again.
		$merchant->save_refreshed_tokens(
			[
				'access_token' => 'another-access-token',
				'expires_at'   => gmdate( 'Y-m-d\TH:i:s\Z', time() + 25 * DAY_IN_SECONDS ),
			]
		);
		$this->accepted_token = 'another-access-token';

		Requests::get_with_cache( 'locations' );
		$this->assertCount( 2, $this->square_calls );
		$this->assertSame( 'Bearer another-access-token', end( $this->square_calls ) );
		$this->assertSame( 0, $this->whodat_calls );
	}
}
