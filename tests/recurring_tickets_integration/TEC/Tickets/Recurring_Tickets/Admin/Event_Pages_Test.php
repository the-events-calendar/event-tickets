<?php

namespace TEC\Tickets\Recurring_Tickets\Admin;

use Codeception\TestCase\WPTestCase;
use RuntimeException;
use TEC\Tickets\Recurring_Tickets\Tables\Tickets;
use TEC\Tickets\Recurring_Tickets\Template_Guard;
use TEC\Tickets\Tests\Recurring_Tickets\Requires_Recurrence_Tier;
use TEC\Tickets\Tests\Recurring_Tickets\Ticket_Rows;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

/**
 * A date's Attendees and Orders pages are its event's, which list every date.
 */
class Event_Pages_Test extends WPTestCase {
	use Ticket_Rows;
	use Ticket_Maker;
	use Requires_Recurrence_Tier;

	/**
	 * The event.
	 *
	 * @var int
	 */
	private int $event = 0;

	/**
	 * The event's second date.
	 *
	 * @var int
	 */
	private int $date = 0;

	/**
	 * Where the request was redirected to, if anywhere.
	 *
	 * @var string|null
	 */
	private ?string $location = null;

	/**
	 * @before
	 */
	public function create_event(): void {
		( new Tickets() )->empty_table();
		$this->event = $this->create_recurring_event();
		$template    = $this->create_tc_ticket( $this->event, 10 );
		// As the ticket editor saves a ticket on a recurring event.
		update_post_meta( $template, '_type', Template_Guard::TICKET_TYPE );
		$this->date = (int) $this->get_dates( $this->event )[1]->provisional_id;

		add_filter(
			'wp_redirect',
			function ( $location ) {
				$this->location = $location;

				// No headers in a test.
				return false;
			}
		);
		add_filter(
			'tribe_exit',
			static fn() => static function () {
				throw new RuntimeException( 'exit' );
			}
		);
	}

	/**
	 * @after
	 */
	public function clean_up(): void {
		remove_all_filters( 'wp_redirect' );
		remove_all_filters( 'tribe_exit' );
		( new Tickets() )->empty_table();
		unset( $_GET['page'], $_GET['event_id'], $_GET['post_id'] );
	}

	/**
	 * @return array<string,array{0:string,1:string}> The page, and the request variable it reads its post from.
	 */
	public function date_page_provider(): array {
		return [
			'Attendees, by event_id' => [ 'tickets-attendees', 'event_id' ],
			'Orders, by post_id'     => [ 'tickets-commerce-orders', 'post_id' ],
			'Orders, by event_id'    => [ 'tickets-commerce-orders', 'event_id' ],
		];
	}

	/**
	 * @test
	 * @dataProvider date_page_provider
	 */
	public function it_should_redirect_a_dates_page_to_its_events_unfiltered( string $page, string $var ): void {
		$_SERVER['REQUEST_URI'] = "/wp-admin/edit.php?post_type=tribe_events&page={$page}&{$var}={$this->date}";
		$_GET['page']           = $page;
		$_GET[ $var ]           = (string) $this->date;

		try {
			tribe( Event_Pages::class )->redirect_date_to_event();
			$this->fail( 'The request should have exited.' );
		} catch ( RuntimeException $e ) {
			$this->assertSame( 'exit', $e->getMessage() );
		}

		$this->assertSame( "/wp-admin/edit.php?post_type=tribe_events&page={$page}&{$var}={$this->event}", $this->location );
	}

	/**
	 * @test
	 */
	public function it_should_leave_the_events_own_page_alone(): void {
		$_GET['page']     = 'tickets-attendees';
		$_GET['event_id'] = (string) $this->event;

		tribe( Event_Pages::class )->redirect_date_to_event();

		$this->assertNull( $this->location );
	}

	/**
	 * @test
	 */
	public function it_should_leave_other_pages_alone(): void {
		$_GET['page']     = 'tickets-other';
		$_GET['event_id'] = (string) $this->date;

		tribe( Event_Pages::class )->redirect_date_to_event();

		$this->assertNull( $this->location );
	}

	/**
	 * @test
	 */
	public function it_should_leave_the_page_of_a_post_without_dates_alone(): void {
		$_GET['page']     = 'tickets-attendees';
		$_GET['event_id'] = (string) static::factory()->post->create();

		tribe( Event_Pages::class )->redirect_date_to_event();

		$this->assertNull( $this->location );
	}
}
