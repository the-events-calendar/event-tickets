<?php

namespace TEC\Tickets\Relative_Sale_Dates;

use DateTimeImmutable;
use TEC\Common\StellarWP\AdminNotices\AdminNotice;
use TEC\Common\StellarWP\AdminNotices\AdminNotices;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\Relative_Sale_Dates_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;

class Inverted_Window_Notice_Test extends Controller_Test_Case {
	use Relative_Sale_Dates_Maker;
	use Ticket_Maker;
	use With_Tickets_Commerce;

	protected $controller_class = Controller::class;

	/**
	 * The ID the notice is registered with.
	 */
	private const NOTICE_ID = 'tec-tickets-relative-sale-dates-inverted-window';

	/**
	 * @before
	 */
	public function register_controller(): void {
		$this->make_controller()->register();
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	/**
	 * @after
	 */
	public function forget_the_notice(): void {
		AdminNotices::removeNotice( self::NOTICE_ID );
		unset( $_GET['post'] );
	}

	/**
	 * @test
	 */
	public function should_name_a_now_ticket_whose_relative_end_passed_once_the_event_dates_were_saved(): void {
		$event_start = $this->get_future_event_start();
		$event_id    = $this->create_event( $event_start->format( 'Y-m-d H:i:s' ) );
		$ticket_id   = $this->create_now_ticket_ending_two_weeks_before_the_event( $event_id, 'Early Bird' );
		// The event's dates are saved after the ticket's, as on a new event: two days away, the end is 12 days ago.
		$this->move_event( $event_id, new DateTimeImmutable( '+2 days 19:00' ) );

		$this->assertTrue( tribe( Ticket_Dates::class )->ends_before_it_starts( $ticket_id, Window_Kind::sales() ) );

		$this->load_edit_screen( $event_id );

		$notice = $this->get_notice();
		$this->assertNotNull( $notice, 'The edit screen warns about the ticket that is never on sale.' );
		$this->assertStringContainsString( 'Early Bird', $notice->getRenderedContent() );
		$this->assertStringContainsString( 'end before they start', $notice->getRenderedContent() );
	}

	/**
	 * @test
	 */
	public function should_show_no_notice_while_every_window_ends_after_it_starts(): void {
		$event_id = $this->create_event( $this->get_future_event_start()->format( 'Y-m-d H:i:s' ) );
		$this->create_now_ticket_ending_two_weeks_before_the_event( $event_id, 'Early Bird' );
		$this->create_ruled_ticket( $event_id );

		$this->load_edit_screen( $event_id );

		$this->assertNull( $this->get_notice() );
	}

	/**
	 * @test
	 */
	public function should_show_no_notice_to_a_user_who_cannot_edit_the_event(): void {
		$event_id = $this->create_event( $this->get_future_event_start()->format( 'Y-m-d H:i:s' ) );
		$this->create_now_ticket_ending_two_weeks_before_the_event( $event_id, 'Early Bird' );
		$this->move_event( $event_id, new DateTimeImmutable( '+2 days 19:00' ) );
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$this->load_edit_screen( $event_id );

		$this->assertNull( $this->get_notice() );
	}

	/**
	 * @param int    $event_id The event post ID.
	 * @param string $name     The ticket name.
	 *
	 * @return int The ticket post ID, on sale from now until 2 weeks before the event starts.
	 */
	private function create_now_ticket_ending_two_weeks_before_the_event( int $event_id, string $name ): int {
		// A Now start saved before the event has dates is stored as the day the event was published.
		$ticket_id = $this->create_tc_ticket(
			$event_id,
			1,
			[
				'ticket_name'       => $name,
				'ticket_start_date' => get_the_date( 'Y-m-d', $event_id ),
				'ticket_start_time' => '00:00:00',
			]
		);
		tribe( Rule_Store::class )->save(
			$ticket_id,
			[
				// A sales start of Now: the window opens at once.
				'start' => [ 'mode' => Rule::MODE_DEFAULT ],
				'end'   => $this->relative( 2, WEEK_IN_SECONDS ),
			]
		);
		tribe( Ticket_Dates::class )->write( $ticket_id, $event_id, Rule::from_stored( tribe( Rule_Store::class )->get( $ticket_id ), Window_Kind::sales() ) );

		return $ticket_id;
	}

	/**
	 * @param int               $event_id The event post ID.
	 * @param DateTimeImmutable $start    The new event start, in UTC.
	 *
	 * @return void
	 */
	private function move_event( int $event_id, DateTimeImmutable $start ): void {
		tribe_update_event(
			$event_id,
			[
				'EventStartDate' => $start->format( 'Y-m-d' ),
				'EventStartTime' => $start->format( 'H:i:s' ),
				'EventEndDate'   => $start->modify( '+3 hours' )->format( 'Y-m-d' ),
				'EventEndTime'   => $start->modify( '+3 hours' )->format( 'H:i:s' ),
				'EventTimezone'  => 'UTC',
			]
		);
		do_action( 'tec_shutdown' );
	}

	/**
	 * @return AdminNotice|null The notice, when it is registered.
	 */
	private function get_notice(): ?AdminNotice {
		foreach ( AdminNotices::getNotices() as $notice ) {
			if ( self::NOTICE_ID === $notice->getId() ) {
				return $notice;
			}
		}

		return null;
	}

	/**
	 * @param int $event_id The event post ID.
	 *
	 * @return void
	 */
	private function load_edit_screen( int $event_id ): void {
		$_GET['post'] = (string) $event_id;
		do_action( 'load-post.php' );
	}
}
