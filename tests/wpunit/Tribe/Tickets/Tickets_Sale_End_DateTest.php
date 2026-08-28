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

		$this->assertNotEmpty( $ticket_id );

		return $ticket_id;
	}
}
