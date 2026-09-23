<?php

namespace TEC\Tickets\Commerce\Order_Items;

use Closure;
use Generator;
use TEC\Common\StellarWP\DB\DB;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Order_Items\Tables\Order_Items;
use Tribe\Tests\Traits\With_Uopz;

class Controller_Test extends Controller_Test_Case {
	use With_Uopz;

	protected string $controller_class = Controller::class;

	/**
	 * @after
	 */
	public function unset_disabled_env_var(): void {
		putenv( Controller::DISABLED );
	}

	public function test_it_is_active_by_default(): void {
		$controller = $this->make_controller();

		$this->assertTrue( $controller->is_active() );

		$controller->register();

		$this->assertTrue( $controller::is_registered() );
	}

	public function disabled_provider(): Generator {
		yield 'constant' => [
			function () {
				$this->set_const_value( Controller::DISABLED, true );
			},
		];

		yield 'environment variable' => [
			function () {
				putenv( Controller::DISABLED . '=1' );
			},
		];

		yield 'filter' => [
			function () {
				add_filter( 'tec_tickets_commerce_order_items_active', '__return_false' );
			},
		];
	}

	/**
	 * @dataProvider disabled_provider
	 */
	public function test_it_can_be_switched_off( Closure $switch_off ): void {
		$switch_off->call( $this );
		$controller = $this->make_controller();

		$this->assertFalse( $controller->is_active() );

		$controller->register();

		$this->assertFalse( $controller::is_registered() );
	}

	public function test_unregister_keeps_the_table_and_its_rows(): void {
		$controller = $this->make_controller();
		$controller->register();
		Order_Items::insert( [ 'order_id' => 1, 'type' => 'ticket', 'ticket_id' => 0, 'modifier_id' => 0, 'purchase_rule_id' => 0, 'name' => 'GA', 'currency' => 'USD', 'quantity' => 1, 'price' => 1050, 'sub_total' => 1050, 'created_at' => gmdate( 'Y-m-d H:i:s' ) ] );

		$controller->unregister();

		$this->assertSame( 1, Order_Items::get_total_items() );
	}

	/**
	 * @after
	 */
	public function empty_table(): void {
		DB::query( DB::prepare( 'DELETE FROM %i', Order_Items::table_name() ) );
	}
}
