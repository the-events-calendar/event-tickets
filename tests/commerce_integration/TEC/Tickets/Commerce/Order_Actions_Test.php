<?php

namespace TEC\Tickets\Commerce;

use Generator;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Pending;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

class Order_Actions_Test extends Controller_Test_Case {
	use Order_Maker;
	use Ticket_Maker;

	protected string $controller_class = Order_Actions::class;

	/**
	 * @var array<int,array{0: mixed, 1: mixed}>
	 */
	private array $calls = [];

	/**
	 * @before
	 */
	public function listen(): void {
		$this->calls = [];
		add_action(
			'tec_tickets_commerce_order_updated',
			function ( $order_id, $items ) {
				$this->calls[] = [ $order_id, $items ];
			},
			10,
			2
		);
	}

	public function switch_provider(): Generator {
		yield 'switch on' => [ true ];
		yield 'switch off' => [ false ];
	}

	/**
	 * @dataProvider switch_provider
	 */
	public function test_it_fires_once_with_the_new_items_only_when_an_order_items_change( bool $active ): void {
		add_filter( 'tec_tickets_commerce_order_items_active', $active ? '__return_true' : '__return_false' );
		$this->make_controller()->register();
		$event_id = static::factory()->post->create( [ 'post_type' => 'page' ] );
		$order    = $this->create_order( [ $this->create_tc_ticket( $event_id, 10 ) => 1, $this->create_tc_ticket( $event_id, 20 ) => 2 ], [ 'order_status' => Pending::SLUG ] );
		$items    = get_post_meta( $order->ID, Order::$items_meta_key, true );
		$this->save_items( $order->ID, $items );
		$this->assertSame( [], $this->calls, 'Neither creating an order nor saving the same items is an update.' );
		unset( $items[ array_key_last( $items ) ] );

		$this->save_items( $order->ID, $items );
		tribe( Order::class )->modify_status( $order->ID, Completed::SLUG );

		$this->assertSame( tribe( Completed::class )->get_wp_slug(), get_post_status( $order->ID ) );
		$this->assertSame( [ [ $order->ID, $items ] ], $this->calls );
	}

	public function test_it_ignores_the_items_meta_key_on_other_post_types(): void {
		$this->make_controller()->register();
		$post_id = static::factory()->post->create();
		add_post_meta( $post_id, Order::$items_meta_key, [ 'a' ] );

		update_post_meta( $post_id, Order::$items_meta_key, [ 'b' ] );

		$this->assertSame( [], $this->calls );
	}

	private function save_items( int $order_id, array $items ): void {
		tec_tc_orders()->by_args( [ 'id' => $order_id, 'status' => 'any' ] )->set_args( [ 'items' => $items ] )->save();
	}
}
