<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal;

use Codeception\TestCase\WPTestCase;

class SignupTest extends WPTestCase {

	/**
	 * @before
	 * @after
	 */
	public function clear_signup_transients(): void {
		$signup = tribe( Signup::class );
		$signup->delete_transient_data();
		$signup->delete_transient_hash();
	}

	/**
	 * Answers every WhoDat request with the given status code and body.
	 */
	protected function stub_whodat( int $status_code, string $body ): void {
		add_filter(
			'pre_http_request',
			static function ( $pre, $args, $url ) use ( $status_code, $body ) {
				if ( false === strpos( $url, 'whodat.theeventscalendar.com' ) ) {
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
	 * A signup URL that fails to generate used to render `href="&displayMode=minibrowser"`, which the
	 * browser resolves against /wp-admin/.
	 */
	public function test_failed_signup_request_renders_no_link(): void {
		$this->stub_whodat( 400, '{"message":"Bad Request"}' );

		$html = tribe( Signup::class )->get_link_html();

		$this->assertStringNotContainsString( 'href=', $html );
		$this->assertStringNotContainsString( 'connect_to_paypal', $html );
		$this->assertStringContainsString( 'PayPal connection unavailable', $html );
		$this->assertStringNotContainsString( 'display: none;', $html, 'The notice should be visible.' );
	}

	public function test_failed_signup_request_is_logged(): void {
		$this->stub_whodat( 400, '{"message":"Bad Request"}' );

		$logs = [];
		add_action(
			'tribe_log',
			static function ( $level, $src, $context ) use ( &$logs ) {
				$logs[] = [ $level, implode( ' ', (array) $context ) ];
			},
			10,
			3
		);

		tribe( Signup::class )->get_link_html();

		$this->assertCount( 1, $logs );
		$this->assertSame( 'error', $logs[0][0] );
		$this->assertStringContainsString( 'WhoDat responded with HTTP 400', $logs[0][1] );
	}

	public function test_successful_signup_request_renders_an_absolute_link(): void {
		$this->stub_whodat(
			200,
			wp_json_encode(
				[
					'links' => [
						[ 'href' => 'https://whodat.theeventscalendar.com/referral-data' ],
						[ 'href' => 'https://www.sandbox.paypal.com/merchantsignup/partner/onboardingentry?token=ABC123' ],
					],
				]
			)
		);

		$html = tribe( Signup::class )->get_link_html();

		preg_match( '/href="([^"]*)"/', $html, $matches );

		$this->assertNotEmpty( $matches, 'The connect link should be rendered.' );

		$href = html_entity_decode( $matches[1] );

		$this->assertSame(
			'https://www.sandbox.paypal.com/merchantsignup/partner/onboardingentry?token=ABC123&displayMode=minibrowser',
			$href
		);

		// The notice ships hidden so the refresh handler can reveal it without a page load.
		$this->assertStringContainsString( 'PayPal connection unavailable', $html );
		$this->assertStringContainsString( 'display: none;', $html );
	}
}
