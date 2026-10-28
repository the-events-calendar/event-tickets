<?php

namespace Tribe\Tickets;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Attendee;
use TEC\Tickets\Commerce\Module as Commerce;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Traits\With_Ticket_Providers;
use Tribe__Tickets__Metabox as Metabox;
use WPDieException;

/**
 * Checking an attendee in, or undoing it, needs the right to manage the attendees of the attendee's own event.
 */
class Metabox_Attendee_Checkin_Permission_Test extends WPTestCase {
	use Order_Maker;
	use With_Ticket_Providers;
	use With_Uopz;

	/**
	 * @after
	 */
	public function reset_request(): void {
		$_POST = [];
	}

	/**
	 * @test
	 */
	public function it_should_not_check_in_an_attendee_of_another_event_through_an_event_the_user_manages(): void {
		$attendee_id = $this->make_attendee( static::factory()->post->create() );
		$own_draft   = $this->log_in_as_contributor_with_own_draft();
		// Community Events lets an organizer manage the attendees of their own events.
		add_filter(
			'tribe_tickets_user_can_manage_attendees',
			static fn( $user_can, $user_id, $event_id ) => $own_draft === absint( $event_id ) ? true : $user_can,
			10,
			3
		);

		$checked_in   = $this->request_checkin( 'ajax_attendee_checkin', 'checkin', $attendee_id, $own_draft );
		$unchecked_in = $this->request_checkin( 'ajax_attendee_uncheckin', 'uncheckin', $attendee_id, $own_draft );

		$this->assertFalse( $checked_in );
		$this->assertFalse( $unchecked_in );
		$this->assertEmpty( get_post_meta( $attendee_id, Attendee::$checked_in_meta_key, true ) );
	}

	/**
	 * @test
	 */
	public function it_should_let_an_editor_check_in_an_attendee_of_another_authors_event(): void {
		$post_id     = $this->log_in_as_editor_of_another_authors_post();
		$attendee_id = $this->make_attendee( $post_id );

		$checked_in = $this->request_checkin( 'ajax_attendee_checkin', 'checkin', $attendee_id, $post_id );

		$this->assertTrue( $checked_in );
		$this->assertNotEmpty( get_post_meta( $attendee_id, Attendee::$checked_in_meta_key, true ) );

		$unchecked_in = $this->request_checkin( 'ajax_attendee_uncheckin', 'uncheckin', $attendee_id, $post_id );

		$this->assertTrue( $unchecked_in );
		$this->assertEmpty( get_post_meta( $attendee_id, Attendee::$checked_in_meta_key, true ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_check_in_an_attendee_of_another_event_when_a_plugin_grants_attendee_management(): void {
		$attendee_id = $this->make_attendee( static::factory()->post->create() );
		$own_draft   = $this->log_in_as_contributor_with_own_draft();
		// Community Events grants attendee management to any logged-in user in the admin when an event is given.
		add_filter( 'tribe_tickets_user_can_manage_attendees', '__return_true' );

		$checked_in   = $this->request_checkin( 'ajax_attendee_checkin', 'checkin', $attendee_id, $own_draft );
		$unchecked_in = $this->request_checkin( 'ajax_attendee_uncheckin', 'uncheckin', $attendee_id, $own_draft );

		$this->assertFalse( $checked_in );
		$this->assertFalse( $unchecked_in );
		$this->assertEmpty( get_post_meta( $attendee_id, Attendee::$checked_in_meta_key, true ) );
	}

	/**
	 * @test
	 */
	public function it_should_let_an_author_check_in_an_attendee_of_their_own_event(): void {
		$post_id     = $this->log_in_as_author_with_own_post();
		$attendee_id = $this->make_attendee( $post_id );

		$checked_in = $this->request_checkin( 'ajax_attendee_checkin', 'checkin', $attendee_id, $post_id );

		$this->assertTrue( $checked_in );
		$this->assertNotEmpty( get_post_meta( $attendee_id, Attendee::$checked_in_meta_key, true ) );

		$unchecked_in = $this->request_checkin( 'ajax_attendee_uncheckin', 'uncheckin', $attendee_id, $post_id );

		$this->assertTrue( $unchecked_in );
		$this->assertEmpty( get_post_meta( $attendee_id, Attendee::$checked_in_meta_key, true ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_check_in_when_a_plugin_narrows_attendee_management(): void {
		$post_id     = $this->log_in_as_editor_of_another_authors_post();
		$attendee_id = $this->make_attendee( $post_id );
		add_filter( 'tribe_tickets_user_can_manage_attendees', '__return_false' );

		$checked_in = $this->request_checkin( 'ajax_attendee_checkin', 'checkin', $attendee_id, $post_id );

		$this->assertFalse( $checked_in );
		$this->assertEmpty( get_post_meta( $attendee_id, Attendee::$checked_in_meta_key, true ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_check_in_through_a_requested_event_the_user_cannot_edit(): void {
		$post_id         = $this->log_in_as_author_with_own_post();
		$attendee_id     = $this->make_attendee( $post_id );
		$forbidden_event = static::factory()->post->create( [ 'post_author' => static::factory()->user->create() ] );

		$checked_in = $this->request_checkin( 'ajax_attendee_checkin', 'checkin', $attendee_id, $forbidden_event );

		$this->assertFalse( $checked_in );
		$this->assertEmpty( get_post_meta( $attendee_id, Attendee::$checked_in_meta_key, true ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_check_in_a_post_that_is_not_an_attendee(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_ticket( $post_id );

		$checked_in = $this->request_checkin( 'ajax_attendee_checkin', 'checkin', $ticket_id, $post_id );

		$this->assertFalse( $checked_in );
		$this->assertEmpty( get_post_meta( $ticket_id, Attendee::$checked_in_meta_key, true ) );
	}

	private function make_attendee( int $post_id ): int {
		$order = $this->create_order( [ $this->create_tc_ticket( $post_id ) => 1 ] );

		return current( tribe( Commerce::class )->get_attendees_by_order_id( $order->ID ) )['attendee_id'];
	}

	/**
	 * @return bool Whether the handler answered that the attendee was checked in, or that the check-in was undone.
	 */
	private function request_checkin( string $action, string $nonce_action, int $attendee_id, int $event_id ): bool {
		$_POST = [
			'nonce'       => wp_create_nonce( $nonce_action ),
			'provider'    => Commerce::class,
			'attendee_id' => $attendee_id,
			'event_ID'    => $event_id,
		];

		// Both end the request in production; stop the handler where they are called.
		$this->set_fn_return( 'wp_send_json_success', static function ( $data ) { throw new WPDieException( wp_json_encode( $data ) ); }, true );
		$this->set_fn_return( 'wp_send_json_error', static function () { throw new WPDieException( '{}' ); }, true );

		try {
			tribe( Metabox::class )->{$action}();
		} catch ( WPDieException $sent ) {
			return in_array( true, json_decode( $sent->getMessage(), true ), true );
		}

		return false;
	}
}
