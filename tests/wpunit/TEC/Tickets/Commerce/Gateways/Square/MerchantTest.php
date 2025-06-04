<?php
/**
 * Test for the Square Merchant class.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */

namespace TEC\Tickets\Commerce\Gateways\Square;

use Codeception\TestCase\WPTestCase;
use Tribe\Tests\Traits\With_Uopz;

/**
 * Class MerchantTest
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */
class MerchantTest extends WPTestCase {
	use With_Uopz;

	/**
	 * @var Merchant
	 */
	protected $merchant;

	/**
	 * @before
	 */
	public function set_up_merchant(): void {
		$this->merchant = tribe( Merchant::class );
	}

	/**
	 * @after
	 */
	public function clean_up_merchant(): void {
		// Clean up any stored data after each test.
		$this->merchant->delete_signup_data();
	}

	/**
	 * Test that get_merchant_country returns empty string when no data is stored.
	 *
	 * @test
	 */
	public function it_should_return_empty_string_when_no_country_data_stored(): void {
		$country = $this->merchant->get_merchant_country();

		$this->assertEmpty( $country );
	}

	/**
	 * Test that get_merchant_country returns stored country from signup data.
	 *
	 * @test
	 */
	public function it_should_return_country_from_stored_signup_data(): void {
		// Save signup data with country.
		$this->merchant->save_signup_data( [
			'merchant_id'      => 'test_merchant_id',
			'access_token'     => 'test_access_token',
			'merchant_country' => 'US',
		] );

		$country = $this->merchant->get_merchant_country();

		$this->assertEquals( 'US', $country );
	}

	/**
	 * Test that get_merchant_country falls back to API data when signup data doesn't have country.
	 *
	 * @test
	 */
	public function it_should_fallback_to_api_data_when_signup_data_missing_country(): void {
		// Save signup data without country.
		$this->merchant->save_signup_data( [
			'merchant_id'  => 'test_merchant_id',
			'access_token' => 'test_access_token',
		] );

		// Mock the fetch_merchant_data method to return API data with country.
		$this->set_class_fn_return(
			Merchant::class,
			'fetch_merchant_data',
			[
				'merchant' => [
					'country' => 'CA',
				],
			]
		);

		$country = $this->merchant->get_merchant_country();

		$this->assertEquals( 'CA', $country );
	}

	/**
	 * Test that get_merchant_country returns empty string when both signup data and API data are missing country.
	 *
	 * @test
	 */
	public function it_should_return_empty_string_when_both_sources_missing_country(): void {
		// Save signup data without country.
		$this->merchant->save_signup_data( [
			'merchant_id'  => 'test_merchant_id',
			'access_token' => 'test_access_token',
		] );

		// Mock the fetch_merchant_data method to return API data without country.
		$this->set_class_fn_return(
			Merchant::class,
			'fetch_merchant_data',
			[
				'merchant' => [
					'business_name' => 'Test Business',
				],
			]
		);

		$country = $this->merchant->get_merchant_country();

		$this->assertEmpty( $country );
	}

	/**
	 * Test that get_merchant_country prioritizes signup data over API data.
	 *
	 * @test
	 */
	public function it_should_prioritize_signup_data_over_api_data(): void {
		// Save signup data with country.
		$this->merchant->save_signup_data( [
			'merchant_id'      => 'test_merchant_id',
			'access_token'     => 'test_access_token',
			'merchant_country' => 'US',
		] );

		// Mock the fetch_merchant_data method to return different country.
		$this->set_class_fn_return(
			Merchant::class,
			'fetch_merchant_data',
			[
				'merchant' => [
					'country' => 'CA',
				],
			]
		);

		$country = $this->merchant->get_merchant_country();

		// Should return the signup data country, not the API data country.
		$this->assertEquals( 'US', $country );
	}

	/**
	 * Test that get_merchant_country can force refresh from API.
	 *
	 * @test
	 */
	public function it_should_force_refresh_from_api_when_requested(): void {
		// Save signup data without country.
		$this->merchant->save_signup_data( [
			'merchant_id'  => 'test_merchant_id',
			'access_token' => 'test_access_token',
		] );

		// Mock the fetch_merchant_data method to return API data with country.
		$this->set_class_fn_return(
			Merchant::class,
			'fetch_merchant_data',
			[
				'merchant' => [
					'country' => 'GB',
				],
			]
		);

		$country = $this->merchant->get_merchant_country( true );

		$this->assertEquals( 'GB', $country );
	}
}
