<?php

namespace TEC\Tickets\Commerce\Order_Items;

use Generator;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Admin_Tables\Orders;
use TEC\Tickets\Commerce\Admin_Tables\Orders_Table;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Order_Items\Tables\Order_Items as Order_Items_Table;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use WP_Post;
use WP_Screen;

class Orders_Lists_Test extends Controller_Test_Case {
	use Order_Maker;
	use Ticket_Maker;

	protected string $controller_class = Controller::class;

	protected array $sub_controller_classes = [ Writer::class, Reader::class ];

	/**
	 * @after
	 */
	public function empty_table(): void {
		( new Order_Items_Table() )->empty_table();
	}

	public function column_provider(): Generator {
		$purchased = static function ( WP_Post $order ): string {
			return ( new Orders() )->column_purchased( $order );
		};
		$items     = static function ( WP_Post $order ): string {
			$GLOBALS['current_screen'] = WP_Screen::get( 'edit-' . Order::POSTTYPE );

			return ( new Orders_Table() )->column_items( $order );
		};

		yield 'event report, all tickets present, old order' => [ $purchased, false, false, '1 - General admission|2 - VIP' ];
		yield 'event report, all tickets present, version 2 order' => [ $purchased, true, false, '1 - General admission|2 - VIP' ];
		yield 'event report, deleted ticket, old order' => [ $purchased, false, true, '1 - General admission|2 - Ticket #{{ticket_id}} (no longer exists)' ];
		yield 'event report, deleted ticket, version 2 order' => [ $purchased, true, true, '1 - General admission|2 - VIP (no longer exists)' ];
		yield 'orders list, all tickets present, old order' => [ $items, false, false, '1 General admission|2 VIP' ];
		yield 'orders list, all tickets present, version 2 order' => [ $items, true, false, '1 General admission|2 VIP' ];
		yield 'orders list, deleted ticket, old order' => [ $items, false, true, '1 General admission|2 Tickets' ];
		yield 'orders list, deleted ticket, version 2 order' => [ $items, true, true, '1 General admission|2 VIP (no longer exists)' ];
	}

	/**
	 * @dataProvider column_provider
	 */
	public function test_it_shows_every_line_of_the_order( callable $render, bool $version_2, bool $delete_ticket, string $expected ): void {
		$event_id   = static::factory()->post->create( [ 'post_type' => 'page' ] );
		$ticket_ids = [
			$this->create_tc_ticket( $event_id, 10, [ 'ticket_name' => 'General admission' ] ),
			$this->create_tc_ticket( $event_id, 20, [ 'ticket_name' => 'VIP' ] ),
		];
		if ( $version_2 ) {
			$this->register_controller();
		}
		$order = $this->create_order( [ $ticket_ids[0] => 1, $ticket_ids[1] => 2 ] );
		if ( $delete_ticket ) {
			tribe( Module::class )->delete_ticket( $event_id, $ticket_ids[1] );
		}
		$expected = implode(
			'',
			array_map(
				static function ( string $line ): string {
					return "<div class='tribe-line-item'>{$line}</div>";
				},
				explode( '|', str_replace( '{{ticket_id}}', (string) $ticket_ids[1], $expected ) )
			)
		);

		$this->assertSame( $expected, $render( tec_tc_get_order( $order->ID ) ) );
	}

	public function renderer_provider(): Generator {
		yield 'event report' => [
			static function ( WP_Post $order ): string {
				return ( new Orders() )->column_purchased( $order );
			},
		];
		yield 'orders list' => [
			static function ( WP_Post $order ): string {
				$GLOBALS['current_screen'] = WP_Screen::get( 'edit-' . Order::POSTTYPE );

				return ( new Orders_Table() )->column_items( $order );
			},
		];
	}

	/**
	 * @dataProvider renderer_provider
	 */
	public function test_it_escapes_the_stored_name( callable $render ): void {
		$event_id  = static::factory()->post->create( [ 'post_type' => 'page' ] );
		$ticket_id = $this->create_tc_ticket( $event_id, 10, [ 'ticket_name' => '<b onmouseover="alert(1)">VIP</b>' ] );
		$this->register_controller();
		$order = $this->create_order( [ $ticket_id => 1 ] );
		tribe( Module::class )->delete_ticket( $event_id, $ticket_id );

		$html = $render( tec_tc_get_order( $order->ID ) );

		$this->assertStringNotContainsString( '<b onmouseover', $html );
		$this->assertStringContainsString( esc_html( '<b onmouseover="alert(1)">VIP</b> (no longer exists)' ), $html );
	}

	/**
	 * @dataProvider renderer_provider
	 */
	public function test_it_does_not_query_the_table_when_all_tickets_exist( callable $render ): void {
		$event_id  = static::factory()->post->create( [ 'post_type' => 'page' ] );
		$ticket_id = $this->create_tc_ticket( $event_id, 10 );
		$this->register_controller();
		$order = tec_tc_get_order( $this->create_order( [ $ticket_id => 2 ] )->ID );
		$count = 0;
		$table = Order_Items_Table::table_name();
		add_filter(
			'query',
			static function ( $query ) use ( &$count, $table ) {
				if ( false !== strpos( $query, "`{$table}`" ) ) {
					$count++;
				}

				return $query;
			}
		);

		$render( $order );

		$this->assertSame( 0, $count );
	}

	private function register_controller(): void {
		add_filter( 'tec_tickets_commerce_order_items_active', '__return_true' );
		$this->make_controller()->register();
	}
}
