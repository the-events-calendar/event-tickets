<?php

namespace TEC\Tickets\Recurring_Tickets;

use Codeception\TestCase\WPTestCase;
use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;
use TEC\Tickets\Recurring_Tickets\Tables\Tickets;
use TEC\Tickets\Tests\Recurring_Tickets\Ticket_Rows;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

/**
 * A row from the front end reaches the cart only when it can be sold, on its own date.
 */
class Front_End_Test extends WPTestCase {
	use Ticket_Rows;
	use Ticket_Maker;

	/**
	 * The dates' IDs.
	 *
	 * @var int[]
	 */
	private array $dates = [];

	/**
	 * The ticket IDs of the template's rows, by date ID.
	 *
	 * @var array<int,int>
	 */
	private array $tickets = [];

	/**
	 * @before
	 */
	public function create_event(): void {
		( new Tickets() )->empty_table();
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$event    = $this->create_recurring_event();
		$template = $this->create_tc_ticket( $event, 10 );
		wp_set_current_user( 0 );

		foreach ( $this->get_dates( $event ) as $date ) {
			$this->dates[] = (int) $date->provisional_id;
			foreach ( tribe( Rows::class )->get_by_template( $template ) as $row ) {
				if ( (int) $row->occurrence_id === (int) $date->occurrence_id ) {
					$this->tickets[ (int) $date->provisional_id ] = Ticket_ID::from_row_id( (int) $row->id );
				}
			}
		}
	}

	/**
	 * @test
	 */
	public function it_should_take_a_row_on_its_own_date(): void {
		$second = $this->dates[1];

		$this->assertSame( [ $this->tickets[ $second ] ], $this->cart_tickets( $second, $this->tickets[ $second ] ) );
	}

	/**
	 * @test
	 */
	public function it_should_refuse_a_row_sent_from_another_dates_page(): void {
		$this->assertSame( [], $this->cart_tickets( $this->dates[2], $this->tickets[ $this->dates[1] ] ) );
	}

	/**
	 * @test
	 */
	public function it_should_refuse_a_row_that_is_not_published(): void {
		$second = $this->dates[1];
		$this->update_row( $this->tickets[ $second ], [ 'status' => 'draft' ] );

		$this->assertSame( [], $this->cart_tickets( $second, $this->tickets[ $second ] ) );
	}

	/**
	 * @test
	 */
	public function it_should_refuse_a_row_outside_its_sale_window(): void {
		$second = $this->dates[1];
		$this->update_row( $this->tickets[ $second ], [ 'end_date' => '2020-01-01 00:00:00', 'end_date_utc' => '2020-01-01 00:00:00' ] );

		$this->assertSame( [], $this->cart_tickets( $second, $this->tickets[ $second ] ) );
	}

	/**
	 * @test
	 */
	public function it_should_refuse_every_row_while_the_recurrence_tier_is_off(): void {
		$second = $this->dates[1];
		tribe( Recurrence_Controller::class )->unregister();
		tribe()->setVar( Recurrence_Controller::class . '_registered', false );

		try {
			$this->assertSame( [], $this->cart_tickets( $second, $this->tickets[ $second ] ) );
		} finally {
			tribe( Recurrence_Controller::class )->register();
		}
	}

	/**
	 * @test
	 */
	public function it_should_leave_other_tickets_alone(): void {
		$event  = tribe_events()->set_args( [ 'title' => 'Single', 'status' => 'publish', 'start_date' => '+1 week 10:00:00', 'end_date' => '+1 week 12:00:00' ] )->create()->ID;
		$ticket = $this->create_tc_ticket( $event, 10 );

		$this->assertSame( [ $ticket ], $this->cart_tickets( $event, $ticket ) );
	}

	/**
	 * Prepares the cart data the front-end ticket form sends.
	 *
	 * @param int $post_id   The page's post ID.
	 * @param int $ticket_id The ticket.
	 *
	 * @return int[] The tickets the cart would take.
	 */
	private function cart_tickets( int $post_id, int $ticket_id ): array {
		$data = tribe( Cart::class )->prepare_data(
			[
				'tribe_tickets_ar_data' => wp_json_encode(
					[
						'tribe_tickets_post_id' => $post_id,
						'tribe_tickets_tickets' => [
							[
								'ticket_id' => $ticket_id,
								'quantity'  => 1,
							],
						],
					]
				),
			]
		);

		return array_values( array_map( static fn( array $ticket ) => (int) $ticket['ticket_id'], $data['tickets'] ?? [] ) );
	}

	/**
	 * @param int                 $ticket_id The row's ticket ID.
	 * @param array<string,mixed> $values    The values to write.
	 *
	 * @return void
	 */
	private function update_row( int $ticket_id, array $values ): void {
		DB::update( Tickets::table_name(), $values, [ 'id' => Ticket_ID::to_row_id( $ticket_id ) ] );
		tribe( Rows::class )->forget( Ticket_ID::to_row_id( $ticket_id ) );
	}
}
