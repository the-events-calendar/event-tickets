<?php

namespace TEC\Tickets\Recurring_Tickets;

use Codeception\TestCase\WPTestCase;
use Generator;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;
use TEC\Tickets\Recurring_Tickets\Tables\Tickets;
use TEC\Tickets\Tests\Recurring_Tickets\Recurrence_Tier_Check;
use TEC\Tickets\Tests\Recurring_Tickets\Ticket_Rows;
use Tribe\Tickets\Events\Views\V2\Models\Tickets as Ticket_Model;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Tickets__Tickets as Tribe_Tickets;

/**
 * The calendar views show each date of a recurring event its own availability.
 */
class Views_Test extends WPTestCase {
	use Ticket_Rows;
	use Ticket_Maker;
	use Recurrence_Tier_Check;

	/**
	 * The event.
	 *
	 * @var int
	 */
	private int $event = 0;

	/**
	 * The template.
	 *
	 * @var int
	 */
	private int $template = 0;

	/**
	 * The dates, by position.
	 *
	 * @var object[]
	 */
	private array $dates = [];

	/**
	 * @before
	 */
	public function create_event(): void {
		( new Tickets() )->empty_table();
		tec_kv_cache()->flush();
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$this->event    = $this->create_recurring_event();
		$this->template = $this->create_tc_ticket( $this->event, 10, [ 'tribe-ticket' => [ 'mode' => 'own', 'capacity' => 5 ] ] );
		wp_set_current_user( 0 );
		$this->dates = $this->get_dates( $this->event );
	}

	/**
	 * @test
	 */
	public function it_should_not_call_a_date_of_an_unlimited_ticket_sold_out(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$event = $this->create_recurring_event();
		// As the ticket editor saves a ticket with unlimited capacity.
		$this->create_tc_ticket( $event, 10, [ 'tribe-ticket' => [ 'mode' => '', 'capacity' => '' ] ] );
		wp_set_current_user( 0 );
		$date = (int) $this->get_dates( $event )[0]->provisional_id;

		$this->assertFalse( ( new Ticket_Model( $date ) )->sold_out() );
		$counts = Tribe_Tickets::get_ticket_counts( $date );
		$this->assertSame( 1, $counts['tickets']['unlimited'] );
	}

	/**
	 * @test
	 */
	public function it_should_keep_each_dates_availability_apart(): void {
		$this->skip_without_recurrence_tier();

		$this->assertTrue( tribe( Stock::class )->sell( $this->row_of( 1 ), 5 ) );

		// The second date is read, and cached, first.
		$this->assertTrue( ( new Ticket_Model( (int) $this->dates[1]->provisional_id ) )->sold_out() );
		$this->assertFalse( ( new Ticket_Model( (int) $this->dates[2]->provisional_id ) )->sold_out(), 'The third date has its own entry.' );

		// Read again, from the cache.
		$this->assertTrue( ( new Ticket_Model( (int) $this->dates[1]->provisional_id ) )->sold_out() );
		$this->assertFalse( ( new Ticket_Model( (int) $this->dates[2]->provisional_id ) )->sold_out() );
	}

	public function stock_write_provider(): Generator {
		yield 'a sale sells the date out' => [ 0, 5, true ];
		yield 'a release reopens a sold out date' => [ 5, -5, false ];
	}

	/**
	 * @test
	 * @dataProvider stock_write_provider
	 *
	 * @param int  $sold_before The tickets sold before the date is read.
	 * @param int  $change      The tickets sold after it is read, negative for released.
	 * @param bool $sold_out    Whether the date is sold out after the change.
	 */
	public function it_should_drop_a_dates_entry_when_its_stock_changes( int $sold_before, int $change, bool $sold_out ): void {
		$this->skip_without_recurrence_tier();
		$stock = tribe( Stock::class );
		$row   = $this->row_of( 1 );

		if ( $sold_before ) {
			$this->assertTrue( $stock->sell( $row, $sold_before ) );
		}

		$this->assertSame( ! $sold_out, ( new Ticket_Model( (int) $this->dates[1]->provisional_id ) )->sold_out() );

		if ( $change > 0 ) {
			$this->assertTrue( $stock->sell( $row, $change ) );
		} else {
			$stock->release( $row, - $change );
		}
		// What the next request starts with.
		tribe_cache()->reset();

		$this->assertSame( $sold_out, ( new Ticket_Model( (int) $this->dates[1]->provisional_id ) )->sold_out() );
	}

	/**
	 * @test
	 */
	public function it_should_drop_a_dates_entry_when_its_rows_are_refreshed(): void {
		$this->skip_without_recurrence_tier();

		$this->assertTrue( tribe( Stock::class )->sell( $this->row_of( 1 ), 5 ) );
		$this->assertTrue( ( new Ticket_Model( (int) $this->dates[1]->provisional_id ) )->sold_out() );

		// More capacity: Sync refreshes the rows.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		tribe( Module::class )->ticket_add(
			$this->event,
			[
				'ticket_id'    => $this->template,
				'ticket_name'  => get_post( $this->template )->post_title,
				'ticket_price' => 10,
				'tribe-ticket' => [ 'mode' => 'own', 'capacity' => 8 ],
			]
		);
		wp_set_current_user( 0 );
		// What the next request starts with.
		tribe_cache()->reset();

		$this->assertFalse( ( new Ticket_Model( (int) $this->dates[1]->provisional_id ) )->sold_out() );
	}

	/**
	 * @test
	 */
	public function it_should_read_the_rows_of_every_date_of_an_events_query_at_once(): void {
		$this->skip_without_recurrence_tier();

		// A new request starts with no row read.
		$rows = tribe( Rows::class );
		\Closure::bind(
			function () {
				$this->found           = [];
				$this->occurrence_rows = [];
			},
			$rows,
			Rows::class
		)();
		$queries = 0;
		add_filter(
			'query',
			static function ( string $query ) use ( &$queries ) {
				$queries += false !== strpos( $query, Tickets::table_name() ) ? 1 : 0;

				return $query;
			}
		);

		$posts = tribe_events()->where( 'starts_after', 'now' )->per_page( -1 )->all();
		foreach ( $this->dates as $date ) {
			$rows->get_by_occurrence( (int) $date->occurrence_id );
		}

		$this->assertGreaterThanOrEqual( 3, count( $posts ) );
		$this->assertSame( 1, $queries );
	}

	/**
	 * @param int $position The date's position.
	 *
	 * @return int The ID of the template's row on that date.
	 */
	private function row_of( int $position ): int {
		foreach ( tribe( Rows::class )->get_by_template( $this->template ) as $row ) {
			if ( (int) $row->occurrence_id === (int) $this->dates[ $position ]->occurrence_id ) {
				return (int) $row->id;
			}
		}

		return 0;
	}
}
