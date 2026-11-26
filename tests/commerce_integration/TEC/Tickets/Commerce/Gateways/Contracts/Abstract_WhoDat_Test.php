<?php

namespace TEC\Tickets\Commerce\Gateways\Contracts;

use Closure;
use Codeception\TestCase\WPTestCase;
use Generator;
use RuntimeException;
use TEC\Tickets\Commerce\Gateways\PayPal\Merchant as PayPal_Merchant;
use TEC\Tickets\Commerce\Gateways\PayPal\WhoDat as PayPal_WhoDat;
use TEC\Tickets\Commerce\Gateways\Square\Merchant as Square_Merchant;
use TEC\Tickets\Commerce\Gateways\Square\WhoDat as Square_WhoDat;
use WP_Error;

class Abstract_WhoDat_Test extends WPTestCase {
	/**
	 * The secret each test plants and then looks for in the log.
	 *
	 * @var string
	 */
	private string $secret;

	/**
	 * Every `tribe_log` call made during the test, JSON encoded.
	 *
	 * @var string[]
	 */
	private array $logged = [];

	/**
	 * The HTTP response every WhoDat request gets.
	 *
	 * @var array|WP_Error
	 */
	private $whodat_response;

	/**
	 * Plants the secret and starts capturing.
	 *
	 * Runs after parent::setUp() rather than as a before-hook method, which PHPUnit calls ahead of the
	 * transaction WPTestCase opens, so the signup option would otherwise outlive this test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->secret          = 'secret-' . wp_generate_password( 24, false );
		$this->logged          = [];
		$this->whodat_response = new WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' );

		add_action( 'tribe_log', [ $this, 'record_log' ], 10, 3 );
		add_filter( 'pre_http_request', [ $this, 'answer_whodat' ], 10, 3 );

		$square_merchant = tribe( Square_Merchant::class );
		update_option(
			$square_merchant->get_signup_data_key(),
			[
				'access_token' => $this->secret,
				'client_id'    => 'client-id',
				'merchant_id'  => 'merchant-id',
			]
		);

		tribe( PayPal_Merchant::class )->set_access_token( $this->secret, false );
	}

	/**
	 * Stops capturing.
	 */
	public function tearDown(): void {
		remove_action( 'tribe_log', [ $this, 'record_log' ], 10 );
		remove_filter( 'pre_http_request', [ $this, 'answer_whodat' ], 10 );
		tribe( PayPal_Merchant::class )->set_access_token( null, false );

		parent::tearDown();
	}

	/**
	 * Records a log call.
	 *
	 * @param string $level   The log level.
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 */
	public function record_log( $level, $message, $context = [] ): void {
		$this->logged[] = wp_json_encode( [ $level, $message, $context ] );
	}

	/**
	 * Answers every WhoDat request with the response the test set.
	 *
	 * @param false|array $preempt Whether to preempt the request.
	 * @param array       $args    The request arguments.
	 * @param string      $url     The request URL.
	 *
	 * @return false|array|WP_Error
	 */
	public function answer_whodat( $preempt, $args, $url ) {
		return false !== strpos( $url, 'whodat' ) ? $this->whodat_response : $preempt;
	}

	/**
	 * Every WhoDat call that puts a secret in the URL.
	 *
	 * @return Generator<string, array{0: Closure}>
	 */
	public function whodat_calls_with_a_secret_in_the_url(): Generator {
		yield 'Square token status' => [ fn() => tribe( Square_WhoDat::class )->get_token_status() ];
		yield 'Square fresh token status' => [ fn() => tribe( Square_WhoDat::class )->get_fresh_token_status() ];
		yield 'Square disconnect' => [ fn() => tribe( Square_WhoDat::class )->disconnect_account() ];
		yield 'Square webhook registration' => [
			function ( string $secret ) {
				try {
					tribe( Square_WhoDat::class )->register_webhook_endpoint(
						'https://example.org/wp-json/tribe/tickets/v1/commerce/square/webhooks?tec-tc-key=' . $secret,
						'merchant-id'
					);
				} catch ( RuntimeException $e ) {
					// Registration throws on failure; the log is what is under test.
					unset( $e );
				}
			},
		];
		yield 'PayPal seller status' => [ fn() => tribe( PayPal_WhoDat::class )->get_seller_status( 'merchant-' . wp_generate_password( 8, false ) ) ];
		yield 'PayPal seller credentials' => [ fn( string $secret ) => tribe( PayPal_WhoDat::class )->get_seller_credentials( $secret ) ];
	}

	/**
	 * @test
	 * @dataProvider whodat_calls_with_a_secret_in_the_url
	 */
	public function it_should_not_log_the_secret_when_the_request_fails( Closure $call ): void {
		$call( $this->secret );

		$this->assertNotEmpty( $this->logged, 'The failure should still be logged.' );
		$this->assertStringContainsString( 'whodat', implode( "\n", $this->logged ), 'The log should name the WhoDat endpoint.' );
		$this->assertStringNotContainsString( $this->secret, implode( "\n", $this->logged ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_log_the_secret_when_the_error_message_repeats_the_url(): void {
		$this->whodat_response = new WP_Error(
			'http_request_failed',
			'cURL error 28: Operation timed out for https://whodat.theeventscalendar.com/commerce/v1/square/oauth/token/revoke?access_token=' . $this->secret . ' (after 5000 ms)'
		);

		tribe( Square_WhoDat::class )->disconnect_account();

		$this->assertNotEmpty( $this->logged, 'The failure should still be logged.' );
		$this->assertStringContainsString( 'after 5000 ms', implode( "\n", $this->logged ), 'The rest of the message should be kept.' );
		$this->assertStringNotContainsString( $this->secret, implode( "\n", $this->logged ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_log_the_body_of_a_response_that_is_not_json(): void {
		$this->whodat_response = [
			'headers'  => [ 'x-whodat-debug' => $this->secret ],
			'body'     => '<html>client_secret=' . $this->secret . '</html>',
			'response' => [
				'code'    => 200,
				'message' => 'OK',
			],
			'cookies'  => [],
			'filename' => null,
		];

		$this->assertNull( tribe( PayPal_WhoDat::class )->get_seller_credentials( 'not-the-secret' ) );

		$this->assertNotEmpty( $this->logged, 'The unexpected response should still be logged.' );
		$this->assertStringNotContainsString( $this->secret, implode( "\n", $this->logged ) );
	}
}
