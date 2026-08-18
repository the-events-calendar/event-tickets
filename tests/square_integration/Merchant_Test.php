<?php

namespace TEC\Tickets\Commerce\Gateways\Square;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Gateways\Stripe\Merchant as Stripe_Merchant;

class Merchant_Test extends WPTestCase {
	/**
	 * @after
	 */
	public function restore_merchant_data(): void {
		$merchant = tribe( Merchant::class );
		$merchant->delete_token_status();
		$merchant->save_signup_data( tec_tickets_tests_get_fake_merchant_data() );
	}

	/**
	 * Replaces the stored signup data, keeping the rest of the fixture intact.
	 *
	 * @param array    $overrides Values to override.
	 * @param string[] $remove    Keys to drop entirely.
	 */
	protected function set_signup_data( array $overrides = [], array $remove = [] ): Merchant {
		$merchant = tribe( Merchant::class );
		$data     = array_merge( tec_tickets_tests_get_fake_merchant_data(), $overrides );

		foreach ( $remove as $key ) {
			unset( $data[ $key ] );
		}

		$merchant->save_signup_data( $data );

		return $merchant;
	}

	/**
	 * @test
	 */
	public function it_should_read_a_utc_expiration(): void {
		$merchant = $this->set_signup_data( [ 'expires_at' => '2030-06-01T10:00:00Z' ] );

		$this->assertSame( 1906538400, $merchant->get_token_expiration()->getTimestamp() );
	}

	/**
	 * @test
	 */
	public function it_should_honour_an_explicit_offset_in_the_expiration(): void {
		$merchant = $this->set_signup_data( [ 'expires_at' => '2030-06-01T10:00:00+02:00' ] );

		$this->assertSame( 1906531200, $merchant->get_token_expiration()->getTimestamp() );
	}

	/**
	 * The site timezone must not decide whether the token is still good.
	 *
	 * @test
	 */
	public function it_should_compare_expirations_independently_of_the_site_timezone(): void {
		$original = get_option( 'timezone_string' );
		update_option( 'timezone_string', 'Pacific/Kiritimati' );

		try {
			$merchant = $this->set_signup_data( [ 'expires_at' => gmdate( 'Y-m-d\TH:i:s\Z', time() - HOUR_IN_SECONDS ) ] );

			$this->assertTrue( $merchant->is_token_expired() );
		} finally {
			update_option( 'timezone_string', $original );
		}
	}

	/**
	 * @test
	 */
	public function it_should_report_no_expiration_for_unusable_values(): void {
		foreach ( [ '', 'not-a-date', [ 'nested' ] ] as $value ) {
			$merchant = $this->set_signup_data( [ 'expires_at' => $value ] );

			$this->assertNull( $merchant->get_token_expiration() );
		}

		$merchant = $this->set_signup_data( [], [ 'expires_at' ] );

		$this->assertNull( $merchant->get_token_expiration() );
	}

	/**
	 * A connection made before the expiration was tracked must keep working.
	 *
	 * @test
	 */
	public function it_should_not_treat_an_unknown_expiration_as_expired(): void {
		$merchant = $this->set_signup_data( [], [ 'expires_at' ] );

		$this->assertFalse( $merchant->is_token_expired() );
		$this->assertTrue( $merchant->is_connected() );
	}

	/**
	 * @test
	 */
	public function it_should_report_an_expiring_token(): void {
		$merchant = $this->set_signup_data( [ 'expires_at' => gmdate( 'Y-m-d\TH:i:s\Z', time() + 2 * DAY_IN_SECONDS ) ] );

		$this->assertFalse( $merchant->is_token_expired() );
		$this->assertTrue( $merchant->is_token_expiring_within( 3 * DAY_IN_SECONDS ) );
		$this->assertFalse( $merchant->is_token_expiring_within( DAY_IN_SECONDS ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_be_connected_while_the_token_is_invalid(): void {
		$merchant = tribe( Merchant::class );

		$this->assertTrue( $merchant->is_connected() );

		$merchant->update_token_status( [ 'invalid_at' => '2026-01-01 00:00:00' ] );

		$this->assertTrue( $merchant->is_token_invalid() );
		$this->assertFalse( $merchant->is_connected() );
		$this->assertFalse( $merchant->is_active() );

		$merchant->delete_token_status();

		$this->assertTrue( $merchant->is_connected() );
	}

	/**
	 * The Square token status must never touch the options Stripe stores its own state in.
	 *
	 * @test
	 */
	public function it_should_not_share_state_with_stripe(): void {
		$merchant = tribe( Merchant::class );

		$merchant->update_token_status( [ 'invalid_at' => '2026-01-01 00:00:00' ] );

		$this->assertNotSame( Stripe_Merchant::$merchant_unauthorized_option_key, $merchant->get_token_status_option_key() );
		$this->assertNotSame( Stripe_Merchant::$merchant_deauthorized_option_key, $merchant->get_token_status_option_key() );
		$this->assertEmpty( get_option( Stripe_Merchant::$merchant_unauthorized_option_key ) );
		$this->assertEmpty( get_option( Stripe_Merchant::$merchant_deauthorized_option_key ) );
	}

	/**
	 * @test
	 */
	public function it_should_scope_the_token_status_to_the_mode(): void {
		$merchant = tribe( Merchant::class );
		$original = $merchant->get_mode();

		try {
			$merchant->set_mode( 'live' );
			$live = $merchant->get_token_status_option_key();

			$merchant->set_mode( 'sandbox' );
			$sandbox = $merchant->get_token_status_option_key();

			$this->assertNotSame( $live, $sandbox );
		} finally {
			$merchant->set_mode( $original );
		}
	}

	/**
	 * @test
	 */
	public function it_should_store_refreshed_tokens(): void {
		$merchant = tribe( Merchant::class );
		$expires  = gmdate( 'Y-m-d\TH:i:s\Z', time() + 30 * DAY_IN_SECONDS );

		$this->assertTrue(
			$merchant->save_refreshed_tokens(
				[
					'access_token'  => 'refreshed-access-token',
					'refresh_token' => 'refreshed-refresh-token',
					'expires_at'    => $expires,
				]
			)
		);

		$this->assertSame( 'refreshed-access-token', $merchant->get_access_token() );
		$this->assertSame( 'refreshed-refresh-token', $merchant->get_refresh_token() );
		$this->assertSame( $expires, get_option( $merchant->get_signup_data_key() )['expires_at'] );
		$this->assertNotEmpty( get_option( $merchant->get_signup_data_key() )['refreshed_at'] );
	}

	/**
	 * @test
	 */
	public function it_should_keep_values_the_refresh_response_omitted(): void {
		$merchant = tribe( Merchant::class );
		$fixture  = tec_tickets_tests_get_fake_merchant_data();

		$merchant->save_refreshed_tokens(
			[
				'access_token' => 'refreshed-access-token',
				'expires_at'   => gmdate( 'Y-m-d\TH:i:s\Z', time() + 30 * DAY_IN_SECONDS ),
			]
		);

		$this->assertSame( $fixture['refresh_token'], $merchant->get_refresh_token() );
		$this->assertSame( $fixture['whodat_signature'], $merchant->get_whodat_signature() );
		$this->assertSame( $fixture['merchant_id'], $merchant->get_merchant_id() );
	}

	/**
	 * A refresh that does not report an expiration leaves it unknown, never the previous one.
	 *
	 * @test
	 */
	public function it_should_blank_an_unusable_expiration_on_refresh(): void {
		$merchant = $this->set_signup_data( [ 'expires_at' => gmdate( 'Y-m-d\TH:i:s\Z', time() - HOUR_IN_SECONDS ) ] );

		$merchant->save_refreshed_tokens( [ 'access_token' => 'refreshed-access-token' ] );

		$this->assertSame( '', get_option( $merchant->get_signup_data_key() )['expires_at'] );
		$this->assertNull( $merchant->get_token_expiration() );
		$this->assertFalse( $merchant->is_token_expired() );
	}

	/**
	 * @test
	 */
	public function it_should_reject_a_refresh_response_without_an_access_token(): void {
		$merchant = tribe( Merchant::class );

		$this->assertFalse( $merchant->save_refreshed_tokens( [] ) );
		$this->assertFalse( $merchant->save_refreshed_tokens( [ 'access_token' => '' ] ) );
		$this->assertSame( tec_tickets_tests_get_fake_merchant_data()['access_token'], $merchant->get_access_token() );
	}

	/**
	 * @test
	 */
	public function it_should_clear_the_token_status_on_reconnect_and_disconnect(): void {
		$merchant = tribe( Merchant::class );

		$merchant->update_token_status( [ 'invalid_at' => '2026-01-01 00:00:00' ] );
		$merchant->save_signup_data( tec_tickets_tests_get_fake_merchant_data() );

		$this->assertFalse( $merchant->is_token_invalid() );

		$merchant->update_token_status( [ 'invalid_at' => '2026-01-01 00:00:00' ] );
		$merchant->delete_signup_data();

		$this->assertFalse( $merchant->is_token_invalid() );
	}
}
