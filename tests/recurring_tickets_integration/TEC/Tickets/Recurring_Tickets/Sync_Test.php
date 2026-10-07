<?php

namespace TEC\Tickets\Recurring_Tickets;

use Codeception\TestCase\WPTestCase;
use TEC\Common\StellarWP\DB\DB;
use TEC\Events\Custom_Tables\V1\Tables\Occurrences;
use TEC\Events\Custom_Tables\V1\Updates\Controller as Updates_Controller;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Recurring_Tickets\Models\Ticket;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;
use TEC\Tickets\Recurring_Tickets\Tables\Tickets;
use TEC\Tickets\Tests\Recurring_Tickets\Ticket_Rows;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

/**
 * Sync keeps one row per template and date.
 */
class Sync_Test extends WPTestCase {
	use Ticket_Rows;
	use Ticket_Maker;

	/**
	 * @before
	 */
	public function empty_table(): void {
		( new Tickets() )->empty_table();
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	/**
	 * @test
	 */
	public function it_should_give_each_date_a_row_of_a_new_template_with_its_values(): void {
		$event    = $this->create_recurring_event();
		$dates    = $this->get_dates( $event );
		$template = $this->create_template( $event, 'General Admission', '10.50', 20 );

		$rows = $this->rows_of( $template );

		$this->assertCount( 3, $rows );
		foreach ( $dates as $index => $date ) {
			$row = $rows[ $index ];
			$this->assertSame( (int) $date->occurrence_id, (int) $row->occurrence_id );
			$this->assertSame( $date->start_date, $this->datetime( $row->occurrence_start ) );
			$this->assertSame( $date->start_date_utc, $this->datetime( $row->occurrence_start_utc ) );
			$this->assertSame( 'recurring', $row->type );
			$this->assertSame( $event, (int) $row->post_id );
			$this->assertSame( 'General Admission', $row->name );
			$this->assertSame( 'Description of General Admission', $row->description );
			$this->assertSame( 10500, (int) $row->price );
			$this->assertSame( 20, (int) $row->capacity );
			$this->assertSame( 20, (int) $row->stock );
			$this->assertSame( 0, (int) $row->sales );
			$this->assertSame( 'own', $row->stock_mode );
			$this->assertSame( '2030-01-01 08:00:00', $this->datetime( $row->start_date ) );
			$this->assertSame( '2030-06-01 20:30:00', $this->datetime( $row->end_date ) );
			$this->assertSame( 'publish', $row->status );
			$this->assertNull( $row->overrides );
		}
	}

	/**
	 * @test
	 */
	public function it_should_change_nothing_when_it_runs_again(): void {
		$event    = $this->create_recurring_event();
		$template = $this->create_template( $event, 'General Admission', '10.50', 20 );
		$before   = $this->snapshot( $template );

		tribe( Sync::class )->sync_event( $event );
		tribe( Sync::class )->sync_event( $event );

		$this->assertSame( $before, $this->snapshot( $template ) );
	}

	/**
	 * @test
	 */
	public function it_should_refresh_the_rows_when_the_template_changes(): void {
		$event    = $this->create_recurring_event();
		$template = $this->create_template( $event, 'General Admission', '10.50', 20 );
		$ids      = array_column( $this->snapshot( $template ), 'id' );

		$this->update_template( $event, $template, [ 'ticket_name' => 'VIP', 'ticket_price' => '25' ] );

		$rows = $this->rows_of( $template );
		$this->assertSame( $ids, array_map( static fn( Ticket $row ) => (int) $row->id, $rows ), 'The rows keep their IDs.' );
		$this->assertSame( [ 'VIP', 'VIP', 'VIP' ], array_column( $rows, 'name' ) );
		$this->assertSame( [ 25000, 25000, 25000 ], array_map( 'intval', array_column( $rows, 'price' ) ) );
	}

	/**
	 * @test
	 */
	public function it_should_keep_sales_when_the_capacity_changes(): void {
		$event    = $this->create_recurring_event();
		$template = $this->create_template( $event, 'General Admission', '10.50', 10 );
		$first    = $this->rows_of( $template )[0];
		$this->assertTrue( tribe( Stock::class )->sell( (int) $first->id, 3 ) );

		$this->update_template( $event, $template, [ 'tribe-ticket' => [ 'mode' => 'own', 'capacity' => 20 ] ] );

		$rows = $this->rows_of( $template );
		$this->assertSame( [ 20, 3, 17 ], [ (int) $rows[0]->capacity, (int) $rows[0]->sales, (int) $rows[0]->stock ] );
		$this->assertSame( [ 20, 0, 20 ], [ (int) $rows[1]->capacity, (int) $rows[1]->sales, (int) $rows[1]->stock ] );

		$this->update_template( $event, $template, [ 'tribe-ticket' => [ 'mode' => 'own', 'capacity' => 2 ] ] );
		$this->assertSame( 0, (int) $this->rows_of( $template )[0]->stock, 'Stock never goes below zero.' );

		$this->update_template( $event, $template, [ 'tribe-ticket' => [ 'mode' => '', 'capacity' => '' ] ] );
		$rows = $this->rows_of( $template );
		$this->assertSame( [ -1, null, 'unlimited' ], [ (int) $rows[0]->capacity, $rows[0]->stock, $rows[0]->stock_mode ] );
	}

	/**
	 * @test
	 */
	public function it_should_keep_an_overridden_column(): void {
		$event    = $this->create_recurring_event();
		$template = $this->create_template( $event, 'General Admission', '10.50', 20 );
		$first    = $this->rows_of( $template )[0];
		tribe( Rows::class )->override( (int) $first->id, [ 'price' => 5000 ] );

		$this->update_template( $event, $template, [ 'ticket_name' => 'VIP', 'ticket_price' => '25' ] );

		$rows = $this->rows_of( $template );
		$this->assertSame( [ 'VIP', 5000 ], [ $rows[0]->name, (int) $rows[0]->price ] );
		$this->assertSame( [ 'VIP', 25000 ], [ $rows[1]->name, (int) $rows[1]->price ] );
	}

	/**
	 * @test
	 */
	public function it_should_delete_the_rows_of_a_removed_date(): void {
		$event    = $this->create_recurring_event();
		$dates    = $this->get_dates( $event );
		$template = $this->create_template( $event, 'General Admission', '10.50', 20 );

		// The rule now ends after two dates, as an editor saves it; then what the end of the request does.
		$recurrence                          = get_post_meta( $event, '_EventRecurrence', true );
		$recurrence['rules'][0]['end-count'] = 2;
		update_post_meta( $event, '_EventRecurrence', $recurrence );
		tribe( Updates_Controller::class )->commit_updates();

		$this->assertCount( 2, $this->get_dates( $event ) );
		$this->assertSame(
			[ (int) $dates[0]->occurrence_id, (int) $dates[1]->occurrence_id ],
			array_map( static fn( Ticket $row ) => (int) $row->occurrence_id, $this->rows_of( $template ) )
		);
	}

	/**
	 * @test
	 */
	public function it_should_delete_the_rows_of_a_deleted_template(): void {
		$event    = $this->create_recurring_event();
		$template = $this->create_template( $event, 'General Admission', '10.50', 20 );
		$other    = $this->create_template( $event, 'VIP', '25', 5 );

		tribe( Module::class )->delete_ticket( $event, $template );

		$this->assertCount( 0, $this->rows_of( $template ) );
		$this->assertCount( 3, $this->rows_of( $other ) );
	}

	/**
	 * @test
	 */
	public function it_should_leave_a_row_whose_date_moved_to_another_event(): void {
		$event    = $this->create_recurring_event();
		$other    = $this->create_recurring_event();
		$dates    = $this->get_dates( $event );
		$template = $this->create_template( $event, 'General Admission', '10.50', 20 );
		// What ECP's "This and following" does to the dates it moves.
		DB::query( DB::prepare( 'UPDATE %i SET post_id = %d WHERE occurrence_id = %d', Occurrences::table_name(), $other, $dates[2]->occurrence_id ) );

		tribe( Sync::class )->sync_event( $event );

		$this->assertCount( 3, $this->rows_of( $template ) );
	}

	/**
	 * @test
	 */
	public function it_should_leave_a_post_that_is_not_an_event_alone(): void {
		$post = static::factory()->post->create();
		$this->insert_ticket_row( [ 'post_id' => $post ] );

		tribe( Sync::class )->sync_event( $post );

		$this->assertSame( 1, Tickets::get_total_items() );
	}

	/**
	 * @test
	 */
	public function it_should_skip_the_dates_of_an_event_without_recurring_event_tickets(): void {
		$event   = $this->create_recurring_event();
		$queries = [];
		add_filter(
			'query',
			static function ( string $query ) use ( &$queries ) {
				$queries[] = $query;

				return $query;
			}
		);

		tribe( Sync::class )->sync_event( $event );

		$this->assertSame( [], array_filter( $queries, static fn( string $query ) => false !== strpos( $query, Occurrences::table_name() ) ) );
	}

	/**
	 * @test
	 */
	public function it_should_take_the_rows_another_request_wrote_meanwhile(): void {
		$event    = $this->create_recurring_event();
		$template = $this->create_template( $event, 'General Admission', '10.50', 20 );
		$first    = $this->rows_of( $template )[0];
		tribe( Rows::class )->delete_ids( array_map( static fn( Ticket $row ) => (int) $row->id, $this->rows_of( $template ) ) );
		$raced = false;
		add_filter(
			'query',
			function ( string $query ) use ( &$raced, $template, $event, $first ) {
				if ( ! $raced && 0 === strpos( $query, 'INSERT INTO `' . Tickets::table_name() . '`' ) ) {
					$raced = true;
					// Another request writes the first date's row between this Sync's read and its insert.
					$this->insert_ticket_row( [ 'parent_id' => $template, 'post_id' => $event, 'occurrence_id' => (int) $first->occurrence_id ] );
				}

				return $query;
			}
		);

		tribe( Sync::class )->sync_event( $event );

		$this->assertTrue( $raced );
		$rows = $this->rows_of( $template );
		$this->assertCount( 3, $rows );
		$this->assertCount( 3, array_unique( array_map( static fn( Ticket $row ) => (int) $row->occurrence_id, $rows ) ) );
	}

	/**
	 * Creates a template on an event, the way an editor does.
	 *
	 * @param int    $event    The event.
	 * @param string $name     The template's name.
	 * @param string $price    The template's price.
	 * @param int    $capacity The template's capacity.
	 *
	 * @return int The template.
	 */
	private function create_template( int $event, string $name, string $price, int $capacity ): int {
		return $this->create_tc_ticket(
			$event,
			$price,
			[
				'ticket_name'        => $name,
				'ticket_description' => "Description of {$name}",
				'ticket_start_date'  => '2030-01-01',
				'ticket_start_time'  => '08:00:00',
				'ticket_end_date'    => '2030-06-01',
				'ticket_end_time'    => '20:30:00',
				'tribe-ticket'       => [
					'mode'     => 'own',
					'capacity' => $capacity,
				],
			]
		);
	}

	/**
	 * Saves a template again with some values changed.
	 *
	 * @param int                 $event    The event.
	 * @param int                 $template The template.
	 * @param array<string,mixed> $changes  The changed values.
	 *
	 * @return void
	 */
	private function update_template( int $event, int $template, array $changes ): void {
		$post = get_post( $template );
		tribe( Module::class )->ticket_add(
			$event,
			array_merge(
				[
					'ticket_id'          => $template,
					'ticket_name'        => $post->post_title,
					'ticket_description' => $post->post_excerpt,
					'ticket_price'       => get_post_meta( $template, '_price', true ),
					'ticket_start_date'  => '2030-01-01',
					'ticket_start_time'  => '08:00:00',
					'ticket_end_date'    => '2030-06-01',
					'ticket_end_time'    => '20:30:00',
					'tribe-ticket'       => [
						'mode'     => 'own',
						'capacity' => get_post_meta( $template, '_tribe_ticket_capacity', true ),
					],
				],
				$changes
			)
		);
	}

	/**
	 * @param int $template The template.
	 *
	 * @return Ticket[] The template's rows, in date order.
	 */
	private function rows_of( int $template ): array {
		$rows = tribe( Rows::class )->get_by_template( $template );
		usort( $rows, fn( Ticket $a, Ticket $b ) => strcmp( (string) $this->datetime( $a->occurrence_start_utc ), (string) $this->datetime( $b->occurrence_start_utc ) ) );

		return $rows;
	}

	/**
	 * @param int $template The template.
	 *
	 * @return array<int,array<string,mixed>> The template's rows as stored, without the time they were last updated.
	 */
	private function snapshot( int $template ): array {
		$rows = DB::get_results( DB::prepare( 'SELECT * FROM %i WHERE parent_id = %d ORDER BY id', Tickets::table_name(), $template ), ARRAY_A );

		return array_map(
			static function ( array $row ) {
				unset( $row['updated_at'] );
				$row['id'] = (int) $row['id'];

				return $row;
			},
			$rows
		);
	}

	/**
	 * @param mixed $date A date as a model holds it.
	 *
	 * @return string|null The date as `Y-m-d H:i:s`.
	 */
	private function datetime( $date ): ?string {
		if ( $date instanceof \DateTimeInterface ) {
			return $date->format( 'Y-m-d H:i:s' );
		}

		return null === $date ? null : (string) $date;
	}
}
