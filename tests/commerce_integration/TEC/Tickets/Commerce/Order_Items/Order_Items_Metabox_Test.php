<?php

namespace TEC\Tickets\Commerce\Order_Items;

use Generator;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Admin\Singular_Order_Page;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Order_Items\Tables\Order_Items as Order_Items_Table;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use WP_Post;

class Order_Items_Metabox_Test extends Controller_Test_Case {
	use Order_Maker;
	use SnapshotAssertions;
	use Ticket_Maker;

	protected string $controller_class = Controller::class;

	protected array $sub_controller_classes = [ Writer::class, Reader::class ];

	/**
	 * @after
	 */
	public function empty_table(): void {
		( new Order_Items_Table() )->empty_table();
	}

	public function order_provider(): Generator {
		yield 'all tickets present' => [ false, false ];
		yield 'deleted ticket on an old order' => [ false, true ];
		yield 'deleted ticket on a version 2 order' => [ true, true ];
	}

	/**
	 * @dataProvider order_provider
	 */
	public function test_it_shows_every_line_of_the_order( bool $version_2, bool $delete_ticket ): void {
		$event_id   = static::factory()->post->create( [ 'post_type' => 'page' ] );
		$ticket_ids = [
			$this->create_tc_ticket( $event_id, 10, [ 'ticket_name' => 'General admission' ] ),
			$this->create_tc_ticket( $event_id, 20, [ 'ticket_name' => 'VIP' ] ),
		];
		// A non-default type shows whether the Type column of a missing line uses the stored type.
		update_post_meta( $ticket_ids[1], '_type', 'series_pass' );
		if ( $version_2 ) {
			$this->register_controller();
		}
		$order = $this->create_order( [ $ticket_ids[0] => 1, $ticket_ids[1] => 2 ] );
		if ( $delete_ticket ) {
			tribe( Module::class )->delete_ticket( $event_id, $ticket_ids[1] );
		}

		$html = $this->render( $order );

		$this->assertSame( $this->get_total( $order ), array_sum( $this->get_line_prices( $html ) ) );
		$this->assertMatchesHtmlSnapshot(
			strtr(
				$html,
				[
					(string) $order->ID     => '{{order_id}}',
					(string) $event_id      => '{{event_id}}',
					(string) $ticket_ids[0] => '{{ticket_id_0}}',
					(string) $ticket_ids[1] => '{{ticket_id_1}}',
				]
			)
		);
	}

	public function test_it_escapes_the_stored_name(): void {
		$event_id  = static::factory()->post->create( [ 'post_type' => 'page' ] );
		$ticket_id = $this->create_tc_ticket( $event_id, 10, [ 'ticket_name' => '<b onmouseover="alert(1)">VIP</b>' ] );
		$this->register_controller();
		$order = $this->create_order( [ $ticket_id => 1 ] );
		tribe( Module::class )->delete_ticket( $event_id, $ticket_id );

		$html = $this->render( $order );

		$this->assertStringNotContainsString( '<b onmouseover', $html );
		$this->assertStringContainsString( esc_html( '<b onmouseover="alert(1)">VIP</b> (no longer exists)' ), $html );
	}

	public function test_it_does_not_query_the_table_when_all_tickets_exist(): void {
		$event_id  = static::factory()->post->create( [ 'post_type' => 'page' ] );
		$ticket_id = $this->create_tc_ticket( $event_id, 10 );
		$this->register_controller();
		$order = $this->create_order( [ $ticket_id => 2 ] );
		// The render loads the order again, and each load reads its rows once through the reader; nothing else may.
		$loads = 0;
		add_filter(
			'tec_tickets_commerce_order_model_items',
			static function ( $items ) use ( &$loads ) {
				$loads++;

				return $items;
			}
		);
		$count = $this->count_queries_against_the_table();

		$this->render( $order );

		$this->assertGreaterThan( 0, $loads );
		$this->assertSame( $loads, $count() );
	}

	private function render( WP_Post $order ): string {
		ob_start();
		tribe( Singular_Order_Page::class )->render_order_items( $order );

		return ob_get_clean();
	}

	private function get_total( WP_Post $order ): float {
		return $this->to_amount( tribe( Order::class )->get_value( $order->ID ) );
	}

	private function get_line_prices( string $html ): array {
		preg_match_all( '/row--price-column">\s*<div class="tec-tickets-commerce-price-container"><ins><span class="tec-tickets-commerce-price">([^<]+)</', $html, $matches );

		$this->assertNotEmpty( $matches[1] );

		return array_map( [ $this, 'to_amount' ], $matches[1] );
	}

	private function to_amount( string $price ): float {
		return (float) preg_replace( '/[^0-9.]/', '', html_entity_decode( $price ) );
	}

	private function register_controller(): void {
		add_filter( 'tec_tickets_commerce_order_items_active', '__return_true' );
		$this->make_controller()->register();
	}

	private function count_queries_against_the_table(): callable {
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

		return static function () use ( &$count ): int {
			return $count;
		};
	}
}
