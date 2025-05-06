<?php

namespace TEC\Tickets\Commerce\Tests\Commerce;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Hooks;
use TEC\Tickets\Commerce\Manager;
use \TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Gateway;
use Tribe\Tests\Traits\With_Uopz;

class Hooks_Test extends WPTestCase {
	use With_Uopz;

	/**
	 * @var Hooks
	 */
	protected $hooks;

	/**
	 * @var string
	 */
	protected $settings_page_id = 'tec-tickets-settings';

	/**
	 * @before
	 */
	public function setup_hooks(): void {
		parent::setUp();
		$this->hooks = tribe( Hooks::class );
	}

	/**
	 * @test
	 * @covers \TEC\Tickets\Commerce\Hooks::maybe_trigger_process_action
	 */
	public function it_should_return_early_if_not_on_correct_page() {
		// Set up request vars
		$_GET['page']      = 'some-other-page';
		$_GET['tab']       = 'paypal';
		$_GET['tc-action'] = 'test-action';

		// Mock the action to verify it's not called
		$action_called = false;
		add_action(
			'tec_tickets_commerce_admin_process_action',
			function () use ( &$action_called ) {
				$action_called = true;
			}
		);

		$this->hooks->maybe_trigger_process_action();

		$this->assertFalse( $action_called, 'Action should not be called when not on correct page' );
	}

	/**
	 * @test
	 * @covers \TEC\Tickets\Commerce\Hooks::maybe_trigger_process_action
	 */
	public function it_should_return_early_if_no_gateway_found() {
		// Set up request vars
		$_GET['page']      = $this->settings_page_id;
		$_GET['tab']       = 'non-existent-gateway';
		$_GET['tc-action'] = 'test-action';

		// Mock the action to verify it's not called
		$action_called = false;
		add_action(
			'tec_tickets_commerce_admin_process_action',
			function () use ( &$action_called ) {
				$action_called = true;
			}
		);

		$this->hooks->maybe_trigger_process_action();

		$this->assertFalse( $action_called, 'Action should not be called when no gateway is found' );
	}

	/**
	 * @test
	 * @covers \TEC\Tickets\Commerce\Hooks::maybe_trigger_process_action
	 */
	public function it_should_return_early_if_gateway_key_doesnt_match_tab() {
		// Set up request vars
		$_GET['page']      = $this->settings_page_id;
		$_GET['tab']       = 'paypal';
		$_GET['tc-action'] = 'test-action';

		// Mock the gateway to return a different key
		$this->set_class_fn_return( Abstract_Gateway::class, 'get_key', 'different-key' );

		// Mock the action to verify it's not called
		$action_called = false;
		add_action(
			'tec_tickets_commerce_admin_process_action',
			function () use ( &$action_called ) {
				$action_called = true;
			}
		);

		$this->hooks->maybe_trigger_process_action();

		$this->assertFalse( $action_called, 'Action should not be called when gateway key does not match tab' );
	}

	/**
	 * @test
	 * @covers \TEC\Tickets\Commerce\Hooks::maybe_trigger_process_action
	 */
	public function it_should_return_early_if_no_action_present() {
		// Set up request vars
		$_GET['page'] = $this->settings_page_id;
		$_GET['tab']  = 'paypal';
		unset( $_GET['tc-action'] );

		// Mock the action to verify it's not called
		$action_called = false;
		add_action(
			'tec_tickets_commerce_admin_process_action',
			function () use ( &$action_called ) {
				$action_called = true;
			}
		);

		$this->hooks->maybe_trigger_process_action();

		$this->assertFalse( $action_called, 'Action should not be called when no action is present' );
	}

	/**
	 * @test
	 * @covers \TEC\Tickets\Commerce\Hooks::maybe_trigger_process_action
	 */
	public function it_should_trigger_actions_when_all_conditions_are_met() {
		// Set up request vars
		$_GET['page']      = $this->settings_page_id;
		$_GET['tab']       = 'paypal';
		$_GET['tc-action'] = 'test-action';

		// Mock the gateway
		$this->set_class_fn_return( Abstract_Gateway::class, 'get_key', 'paypal' );

		// Track if actions were called
		$general_action_called  = false;
		$specific_action_called = false;

		// Add action hooks to verify they're called
		add_action(
			'tec_tickets_commerce_admin_process_action',
			function ( $action ) use ( &$general_action_called ) {
				$general_action_called = true;
				$this->assertEquals( 'test-action', $action, 'Action should be passed to general hook' );
			}
		);

		add_action(
			'tec_tickets_commerce_admin_process_action:test-action',
			function () use ( &$specific_action_called ) {
				$specific_action_called = true;
			}
		);

		$this->hooks->maybe_trigger_process_action();

		$this->assertTrue( $general_action_called, 'General action should be called' );
		$this->assertTrue( $specific_action_called, 'Specific action should be called' );
	}
}
