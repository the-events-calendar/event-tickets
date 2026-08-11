<?php
/**
 * Guards that gateway secrets are never sent to WhoDat in a URL query string.
 *
 * Query strings are recorded by server access logs, reverse proxies and CDNs by default, so a
 * credential placed there is persisted well outside our control. Secrets belong in the request
 * body, which is not logged.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways
 */

namespace TEC\Tickets\Commerce\Gateways;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Gateways\PayPal\Merchant as PayPal_Merchant;
use TEC\Tickets\Commerce\Gateways\PayPal\WhoDat as PayPal_WhoDat;
use TEC\Tickets\Commerce\Gateways\Square\Merchant as Square_Merchant;
use TEC\Tickets\Commerce\Gateways\Square\WhoDat as Square_WhoDat;

/**
 * Class WhoDat_Request_Secrets_Test.
 *
 * @since TBD
 *
 * @covers \TEC\Tickets\Commerce\Gateways\Square\WhoDat
 * @covers \TEC\Tickets\Commerce\Gateways\PayPal\WhoDat
 *
 * @package TEC\Tickets\Commerce\Gateways
 */
class WhoDat_Request_Secrets_Test extends WPTestCase {

	/**
	 * The outbound request captured by the pre_http_request filter.
	 *
	 * @var array
	 */
	private array $captured = [];

	/**
	 * Remove the seeded Square credentials.
	 *
	 * @after
	 */
	public function cleanup_signup_data(): void {
		delete_option( tribe( Square_Merchant::class )->get_signup_data_key() );
	}

	/**
	 * Intercept the outbound request and answer it without touching the network.
	 *
	 * @return void
	 */
	private function capture_next_request(): void {
		add_filter(
			'pre_http_request',
			function ( $response, $parsed_args, $url ) {
				$this->captured = [
					'url'  => $url,
					'args' => $parsed_args,
				];

				return [
					'headers'  => [],
					'body'     => wp_json_encode( [ 'success' => true ] ),
					'response' => [
						'code'    => 200,
						'message' => 'OK',
					],
					'cookies'  => [],
					'filename' => null,
				];
			},
			99,
			3
		);
	}

	/**
	 * Every WhoDat call that carries a credential.
	 *
	 * @return array<string,array{0:string,1:string,2:string}>
	 */
	public function secret_bearing_call_provider(): array {
		return [
			'Square disconnect'         => [ 'square_disconnect', 'sq0atp-SQUARE-ACCESS-TOKEN', 'access_token' ],
			'Square token refresh'      => [ 'square_refresh', 'sq0rtp-SQUARE-REFRESH-TOKEN', 'refresh_token' ],
			'PayPal seller status'      => [ 'paypal_status', 'A21AA-PAYPAL-ACCESS-TOKEN', 'access_token' ],
			'PayPal seller credentials' => [ 'paypal_credentials', 'A21AA-PAYPAL-CREDENTIALS-TOKEN', 'access_token' ],
		];
	}

	/**
	 * Run the named WhoDat call with the given secret in play.
	 *
	 * @param string $case   Which call to exercise.
	 * @param string $secret The credential that must not leak into the URL.
	 *
	 * @return void
	 */
	private function invoke_case( string $case, string $secret ): void {
		switch ( $case ) {
			case 'square_disconnect':
				update_option(
					tribe( Square_Merchant::class )->get_signup_data_key(),
					[ 'access_token' => $secret ]
				);
				tribe( Square_WhoDat::class )->disconnect_account();
				break;

			case 'square_refresh':
				update_option(
					tribe( Square_Merchant::class )->get_signup_data_key(),
					[ 'refresh_token' => $secret ]
				);
				tribe( Square_WhoDat::class )->refresh_token();
				break;

			case 'paypal_status':
				tribe( PayPal_Merchant::class )->set_access_token( $secret, false );
				tribe( PayPal_WhoDat::class )->get_seller_status( 'MERCHANT-' . uniqid() );
				break;

			case 'paypal_credentials':
				tribe( PayPal_WhoDat::class )->get_seller_credentials( $secret );
				break;
		}
	}

	/**
	 * @test
	 * @dataProvider secret_bearing_call_provider
	 *
	 * @param string $case      Which call to exercise.
	 * @param string $secret    The credential that must not leak into the URL.
	 * @param string $body_key  The body parameter the credential is expected under.
	 */
	public function should_send_credentials_in_the_body_never_the_url( string $case, string $secret, string $body_key ): void {
		$this->capture_next_request();

		$this->invoke_case( $case, $secret );

		$this->assertNotEmpty( $this->captured, 'Expected an outbound request to WhoDat.' );

		$this->assertStringNotContainsString(
			$secret,
			$this->captured['url'],
			'The credential must not appear in the request URL — query strings land in access logs.'
		);

		$this->assertStringNotContainsString(
			$body_key,
			$this->captured['url'],
			'Not even the parameter name belongs in the URL.'
		);

		$body = $this->captured['args']['body'] ?? [];
		$body = is_array( $body ) ? $body : (array) json_decode( (string) $body, true );

		$this->assertSame(
			$secret,
			$body[ $body_key ] ?? null,
			'The credential must be sent in the request body.'
		);
	}
}
