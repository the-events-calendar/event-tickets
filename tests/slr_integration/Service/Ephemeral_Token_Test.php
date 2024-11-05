<?php

namespace TEC\Tickets\Seating\Service;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PHPUnit\Framework\Assert;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tests\Traits\WP_Remote_Mocks;
use WP_Error;

class Ephemeral_Token_Test extends WPTestCase {
	use With_Uopz;
	use WP_Remote_Mocks;
	use OAuth_Token;

	public function test_get_ephemeral_token_url(): void {
		$ephemeral_token = new Ephemeral_Token( 'http://test.com' );

		$ephemeral_token_url = $ephemeral_token->get_ephemeral_token_url();

		$this->assertEquals( 'http://test.com/api/v1/ephemeral-token', $ephemeral_token_url );
	}

	public function test_get_ephemeral_token_defaults(): void {
		$ephemeral_token = new Ephemeral_Token( 'http://test.com' );

		add_filter( 'tec_tickets_seating_ephemeral_token', function ( $token, $expiration, $scope ) {
			Assert::assertEquals( 900, $expiration );
			Assert::assertEquals( 'visitor', $scope );

			return 'test-token';
		}, 10, 3 );

		$token = $ephemeral_token->get_ephemeral_token();

		$this->assertEquals( 'test-token', $token );
	}

	public function test_get_ephemeral_token_with_custom_expiration(): void {
		$ephemeral_token = new Ephemeral_Token( 'http://test.com' );
		$this->set_oauth_token( 'test-oauth-token' );

		$this->mock_wp_remote(
			'post',
			add_query_arg( [
				'site'       => urlencode_deep('http://wordpress.test'),
				'expires_in' => 2389 * 1000,
				'scope'      => 'visitor',
			],
				'http://test.com/api/v1/ephemeral-token' ),
			[
				'headers' => [
					'Accept'        => 'application/json',
					'Authorization' => 'Bearer test-oauth-token',
				],
			],
			function () {
				return [
					'response' => [
						'code' => 200,
					],
					'body'     => wp_json_encode(
						[
							'data' => [
								'token' => 'test-token',
							],
						]
					),
				];
			}
		);

		$token = $ephemeral_token->get_ephemeral_token( 2389 );

		$this->assertEquals( 'test-token', $token );
	}

	public function test_get_ephemeral_token_with_custom_scope(): void {
		$ephemeral_token = new Ephemeral_Token( 'http://test.com' );
		$this->set_oauth_token( 'test-oauth-token' );

		$this->mock_wp_remote(
			'post',
			add_query_arg( [
				'site'       => urlencode_deep('http://wordpress.test'),
				'expires_in' => 900 * 1000,
				'scope'      => 'custom',
			],
				'http://test.com/api/v1/ephemeral-token' ),
			[
				'headers' => [
					'Accept'        => 'application/json',
					'Authorization' => 'Bearer test-oauth-token',
				],
			],
			function () {
				return [
					'response' => [
						'code' => 200,
					],
					'body'     => wp_json_encode(
						[
							'data' => [
								'token' => 'test-token',
							],
						]
					),
				];
			}
		);

		$token = $ephemeral_token->get_ephemeral_token( 900, 'custom' );

		$this->assertEquals( 'test-token', $token );
	}

	public function test_get_ephemeral_token_fails_if_response_code_is_not_200(): void {
		$ephemeral_token = new Ephemeral_Token( 'http://test.com' );
		$this->set_oauth_token( 'test-oauth-token' );

		$this->mock_wp_remote(
			'post',
			add_query_arg( [
				'site'       => urlencode_deep('http://wordpress.test'),
				'expires_in' => 900 * 1000,
				'scope'      => 'custom',
			],
				'http://test.com/api/v1/ephemeral-token' ),
			[
				'headers' => [
					'Accept'        => 'application/json',
					'Authorization' => 'Bearer test-oauth-token',
				],
			],
			function () {
				return [
					'response' => [
						'code' => 400,
					],
				];
			}
		);

		$token = $ephemeral_token->get_ephemeral_token( 900, 'custom' );

		$this->assertInstanceOf( WP_Error::class, $token );
		$this->assertEquals( 'ephemeral_token_request_failed', $token->get_error_code() );
		$this->assertEquals( 400, $token->get_error_data()['code'] );
	}

	public function test_get_ephemeral_token_fails_if_request_fails():void{
		$ephemeral_token = new Ephemeral_Token( 'http://test.com' );
		$this->set_oauth_token( 'test-oauth-token' );
		$response = new WP_Error();
		$response->add(0,'cURL error 6: Could not resolve host: test.com');

		$this->mock_wp_remote(
			'post',
			add_query_arg( [
				'site'       => urlencode_deep('http://wordpress.test'),
				'expires_in' => 900 * 1000,
				'scope'      => 'custom',
			],
				'http://test.com/api/v1/ephemeral-token' ),
			[
				'headers' => [
					'Accept'        => 'application/json',
					'Authorization' => 'Bearer test-oauth-token',
				],
			],
			$response
		);

		$token = $ephemeral_token->get_ephemeral_token( 900, 'custom' );

		$this->assertInstanceOf( WP_Error::class, $token );
		$this->assertEquals( 'ephemeral_token_request_failed', $token->get_error_code() );
		$this->assertEquals( 0, $token->get_error_data()['code'] );
		$this->assertEquals( 'cURL error 6: Could not resolve host: test.com', $token->get_error_data()['error'] );
	}

	public function test_get_ephemeral_token_fails_if_response_body_is_empty(): void {
		$ephemeral_token = new Ephemeral_Token( 'http://test.com' );
		$this->set_oauth_token( 'test-oauth-token' );

		$this->mock_wp_remote(
			'post',
			add_query_arg( [
				'site'       => urlencode_deep('http://wordpress.test'),
				'expires_in' => 900 * 1000,
				'scope'      => 'custom',
			],
				'http://test.com/api/v1/ephemeral-token' ),
			[
				'headers' => [
					'Accept'        => 'application/json',
					'Authorization' => 'Bearer test-oauth-token',
				],
			],
			function () {
				return [
					'response' => [
						'code' => 200,
					],
					'body'     => '',
				];
			}
		);

		$token = $ephemeral_token->get_ephemeral_token( 900, 'custom' );

		$this->assertInstanceOf( WP_Error::class, $token );
		$this->assertEquals( 'ephemeral_token_response_invalid', $token->get_error_code() );
	}

	public function test_get_ephemeral_token_fails_if_response_body_is_invalid(): void {
		$ephemeral_token = new Ephemeral_Token( 'http://test.com' );
		$this->set_oauth_token( 'test-oauth-token' );

		$this->mock_wp_remote(
			'post',
			add_query_arg( [
				'site'       => urlencode_deep('http://wordpress.test'),
				'expires_in' => 900 * 1000,
				'scope'      => 'custom',
			],
				'http://test.com/api/v1/ephemeral-token' ),
			[
				'headers' => [
					'Accept'        => 'application/json',
					'Authorization' => 'Bearer test-oauth-token',
				],
			],
			function () {
				return [
					'response' => [
						'code' => 200,
					],
					'body'     => '{"data":{"foo":"bar"}}',
				];
			}
		);

		$token = $ephemeral_token->get_ephemeral_token( 900, 'custom' );

		$this->assertInstanceOf( WP_Error::class, $token );
		$this->assertEquals( 'ephemeral_token_response_invalid', $token->get_error_code() );
		$this->assertEquals( '{"data":{"foo":"bar"}}', $token->get_error_data()['body'] );
	}
}
