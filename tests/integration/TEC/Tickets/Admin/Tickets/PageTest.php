<?php

namespace TEC\Tickets\Admin\Tickets;

use TEC\Tickets\Commerce as TicketsCommerce;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

class PageTest extends \Codeception\TestCase\WPTestCase {
	use SnapshotAssertions;
	use With_Uopz;
	use Ticket_Maker;

	/**
	 * @var \TEC\Tickets\Admin\Tickets\Page
	 */
	protected $page;

	/**
	 * Created ticket IDs.
	 *
	 * @var array
	 */
	protected $ticket_ids;

	/**
	 * Created event IDs.
	 *
	 * @var array
	 */
	protected $event_ids;

	/**
	 * Deleted event ID.
	 *
	 * @var int
	 */
	protected $deleted_event_id;

	public function setUp(): void {
		// before
		parent::setUp();

		$this->page = new Page();

		add_filter( 'tec_tickets_admin_tickets_table_provider_info', function() {
			return [
				TicketsCommerce\Module::class => [
					'title'              => 'Tickets Commerce',
					'event_meta_key'     => TicketsCommerce\Attendee::$event_relation_meta_key,
					'attendee_post_type' => TicketsCommerce\Attendee::POSTTYPE,
					'ticket_post_type'   => TicketsCommerce\Ticket::POSTTYPE,
				]
			];
		} );
	}

	/**
	 * Create test events.
	 *
	 * @param int $number_of_events
	 *
	 * @return array
	 */
	protected function create_test_events( $number_of_events = 3 ) {
		$events_ids = [];

		for ( $i = 0; $i < $number_of_events; $i ++ ) {
			$event_ts = strtotime( '2025-01-01 00:00:00' ) + $i * DAY_IN_SECONDS;
			$event_dt = new \DateTime( "@$event_ts" );

			$events_ids[] = tribe_events()->set_args(
				[
					'title'      => 'Event ' . ( $i + 1 ),
					'status'     => 'publish',
					'start_date' => $event_dt->format( 'Y-m-d H:i:s' ),
					'duration'   => ( $i + 1 ) * HOUR_IN_SECONDS,
				]
			)->create()->ID;
		}

		// Create an event that will be deleted.
		$event_ts               = strtotime( '2025-01-01 00:00:00' ) + ( 3 * DAY_IN_SECONDS );
		$event_dt               = new \DateTime( "@$event_ts" );
		$this->deleted_event_id = tribe_events()->set_args(
			[
				'title'      => 'Deleted Event 4',
				'status'     => 'publish',
				'start_date' => $event_dt->format( 'Y-m-d H:i:s' ),
				'duration'   => 4 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		$events_ids[]           = $this->deleted_event_id;

		return $events_ids;
	}

	/**
	 * Create test tickets.
	 *
	 * @param array $event_ids
	 * @param array $number_of_tickets_per_event
	 *
	 * @return array
	 */
	protected function create_test_tickets( $event_ids, array $number_of_tickets_per_event = [ 1, 0, 2, 1 ] ) {
		$ticket_ids = [];

		foreach ( $event_ids as $key => $event_id ) {
			for ( $i = 0; $i < $number_of_tickets_per_event[ $key ]; $i ++ ) {
				$ticket_ids[] = $this->create_tc_ticket( $event_id );
			}
		}

		return $ticket_ids;
	}

	/**
	 * Prepare test data.
	 *
	 * @return array
	 */
	protected function prepare_test_data() {
		if ( ! empty( $this->ticket_ids ) ) {
			return [ $this->ticket_ids, $this->event_ids ];
		}

		$this->event_ids  = $this->create_test_events();
		$this->ticket_ids = $this->create_test_tickets( $this->event_ids );

		return [ $this->ticket_ids, $this->event_ids ];
	}

	/**
	 * Delete test data.
	 */
	protected function delete_test_data() {
		if ( ! empty( $this->ticket_ids ) ) {
			foreach ( $this->ticket_ids as $ticket_id ) {
				wp_delete_post( $ticket_id, true );
			}
		}

		if ( ! empty( $this->event_ids ) ) {
			foreach ( $this->event_ids as $event_id ) {
				wp_delete_post( $event_id, true );
			}
		}
	}

	// test
	public function test_is_on_page() {
		// Not on page.
		$this->assertFalse( $this->page->is_on_page(), 'Should return false when not on page.' );

		// On page.
		$this->set_fn_return( 'get_current_screen',  ( object ) [
			'id' => Page::$slug,
		] );
		$this->assertTrue( $this->page->is_on_page(), 'Should return true when on page.' );
	}

	// test
	public function test_get_url() {
		$this->assertEquals(
			'http://wordpress.test/wp-admin/admin.php?page=' . Page::$slug,
			$this->page->get_url(),
			'Should return regular URL when no arguments are passed.'
		);
		$this->assertEquals(
			'http://wordpress.test/wp-admin/admin.php?page=' . Page::$slug . '&s=some-value&var=some-other-page',
			$this->page->get_url( [
				's'    => 'some-value',
				'var'  => 'some-other-page',
			] ),
			'Should add to query URL when no arguments are passed.'
		);
	}

	// test
	public function test_render_tec_tickets_admin_tickets_page() {
		$this->prepare_test_data();

		// Delete event to test orphaned ticket scenario.
		wp_delete_post( $this->deleted_event_id, true );

		$_GET['status-filter'] = 'all';
		$_GET['provider-filter'] = addslashes( TicketsCommerce\Module::class );
		$this->set_class_fn_return( 'DateTime', 'diff', (object) [
			'days' => 999,
			'invert' => false,
		] );
		ob_start();
		$this->page->render_tec_tickets_admin_tickets_page();
		$actual = ob_get_clean();
		preg_match( '/name=\"_wpnonce\" value=\"([^\"]+)\"/', $actual, $matches );
		if ( count( $matches ) > 1 ) {
			$nonce = $matches[1];
			$actual = str_replace( $nonce, 'WP_NONCE', $actual );
		}
		$actual = str_replace( $this->event_ids, 'EVENT_ID', $actual );
		$actual = str_replace( $this->ticket_ids, 'TICKET_ID', $actual );
		$actual = preg_replace( '/Event \d/', 'Event EVENT_NUMBER', $actual );
		$this->assertMatchesHtmlSnapshot( $actual );

		$this->delete_test_data();
	}

	// test
	public function test_render_tec_tickets_no_tickets_page() {
		ob_start();
		$this->page->render_tec_tickets_admin_tickets_page();
		$actual = ob_get_clean();
		$this->assertMatchesHtmlSnapshot( $actual );
	}
}
