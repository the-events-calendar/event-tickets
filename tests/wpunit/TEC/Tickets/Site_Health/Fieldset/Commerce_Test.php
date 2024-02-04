<?php

namespace TEC\Tickets\Site_Health\Fieldset;

use \Codeception\TestCase\WPTestCase;

use TEC\Tickets\Commerce\Gateways\Manager;
use TEC\Tickets\Settings as Tickets_Settings;
use TEC\Tickets\Commerce\Gateways\PayPal\Gateway as PayPal_Gateway;
use TEC\Tickets\Commerce\Gateways\Stripe\Gateway as Stripe_Gateway;
use TEC\Tickets\Commerce\Settings as Commerce_Settings;
use Tribe\Tests\Traits\With_Uopz;

/**
 * Class Commerce_Test
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Site_Health\Fieldset
 */
class Commerce_Test extends WPTestCase {
	use With_Uopz;

	/**
	 * @test
	 */
	public function should_be_able_to_instantiate(): void {
		$fieldset = new Commerce();
		$this->assertInstanceOf( Commerce::class, $fieldset );
	}

	/**
	 * @before
	 */
	public function unset_tc_env_var(): void {
		putenv( 'TEC_TICKETS_COMMERCE' );
	}

	/**
	 * @after
	 */
	public function reset_tc_env_var(): void {
		putenv( 'TEC_TICKETS_COMMERCE=0' );
	}

	/**
	 * @test
	 * @covers \TEC\Tickets\Site_Health\Fieldset\Commerce::is_tickets_commerce_enabled
	 */
	public function should_return_yes_if_tickets_commerce_is_enabled(): void {
		$fieldset = new Commerce();
		putenv( 'TEC_TICKETS_COMMERCE' );

		add_filter( 'tec_tickets_commerce_is_enabled', '__return_true' );
		$this->assertEquals( 'yes', $fieldset->is_tickets_commerce_enabled() );
		remove_filter( 'tec_tickets_commerce_is_enabled', '__return_true' );

		tribe_update_option( Tickets_Settings::$tickets_commerce_enabled, true );
		$this->assertEquals( 'yes', $fieldset->is_tickets_commerce_enabled() );
		tribe_remove_option( Tickets_Settings::$tickets_commerce_enabled );
	}

	/**
	 * @test
	 * @covers \TEC\Tickets\Site_Health\Fieldset\Commerce::is_tickets_commerce_enabled
	 */
	public function should_return_no_if_tickets_commerce_is_not_enabled(): void {
		$fieldset = new Commerce();

		add_filter( 'tec_tickets_commerce_is_enabled', '__return_false' );
		$this->assertEquals( 'no', $fieldset->is_tickets_commerce_enabled() );
		remove_filter( 'tec_tickets_commerce_is_enabled', '__return_false' );

		tribe_update_option( Tickets_Settings::$tickets_commerce_enabled, false );
		$this->assertEquals( 'no', $fieldset->is_tickets_commerce_enabled() );
		tribe_remove_option( Tickets_Settings::$tickets_commerce_enabled );
	}

	/**
	 * @test
	 * @covers \TEC\Tickets\Site_Health\Fieldset\Commerce::is_tickets_commerce_sandbox_mode
	 */
	public function should_return_yes_if_tickets_commerce_sandbox_is_enabled(): void {
		$fieldset = new Commerce();

		add_filter( 'tec_tickets_commerce_is_sandbox_mode', '__return_true' );
		$this->assertEquals( 'yes', $fieldset->is_tickets_commerce_sandbox_mode() );
		remove_filter( 'tec_tickets_commerce_is_sandbox_mode', '__return_true' );

		tribe_update_option( Commerce_Settings::$option_sandbox, true );
		$this->assertEquals( 'yes', $fieldset->is_tickets_commerce_sandbox_mode() );
		tribe_remove_option( Commerce_Settings::$option_sandbox );
	}

	/**
	 * @test
	 * @covers \TEC\Tickets\Site_Health\Fieldset\Commerce::is_tickets_commerce_sandbox_mode
	 */
	public function should_return_no_if_tickets_commerce_sandbox_is_not_enabled(): void {
		$fieldset = new Commerce();

		add_filter( 'tec_tickets_commerce_is_sandbox_mode', '__return_false' );
		$this->assertEquals( 'no', $fieldset->is_tickets_commerce_sandbox_mode() );
		remove_filter( 'tec_tickets_commerce_is_sandbox_mode', '__return_false' );

		tribe_update_option( Commerce_Settings::$option_sandbox, false );
		$this->assertEquals( 'no', $fieldset->is_tickets_commerce_sandbox_mode() );
		tribe_remove_option( Commerce_Settings::$option_sandbox );
	}

	/**
	 * @test
	 * @covers \TEC\Tickets\Site_Health\Fieldset\Commerce::is_tribe_commerce_available
	 */
	public function should_return_yes_if_tribe_commerce_is_available(): void {
		$fieldset = new Commerce();

		add_filter( 'tec_tickets_commerce_is_sandbox_mode', '__return_true' );
		$this->assertEquals( 'yes', $fieldset->is_tickets_commerce_sandbox_mode() );
		remove_filter( 'tec_tickets_commerce_is_sandbox_mode', '__return_true' );
	}

	/**
	 * @test
	 * @covers \TEC\Tickets\Site_Health\Fieldset\Commerce::is_tribe_commerce_available
	 */
	public function should_return_no_if_tribe_commerce_is_not_available(): void {
		$fieldset = new Commerce();

		add_filter( 'tec_tribe_commerce_is_available', '__return_false' );
		$this->assertEquals( 'no', $fieldset->is_tribe_commerce_available() );
		remove_filter( 'tec_tribe_commerce_is_available', '__return_false' );
	}

	/**
	 * @test
	 * @covers \TEC\Tickets\Site_Health\Fieldset\Commerce::is_tc_stripe_active
	 */
	public function should_return_yes_if_tickets_commerce_stripe_is_active(): void {
		$fieldset = new Commerce();
		$gateway = tribe( Manager::class )->get_gateway_by_key( Stripe_Gateway::get_key() );

		add_filter( 'tec_tickets_commerce_is_enabled', '__return_true' );
		$this->set_class_fn_return( $gateway, 'is_active', true );
		$this->set_class_fn_return( $gateway, 'is_enabled', true );

		$this->assertEquals( 'yes', $fieldset->is_tc_stripe_active() );

		remove_filter( 'tec_tickets_commerce_is_enabled', '__return_true' );
	}

	/**
	 * @test
	 * @covers \TEC\Tickets\Site_Health\Fieldset\Commerce::is_tc_stripe_active
	 */
	public function should_return_no_if_tickets_commerce_stripe_is_not_active(): void {
		$fieldset = new Commerce();
		$gateway = tribe( Manager::class )->get_gateway_by_key( Stripe_Gateway::get_key() );

		add_filter( 'tec_tickets_commerce_is_enabled', '__return_false' );
		$this->set_class_fn_return( $gateway, 'is_active', false );
		$this->set_class_fn_return( $gateway, 'is_enabled', false );

		$this->assertEquals( 'no', $fieldset->is_tc_stripe_active() );

		remove_filter( 'tec_tickets_commerce_is_enabled', '__return_false' );
	}


	/**
	 * @test
	 * @covers \TEC\Tickets\Site_Health\Fieldset\Commerce::is_tc_paypal_active
	 */
	public function should_return_yes_if_tickets_commerce_paypal_is_active(): void {
		$fieldset = new Commerce();
		$gateway = tribe( Manager::class )->get_gateway_by_key( Paypal_Gateway::get_key() );

		add_filter( 'tec_tickets_commerce_is_enabled', '__return_true' );
		$this->set_class_fn_return( $gateway, 'is_active', true );
		$this->set_class_fn_return( $gateway, 'is_enabled', true );

		$this->assertEquals( 'yes', $fieldset->is_tc_paypal_active() );

		remove_filter( 'tec_tickets_commerce_is_enabled', '__return_true' );
	}

	/**
	 * @test
	 * @covers \TEC\Tickets\Site_Health\Fieldset\Commerce::is_tc_paypal_active
	 */
	public function should_return_no_if_tickets_commerce_paypal_is_not_active(): void {
		$fieldset = new Commerce();
		$gateway = tribe( Manager::class )->get_gateway_by_key( Paypal_Gateway::get_key() );

		add_filter( 'tec_tickets_commerce_is_enabled', '__return_false' );
		$this->set_class_fn_return( $gateway, 'is_active', false );
		$this->set_class_fn_return( $gateway, 'is_enabled', false );

		$this->assertEquals( 'no', $fieldset->is_tc_paypal_active() );

		remove_filter( 'tec_tickets_commerce_is_enabled', '__return_false' );
	}

}