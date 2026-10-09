<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Gateways\PayPal\Location\Country;

class SignupTest extends WPTestCase {

	/**
	 * Log entries collected while a test runs.
	 *
	 * @var array<array{0:string,1:string}>
	 */
	protected array $logs = [];

	/**
	 * @before
	 * @after
	 */
	public function reset_signup_state(): void {
		$signup = tribe( Signup::class );
		$signup->delete_transient_data();
		$signup->delete_transient_hash();
		tribe( Country::class )->save_setting( Country::DEFAULT_COUNTRY_CODE );
	}

	/**
	 * @before
	 */
	public function collect_logs(): void {
		$this->logs = [];

		add_action(
			'tribe_log',
			function ( $level, $src, $context ) {
				$this->logs[] = [ $level, implode( ' ', (array) $context ) ];
			},
			10,
			3
		);
	}

	/**
	 * Answers every WhoDat request with the given status code and body.
	 */
	protected function stub_whodat( int $status_code, string $body ): void {
		add_filter(
			'pre_http_request',
			static function ( $pre, $args, $url ) use ( $status_code, $body ) {
				if ( false === strpos( $url, '/commerce/v1/paypal/' ) ) {
					return $pre;
				}

				return [
					'headers'  => [],
					'body'     => $body,
					'response' => [ 'code' => $status_code, 'message' => '' ],
					'cookies'  => [],
					'filename' => null,
				];
			},
			10,
			3
		);
	}

	/**
	 * Builds the WhoDat payload for a signup URL. `links[1]` is the one the plugin reads.
	 */
	protected function whodat_signup_body( string $signup_url ): string {
		return wp_json_encode(
			[
				'links' => [
					[ 'href' => 'https://whodat.theeventscalendar.com/referral-data' ],
					[ 'href' => $signup_url ],
				],
			]
		);
	}

	/**
	 * Pulls the connect button's href out of the rendered markup by id, not by position.
	 */
	protected function get_connect_href( string $html ): ?string {
		if ( ! preg_match( '/<a\b[^>]*\bid="connect_to_paypal"[^>]*>/', $html, $anchor ) ) {
			return null;
		}

		if ( ! preg_match( '/\bhref="([^"]*)"/', $anchor[0], $href ) ) {
			return null;
		}

		return html_entity_decode( $href[1] );
	}

	/**
	 * A bare `&displayMode=minibrowser` href has no scheme and no leading `/`, so the browser resolves
	 * it against /wp-admin/.
	 */
	public function test_failed_signup_request_renders_no_link(): void {
		$this->stub_whodat( 400, '{"message":"Bad Request"}' );

		$html = tribe( Signup::class )->get_link_html();

		$this->assertStringNotContainsString( 'href="&', $html );
		$this->assertStringNotContainsString( 'connect_to_paypal', $html );
		$this->assertNull( $this->get_connect_href( $html ) );
		$this->assertStringContainsString( 'PayPal connection unavailable', $html );
	}

	public function test_failed_signup_request_is_logged(): void {
		$this->stub_whodat( 400, '{"message":"Bad Request"}' );

		tribe( Signup::class )->get_link_html();

		$this->assertCount( 1, $this->logs );
		$this->assertSame( 'error', $this->logs[0][0] );
		$this->assertStringContainsString( 'PayPal signup URL could not be generated', $this->logs[0][1] );
	}

	/**
	 * The cached URL carries no country, so a failed refresh must not leave the previous country's URL
	 * behind for the next page load to serve as if nothing went wrong.
	 */
	public function test_failed_refresh_discards_the_cached_url(): void {
		$this->stub_whodat( 200, $this->whodat_signup_body( 'https://www.paypal.com/onboard?token=US' ) );

		$signup = tribe( Signup::class );

		$this->assertSame( 'https://www.paypal.com/onboard?token=US', $signup->generate_url( 'US' ) );

		// The seller switches country and the request fails.
		remove_all_filters( 'pre_http_request' );
		$this->stub_whodat( 400, '{"message":"Bad Request"}' );

		$this->assertFalse( $signup->generate_url( 'DE', true ) );

		// This is the "reload this page to try again" the notice asks for.
		$this->assertFalse( $signup->generate_url( 'DE' ), 'The stale URL should not come back from cache.' );
	}

	public function test_successful_signup_request_renders_an_absolute_link(): void {
		$this->stub_whodat(
			200,
			$this->whodat_signup_body( 'https://www.sandbox.paypal.com/merchantsignup/partner/onboardingentry?token=ABC123' )
		);

		$html = tribe( Signup::class )->get_link_html();

		$this->assertSame(
			'https://www.sandbox.paypal.com/merchantsignup/partner/onboardingentry?token=ABC123&displayMode=minibrowser',
			$this->get_connect_href( $html )
		);

		// The notice ships hidden so the refresh handler can reveal it without a page load.
		$this->assertStringContainsString( 'PayPal connection unavailable', $html );
		$this->assertStringContainsString( 'display: none;', $html );

		$this->assertCount( 0, $this->logs, 'A successful request should log nothing.' );
	}

	/**
	 * `add_query_arg()` prepends `?` when the signup URL carries no query string of its own.
	 */
	public function test_signup_url_without_a_query_string_gets_a_question_mark(): void {
		$this->stub_whodat( 200, $this->whodat_signup_body( 'https://www.paypal.com/merchantsignup/partner/entry' ) );

		$html = tribe( Signup::class )->get_link_html();

		$this->assertSame(
			'https://www.paypal.com/merchantsignup/partner/entry?displayMode=minibrowser',
			$this->get_connect_href( $html )
		);
	}

	/**
	 * The AJAX response feeds the button's href directly, with no escaping on the JS side.
	 */
	public function test_refresh_ajax_returns_an_escaped_url_with_the_display_mode(): void {
		$this->stub_whodat( 200, $this->whodat_signup_body( 'https://www.paypal.com/onboard?token=ABC123' ) );

		$_GET['nonce']        = wp_create_nonce( 'tec-tickets-commerce-gateway-paypal-refresh-connect-url' );
		$_GET['country_code'] = 'CA';

		// Route wp_send_json through wp_die so the no-op handler below returns instead of exiting.
		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter(
			'wp_die_ajax_handler',
			static function () {
				return static function () {};
			}
		);

		ob_start();
		tribe( Signup::class )->ajax_refresh_connect_url();
		$payload = json_decode( ob_get_clean(), true );

		unset( $_GET['nonce'], $_GET['country_code'] );

		$this->assertTrue( $payload['success'] );
		$this->assertSame(
			'https://www.paypal.com/onboard?token=ABC123&displayMode=minibrowser',
			$payload['data']['new_url']
		);
	}

	/**
	 * `generate_url()` rotates the signup hash before it calls WhoDat, so a country change invalidates
	 * an onboarding session opened against the previous hash whether the refresh succeeds or fails.
	 * `On_Boarding_Endpoint::handle_signup_redirect()` bails on that hash check before it reads the
	 * referral data link, so dropping the whole payload costs nothing that keeping `links[0]` would save.
	 */
	public function test_failed_refresh_invalidates_an_open_onboarding_session(): void {
		$responses = [
			[ 200, $this->whodat_signup_body( 'https://www.paypal.com/onboard?token=ABC123' ) ],
			[ 400, '{"message":"Bad Request"}' ],
		];

		add_filter(
			'pre_http_request',
			static function ( $pre, $args, $url ) use ( &$responses ) {
				if ( false === strpos( $url, '/commerce/v1/paypal/' ) ) {
					return $pre;
				}

				[ $code, $body ] = array_shift( $responses ) ?? [ 400, '{}' ];

				return [
					'headers'  => [],
					'body'     => $body,
					'response' => [ 'code' => $code, 'message' => '' ],
					'cookies'  => [],
					'filename' => null,
				];
			},
			10,
			3
		);

		$signup = tribe( Signup::class );

		$this->assertNotFalse( $signup->generate_url( 'US' ) );
		$hash_onboarding_opened_with = $signup->get_transient_hash();
		$this->assertNotEmpty( $hash_onboarding_opened_with );
		$this->assertNotFalse( $signup->get_referral_data_link() );

		// The seller changes country and the refresh fails while PayPal onboarding is still open.
		$this->assertFalse( $signup->generate_url( 'CA', true ) );

		$this->assertNotSame(
			$hash_onboarding_opened_with,
			$signup->get_transient_hash(),
			'A country change must not leave the hash an open onboarding session started with.'
		);
	}
}
