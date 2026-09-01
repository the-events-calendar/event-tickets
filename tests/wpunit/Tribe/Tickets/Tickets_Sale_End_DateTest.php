<?php

namespace Tribe\Tickets;

use lucatume\WPBrowser\TestCase\WPTestCase as WPBrowserTestCase;

/**
 * Covers the sale end date manual update flag set when a ticket is created.
 *
 * The block editor always submits a sale end date, so the flag must only be
 * added when that date differs from the event start date; otherwise a default
 * ticket stops following event start changes.
 */
class Tickets_Sale_End_DateTest extends WPBrowserTestCase {

	/**
	 * {@inheritdoc}
	 */
	public function setUp() {
		parent::setUp();

		// Enable posts as ticket-able posts.
		add_filter( 'tribe_tickets_post_types', function () {
			return [ 'post' ];
		} );
	}

	/**
	 * It should flag a custom sale end date on ticket creation.
	 *
	 * @test
	 */
	public function should_flag_custom_sale_end_date_on_creation() {
		$post_id = $this->factory->post->create();
		update_post_meta( $post_id, '_EventStartDate', '2026-08-28 08:00:00' );

		$ticket_id = $this->create_ticket(
			$post_id,
			[
				'ticket_end_date' => '2026-09-15',
				'ticket_end_time' => '08:00:00',
			]
		);

		$this->assertContains( '_ticket_end_date', get_post_meta( $ticket_id, '_tribe_ticket_manual_updated' ) );
	}

	/**
	 * It should not flag a default sale end date on ticket creation.
	 *
	 * @test
	 */
	public function should_not_flag_default_sale_end_date_on_creation() {
		$post_id = $this->factory->post->create();
		update_post_meta( $post_id, '_EventStartDate', '2026-08-28 08:00:00' );

		$ticket_id = $this->create_ticket(
			$post_id,
			[
				'ticket_end_date' => '2026-08-28',
				'ticket_end_time' => '08:00:00',
			]
		);

		$this->assertEmpty( get_post_meta( $ticket_id, '_tribe_ticket_manual_updated' ) );
	}

	/**
	 * It should not flag a ticket created without a sale end date.
	 *
	 * @test
	 */
	public function should_not_flag_ticket_created_without_end_date() {
		$post_id = $this->factory->post->create();
		update_post_meta( $post_id, '_EventStartDate', '2026-08-28 08:00:00' );

		$ticket_id = $this->create_ticket( $post_id, [ 'ticket_end_date' => '' ] );

		$this->assertEmpty( get_post_meta( $ticket_id, '_tribe_ticket_manual_updated' ) );
	}

	/**
	 * It should keep a custom sale end date when the event start date changes.
	 *
	 * @test
	 */
	public function should_keep_custom_sale_end_date_when_event_start_changes() {
		$post_id = $this->factory->post->create();
		update_post_meta( $post_id, '_EventStartDate', '2026-08-28 08:00:00' );

		$ticket_id = $this->create_ticket(
			$post_id,
			[
				'ticket_end_date' => '2026-09-15',
				'ticket_end_time' => '08:00:00',
			]
		);

		update_post_meta( $post_id, '_EventStartDate', '2026-10-01 08:00:00' );

		$this->assertEquals( '2026-09-15 08:00:00', get_post_meta( $ticket_id, '_ticket_end_date', true ) );
	}

	/**
	 * It should sync a default sale end date when the event start date changes.
	 *
	 * @test
	 */
	public function should_sync_default_sale_end_date_when_event_start_changes() {
		$post_id = $this->factory->post->create();
		update_post_meta( $post_id, '_EventStartDate', '2026-08-28 08:00:00' );

		$ticket_id = $this->create_ticket(
			$post_id,
			[
				'ticket_end_date' => '2026-08-28',
				'ticket_end_time' => '08:00:00',
			]
		);

		update_post_meta( $post_id, '_EventStartDate', '2026-10-01 08:00:00' );

		$this->assertEquals( '2026-10-01 08:00:00', get_post_meta( $ticket_id, '_ticket_end_date', true ) );
	}

	/**
	 * It should only add the manual update flag once per ticket creation.
	 *
	 * @test
	 */
	public function should_only_add_manual_update_flag_once() {
		$post_id = $this->factory->post->create();
		update_post_meta( $post_id, '_EventStartDate', '2026-08-28 08:00:00' );

		$ticket_id = $this->create_ticket(
			$post_id,
			[
				'ticket_end_date' => '2026-09-15',
				'ticket_end_time' => '08:00:00',
			]
		);

		$manual_updates = get_post_meta( $ticket_id, '_tribe_ticket_manual_updated' );
		$this->assertCount( 1, $manual_updates, 'Manual update flag should only be added once' );
		$this->assertContains( '_ticket_end_date', $manual_updates );
	}

	/**
	 * It demonstrates that a raw add_post_meta() call allows duplicate meta entries.
	 *
	 * add_post_meta() does not check for existing values by default, so calling it
	 * directly (bypassing the has_manual_update() guard) multiple times with the same
	 * key/value will keep appending entries. This is why the flag must always be
	 * added through a has_manual_update() check, never with a bare add_post_meta() call.
	 *
	 * @test
	 */
	public function demonstrates_raw_add_post_meta_allows_duplicates() {
		$ticket_id = $this->factory->post->create( [ 'post_type' => 'tribe_rsvp_tickets' ] );

		add_post_meta( $ticket_id, '_tribe_ticket_manual_updated', '_ticket_end_date' );
		add_post_meta( $ticket_id, '_tribe_ticket_manual_updated', '_ticket_end_date' );

		$manual_updates = get_post_meta( $ticket_id, '_tribe_ticket_manual_updated' );
		$this->assertCount( 2, $manual_updates, 'A bare add_post_meta() call allows duplicate entries with the same key and value' );
		$this->assertEquals( [ '_ticket_end_date', '_ticket_end_date' ], $manual_updates );
	}

	/**
	 * It should not duplicate the manual update flag if the guarded add is run more than once.
	 *
	 * Mirrors the fixed code path in Tribe__Tickets__Tickets::ticket_add(): the flag is only
	 * added when has_manual_update() is false for that key, so re-running the same guarded
	 * logic against a ticket that already carries the flag is a no-op.
	 *
	 * @test
	 */
	public function should_not_duplicate_manual_update_flag_when_guarded_add_runs_twice() {
		/** @var \Tribe__Tickets__Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );

		$ticket_id = $this->factory->post->create( [ 'post_type' => 'tribe_rsvp_tickets' ] );

		$add_flag_if_missing = static function () use ( $tickets_handler, $ticket_id ) {
			if ( ! $tickets_handler->has_manual_update( $ticket_id, $tickets_handler->key_end_date ) ) {
				add_post_meta( $ticket_id, $tickets_handler->key_manual_updated, $tickets_handler->key_end_date );
			}
		};

		$add_flag_if_missing();
		$add_flag_if_missing();
		$add_flag_if_missing();

		$manual_updates = get_post_meta( $ticket_id, '_tribe_ticket_manual_updated' );
		$this->assertCount( 1, $manual_updates, 'The has_manual_update() guard must prevent duplicate entries across repeated calls' );
	}

	/**
	 * Creates a ticket through the RSVP provider using the shared ticket_add() path.
	 *
	 * @param int   $post_id   The ticket-able post ID.
	 * @param array $overrides Data overrides, mirroring the block editor REST payload keys.
	 *
	 * @return int The created ticket ID.
	 */
	private function create_ticket( $post_id, array $overrides = [] ) {
		/** @var \Tribe__Tickets__RSVP $rsvp */
		$rsvp = tribe( 'tickets.rsvp' );

		$data = array_merge(
			[
				'ticket_name'             => 'Test ticket',
				'ticket_description'      => 'Test ticket description',
				'ticket_price'            => 0,
				'ticket_show_description' => 'yes',
				'ticket_start_date'       => '2026-08-01',
				'ticket_start_time'       => '08:00:00',
				'ticket_end_date'         => '2026-09-15',
				'ticket_end_time'         => '08:00:00',
			],
			$overrides
		);

		$ticket_id = $rsvp->ticket_add( $post_id, $data );

		$this->assertIsInt( $ticket_id );

		return $ticket_id;
	}
}
