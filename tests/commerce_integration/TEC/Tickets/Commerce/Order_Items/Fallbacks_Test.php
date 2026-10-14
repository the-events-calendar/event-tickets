<?php

namespace TEC\Tickets\Commerce\Order_Items;

use Closure;
use Generator;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use RuntimeException;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Order_Items\Repositories\Order_Items as Order_Items_Repository;
use TEC\Tickets\Commerce\Order_Items\Tables\Order_Items as Order_Items_Table;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

class Fallbacks_Test extends Controller_Test_Case {
	use Order_Maker;
	use With_Uopz;
	use Ticket_Maker;

	protected string $controller_class = Controller::class;

	/**
	 * @after
	 */
	public function empty_table(): void {
		( new Order_Items_Table() )->empty_table();
	}

	public function name_provider(): Generator {
		yield 'version 2 order: the values stored at purchase' => [
			true,
			static fn( array $item ) => $item,
			static fn( int $ticket_id ) => [ 'name' => 'Early bird (no longer exists)', 'type' => 'series_pass', 'stored' => true ],
		];

		yield 'version 2 order: the stored values win over the snapshot entry' => [
			true,
			static fn( array $item ) => array_merge( $item, [ 'snapshot' => [ 'name' => 'From the snapshot', 'ticket_type' => 'default' ] ] ),
			static fn( int $ticket_id ) => [ 'name' => 'Early bird (no longer exists)', 'type' => 'series_pass', 'stored' => true ],
		];

		yield 'old order: the snapshot entry' => [
			false,
			static fn( array $item ) => array_merge( $item, [ 'snapshot' => [ 'name' => 'From the snapshot', 'ticket_type' => 'series_pass' ] ] ),
			static fn( int $ticket_id ) => [ 'name' => 'From the snapshot (no longer exists)', 'type' => 'series_pass', 'stored' => true ],
		];

		yield 'old order: an RSVP item keeps its own type' => [
			false,
			static fn( array $item ) => array_merge( $item, [ 'type' => 'tc-rsvp' ] ),
			static fn( int $ticket_id ) => [ 'name' => "Ticket #{$ticket_id} (no longer exists)", 'type' => 'tc-rsvp', 'stored' => false ],
		];

		yield 'old order: the ticket ID' => [
			false,
			static fn( array $item ) => $item,
			static fn( int $ticket_id ) => [ 'name' => "Ticket #{$ticket_id} (no longer exists)", 'type' => 'default', 'stored' => false ],
		];
	}

	/**
	 * @dataProvider name_provider
	 */
	public function test_it_describes_a_line_whose_ticket_is_gone( bool $version_2, Closure $make_item, Closure $expected ): void {
		$event_id  = static::factory()->post->create( [ 'post_type' => 'page' ] );
		$ticket_id = $this->create_tc_ticket( $event_id, 10, [ 'ticket_name' => 'Early bird' ] );
		update_post_meta( $ticket_id, '_type', 'series_pass' );
		if ( $version_2 ) {
			$this->register_controller();
		}
		$order = tec_tc_get_order( $this->create_order( [ $ticket_id => 1 ] )->ID );
		// The values are read from what the order stored, never from the ticket.
		wp_update_post( [ 'ID' => $ticket_id, 'post_title' => 'Renamed' ] );
		update_post_meta( $ticket_id, '_type', 'default' );
		$key = array_key_first( $order->items );

		$missing = tribe( Fallbacks::class )->get_missing_ticket( $order, $key, $make_item( $order->items[ $key ] ) );

		$this->assertSame( $expected( $ticket_id ), $missing );
	}

	public function test_it_reads_the_rows_of_an_order_once(): void {
		$event_id   = static::factory()->post->create( [ 'post_type' => 'page' ] );
		$ticket_ids = [
			$this->create_tc_ticket( $event_id, 10, [ 'ticket_name' => 'General admission' ] ),
			$this->create_tc_ticket( $event_id, 20, [ 'ticket_name' => 'VIP' ] ),
		];
		$this->register_controller();
		$order = tec_tc_get_order( $this->create_order( [ $ticket_ids[0] => 1, $ticket_ids[1] => 2 ] )->ID );
		// Creating the order already read its rows through the cache.
		wp_cache_flush();
		$count = $this->count_queries_against_the_table();
		$names = [];

		foreach ( $order->items as $key => $item ) {
			$names[] = tribe( Fallbacks::class )->get_missing_ticket( $order, $key, $item )['name'];
		}

		$this->assertSame( [ 'General admission (no longer exists)', 'VIP (no longer exists)' ], $names );
		$this->assertSame( 1, $count() );
	}

	public function test_it_does_not_query_the_table_for_an_old_order(): void {
		$event_id  = static::factory()->post->create( [ 'post_type' => 'page' ] );
		$ticket_id = $this->create_tc_ticket( $event_id, 10 );
		$order     = tec_tc_get_order( $this->create_order( [ $ticket_id => 1 ] )->ID );
		$this->register_controller();
		$count = $this->count_queries_against_the_table();
		$key   = array_key_first( $order->items );

		tribe( Fallbacks::class )->get_missing_ticket( $order, $key, $order->items[ $key ] );

		$this->assertSame( 0, $count() );
	}

	public function test_a_failed_read_of_the_rows_is_logged_and_the_ticket_id_shown(): void {
		$event_id  = static::factory()->post->create( [ 'post_type' => 'page' ] );
		$ticket_id = $this->create_tc_ticket( $event_id, 10, [ 'ticket_name' => 'Early bird' ] );
		$this->register_controller();
		$order = tec_tc_get_order( $this->create_order( [ $ticket_id => 1 ] )->ID );
		wp_cache_flush();
		$this->set_class_fn_return(
			Order_Items_Repository::class,
			'get_by_order',
			static function () {
				throw new RuntimeException( 'Query failed.' );
			},
			true
		);
		$key = array_key_first( $order->items );

		$name = tribe( Fallbacks::class )->get_missing_ticket( $order, $key, $order->items[ $key ] )['name'];

		$this->assertSame( "Ticket #{$ticket_id} (no longer exists)", $name );
		$this->assert_logged( 'warning', 'could not be read' );
	}

	public function test_an_order_whose_table_write_failed_names_a_deleted_ticket(): void {
		$event_id  = static::factory()->post->create( [ 'post_type' => 'page' ] );
		$ticket_id = $this->create_tc_ticket( $event_id, 10, [ 'ticket_name' => 'Early bird' ] );
		$this->register_controller();
		$this->set_class_fn_return(
			Order_Items_Repository::class,
			'insert_many',
			static function () {
				throw new RuntimeException( 'Insert failed.' );
			},
			true
		);
		$order = $this->create_order( [ $ticket_id => 1 ] );
		tribe( Module::class )->delete_ticket( $event_id, $ticket_id );
		$order = tec_tc_get_order( $order->ID );
		$key   = array_key_first( $order->items );

		$name = tribe( Fallbacks::class )->get_missing_ticket( $order, $key, $order->items[ $key ] )['name'];

		$this->assertSame( 'Early bird (no longer exists)', $name );
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
