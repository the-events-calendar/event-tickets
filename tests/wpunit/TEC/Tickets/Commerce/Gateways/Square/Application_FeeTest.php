<?php
/**
 * Test for the Square Application_Fee class.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */

namespace TEC\Tickets\Commerce\Gateways\Square;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Settings;
use TEC\Tickets\Commerce\Utils\Value;
use Tribe\Tests\Traits\With_Uopz;

/**
 * Class Application_FeeTest
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */
class Application_FeeTest extends WPTestCase {
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
	 * Test that no fee is applied when plugin is licensed.
	 *
	 * @test
	 */
	public function it_should_return_zero_fee_when_plugin_is_licensed(): void {
		// Mock the plugin as licensed.
		$this->set_class_fn_return( Settings::class, 'is_licensed_plugin', true );

		$value = Value::create( 100.00 );
		$fee   = Application_Fee::calculate( $value );

		$this->assertEquals( 0, $fee->get_decimal() );
	}

	/**
	 * Test that no fee is applied for non-US merchants even when unlicensed.
	 *
	 * @test
	 */
	public function it_should_return_zero_fee_for_non_us_merchants(): void {
		// Mock the plugin as unlicensed.
		$this->set_class_fn_return( Settings::class, 'is_licensed_plugin', false );

		// Set merchant country to Canada.
		$this->merchant->save_signup_data( [
			'merchant_id'      => 'test_merchant_id',
			'access_token'     => 'test_access_token',
			'merchant_country' => 'CA',
		] );

		$value = Value::create( 100.00 );
		$fee   = Application_Fee::calculate( $value );

		$this->assertEquals( 0, $fee->get_decimal() );
	}

	/**
	 * Test that 2% fee is applied for US merchants when plugin is unlicensed.
	 *
	 * @test
	 */
	public function it_should_apply_2_percent_fee_for_us_merchants_when_unlicensed(): void {
		// Mock the plugin as unlicensed.
		$this->set_class_fn_return( Settings::class, 'is_licensed_plugin', false );

		// Set merchant country to US.
		$this->merchant->save_signup_data( [
			'merchant_id'      => 'test_merchant_id',
			'access_token'     => 'test_access_token',
			'merchant_country' => 'US',
		] );

		$value = Value::create( 100.00 );
		$fee   = Application_Fee::calculate( $value );

		// 2% of $100.00 = $2.00.
		$this->assertEquals( 2.00, $fee->get_decimal() );
	}

	/**
	 * Test that no fee is applied when merchant country is unknown.
	 *
	 * @test
	 */
	public function it_should_return_zero_fee_when_merchant_country_unknown(): void {
		// Mock the plugin as unlicensed.
		$this->set_class_fn_return( Settings::class, 'is_licensed_plugin', false );

		// Don't set any merchant data (country will be empty).
		$value = Value::create( 100.00 );
		$fee   = Application_Fee::calculate( $value );

		$this->assertEquals( 0, $fee->get_decimal() );
	}

	/**
	 * Test fee calculation with different amounts for US merchants.
	 *
	 * @test
	 * @dataProvider fee_calculation_data_provider
	 */
	public function it_should_calculate_correct_fee_for_different_amounts( float $amount, float $expected_fee ): void {
		// Mock the plugin as unlicensed.
		$this->set_class_fn_return( Settings::class, 'is_licensed_plugin', false );

		// Set merchant country to US.
		$this->merchant->save_signup_data( [
			'merchant_id'      => 'test_merchant_id',
			'access_token'     => 'test_access_token',
			'merchant_country' => 'US',
		] );

		$value = Value::create( $amount );
		$fee   = Application_Fee::calculate( $value );

		$this->assertEquals( $expected_fee, $fee->get_decimal() );
	}

	/**
	 * Data provider for fee calculation tests.
	 *
	 * @return array
	 */
	public function fee_calculation_data_provider(): array {
		return [
			'$10.00 order'   => [ 10.00, 0.20 ],   // 2% of $10.00 = $0.20
			'$50.00 order'   => [ 50.00, 1.00 ],   // 2% of $50.00 = $1.00
			'$100.00 order'  => [ 100.00, 2.00 ],  // 2% of $100.00 = $2.00
			'$250.00 order'  => [ 250.00, 5.00 ],  // 2% of $250.00 = $5.00
			'$1000.00 order' => [ 1000.00, 20.00 ], // 2% of $1000.00 = $20.00
			'$0.00 order'    => [ 0.00, 0.00 ],    // 2% of $0.00 = $0.00
		];
	}

	/**
	 * Test that the fixed fee percentage is correct.
	 *
	 * @test
	 */
	public function it_should_have_correct_fixed_fee_percentage(): void {
		$this->assertEquals( 0.02, Application_Fee::FIXED_FEE );
	}
}
