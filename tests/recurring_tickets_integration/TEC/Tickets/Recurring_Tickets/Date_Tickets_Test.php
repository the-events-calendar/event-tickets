<?php

namespace TEC\Tickets\Recurring_Tickets;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Recurring_Tickets\Tables\Tickets;
use TEC\Tickets\Tests\Recurring_Tickets\Ticket_Rows;
use TEC\Tickets\Tests\Recurring_Tickets\Without_Recurrence_Tier;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

/**
 * A date's ticket list holds its own rows where the templates were, and keeps every other ticket.
 */
class Date_Tickets_Test extends WPTestCase {
	use Without_Recurrence_Tier;
	use Ticket_Rows;
	use Ticket_Maker;

	/**
	 * @after
	 */
	public function empty_table(): void {
		( new Tickets() )->empty_table();
	}

	/**
	 * @test
	 */
	public function it_should_list_a_dates_rows_in_place_of_the_templates(): void {
		[ $event, $dates, $templates ] = $this->create_event_with_templates();
		[ $first, $second ]            = $templates;
		$this->insert_ticket_row( [ 'parent_id' => $second, 'post_id' => $event, 'occurrence_id' => $dates[1]->occurrence_id, 'name' => 'Second on day two', 'menu_order' => 0 ] );
		$this->insert_ticket_row( [ 'parent_id' => $first, 'post_id' => $event, 'occurrence_id' => $dates[1]->occurrence_id, 'name' => 'First on day two', 'menu_order' => 1 ] );
		$this->insert_ticket_row( [ 'parent_id' => $first, 'post_id' => $event, 'occurrence_id' => $dates[2]->occurrence_id, 'name' => 'First on day three' ] );

		$day_two   = tribe( Module::class )->get_tickets( $dates[1]->provisional_id );
		$day_three = tribe( Module::class )->get_tickets( $dates[2]->provisional_id );

		$this->assertSame( [ 'Second on day two', 'First on day two', 'Default ticket' ], array_column( $day_two, 'name' ) );
		$this->assertSame( [ 'First on day three', 'Default ticket' ], array_column( $day_three, 'name' ) );
		$this->assertTrue( Ticket_ID::is_table_ticket( $day_two[0]->ID ) );
		$this->assertSame( $dates[1]->provisional_id, $day_two[0]->get_event_id() );
	}

	/**
	 * @test
	 */
	public function it_should_leave_out_a_draft_row(): void {
		[ $event, $dates, $templates ] = $this->create_event_with_templates();
		$this->insert_ticket_row( [ 'parent_id' => $templates[0], 'post_id' => $event, 'occurrence_id' => $dates[0]->occurrence_id, 'name' => 'Published' ] );
		$this->insert_ticket_row( [ 'parent_id' => $templates[1], 'post_id' => $event, 'occurrence_id' => $dates[0]->occurrence_id, 'name' => 'Draft', 'status' => 'draft' ] );

		$names = array_column( tribe( Module::class )->get_tickets( $dates[0]->provisional_id ), 'name' );

		$this->assertSame( [ 'Published', 'Default ticket' ], $names );
	}

	/**
	 * @test
	 */
	public function it_should_list_the_templates_of_the_event_in_the_admin(): void {
		[ $event, $dates, $templates ] = $this->create_event_with_templates();
		$this->insert_ticket_row( [ 'parent_id' => $templates[0], 'post_id' => $event, 'occurrence_id' => $dates[0]->occurrence_id ] );
		set_current_screen( 'edit-post' );
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$ids = array_column( tribe( Module::class )->get_tickets( $event ), 'ID' );

		$this->assertSame( array_merge( $templates, [ $this->default_ticket ] ), $ids );
	}

	/**
	 * @test
	 */
	public function it_should_read_a_dates_rows_in_one_query(): void {
		[ $event, $dates, $templates ] = $this->create_event_with_templates();
		foreach ( $templates as $order => $template ) {
			$this->insert_ticket_row( [ 'parent_id' => $template, 'post_id' => $event, 'occurrence_id' => $dates[0]->occurrence_id, 'menu_order' => $order ] );
		}
		$queries = 0;
		add_filter(
			'query',
			static function ( string $query ) use ( &$queries ) {
				if ( false !== strpos( $query, Tickets::table_name() ) ) {
					++$queries;
				}

				return $query;
			}
		);

		tribe( Module::class )->get_tickets( $dates[0]->provisional_id );
		// A context skips the list cache of get_tickets(): the date's rows are still read once in the request.
		tribe( Module::class )->get_tickets( $dates[0]->provisional_id, 'some-context' );

		$this->assertSame( 1, $queries );
	}

	/**
	 * @test
	 */
	public function it_should_list_a_dates_new_stock_after_a_sale_in_the_same_request(): void {
		[ $event, $dates, $templates ] = $this->create_event_with_templates();
		$id                            = $this->insert_ticket_row( [ 'parent_id' => $templates[0], 'post_id' => $event, 'occurrence_id' => $dates[0]->occurrence_id, 'capacity' => 10, 'stock' => 10 ] );
		tribe( Module::class )->get_tickets( $dates[0]->provisional_id, 'some-context' );

		tribe( Stock::class )->sell( Ticket_ID::to_row_id( $id ), 3 );

		$listed = tribe( Module::class )->get_tickets( $dates[0]->provisional_id, 'some-context' );
		$this->assertSame( 7, $listed[0]->stock() );
	}

	/**
	 * @after
	 */
	public function reset_screen(): void {
		set_current_screen( 'front' );
	}

	/**
	 * The default ticket of the last event created.
	 *
	 * @var int
	 */
	private int $default_ticket = 0;

	/**
	 * @return array{0: int, 1: array<int,object>, 2: int[]} The event, its dates, and its two templates.
	 */
	private function create_event_with_templates(): array {
		$event     = $this->create_recurring_event();
		$templates = [];
		foreach ( [ 'First template', 'Second template' ] as $name ) {
			$template = $this->create_tc_ticket( $event, 10, [ 'ticket_name' => $name ] );
			update_post_meta( $template, '_type', Template_Guard::TICKET_TYPE );
			$templates[] = $template;
		}
		$this->default_ticket = $this->create_tc_ticket( $event, 5, [ 'ticket_name' => 'Default ticket' ] );

		// Tickets are listed by menu order alone: give each its own, so tickets created in the same second keep an order.
		foreach ( array_merge( $templates, [ $this->default_ticket ] ) as $menu_order => $ticket ) {
			wp_update_post( [ 'ID' => $ticket, 'menu_order' => $menu_order ] );
		}

		return [ $event, $this->get_dates( $event ), $templates ];
	}
}
