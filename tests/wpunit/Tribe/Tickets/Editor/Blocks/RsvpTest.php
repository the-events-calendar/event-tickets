<?php

namespace Tribe\Tickets\Editor\Blocks;

use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe__Tickets__RSVP as RSVP;

/**
 * @covers \Tribe__Tickets__Editor__Blocks__Rsvp::rsvp_process
 */
class RsvpTest extends \Codeception\TestCase\WPAjaxTestCase {

	use RSVP_Ticket_Maker;

	public function setUp() {
		parent::setUp();

		// Enable the default `post` post type as ticket-able, as most fixtures use it.
		add_filter( 'tribe_tickets_post_types', function () {
			return [ 'post', 'tribe_events' ];
		} );

		// Avoid sending confirmation emails during tests.
		add_filter( 'tribe_tickets_rsvp_send_mail', '__return_false' );
	}

	public function _tearDown() {
		parent::_tearDown();

		// Prevent "Test code or tested code did not (only) close its own output buffers".
		ob_start();
	}

	/**
	 * @return \Tribe__Tickets__Editor__Blocks__Rsvp
	 */
	private function get_rsvp_block() {
		return tribe( 'tickets.editor.blocks.rsvp' );
	}

	/**
	 * @return RSVP
	 */
	private function get_rsvp_provider() {
		return tribe( 'tickets.rsvp' );
	}

	private function set_process_request( $ticket_id ) {
		$_REQUEST['action']                       = 'rsvp-process';
		$_REQUEST['ticket_id']                     = $ticket_id;
		$_POST['ticket_id']                        = $ticket_id;
		$_POST['nonce']                            = wp_create_nonce( 'tribe_tickets_rsvp_handle' );
		$_POST['product_id']                       = [ $ticket_id ];
		$_POST[ "quantity_{$ticket_id}" ]          = 1;
		$_POST['attendee']                         = [
			'full_name'    => 'Unauthorized RSVP',
			'email'        => 'attacker@example.test',
			'order_status' => 'yes',
			'optout'       => '0',
		];
	}

	private function process_and_capture() {
		try {
			$this->_handleAjax( 'rsvp-process' );
		} catch ( \WPAjaxDieContinueException $e ) {
			// Expected: rsvp_process() ends with wp_send_json_error()/wp_send_json_success().
		}

		return json_decode( $this->_last_response, true );
	}

	/**
	 * @test
	 */
	public function it_should_reject_unauthenticated_rsvp_for_private_event() {
		wp_set_current_user( 0 );

		$post_id   = $this->factory()->post->create( [ 'post_status' => 'private' ] );
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$this->set_process_request( $ticket_id );

		$response = $this->process_and_capture();

		$this->assertFalse( $response['success'], 'Unauthenticated RSVP processing for a private event must be rejected.' );
		$this->assertCount(
			0,
			$this->get_rsvp_provider()->get_attendees_by_id( $ticket_id ),
			'No attendee should have been created for the private event.'
		);
	}

	/**
	 * @test
	 */
	public function it_should_reject_unauthenticated_rsvp_for_password_protected_event() {
		wp_set_current_user( 0 );

		$post_id = $this->factory()->post->create( [
			'post_status'   => 'publish',
			'post_password' => 'secret',
		] );
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$this->set_process_request( $ticket_id );

		$response = $this->process_and_capture();

		$this->assertFalse( $response['success'], 'Unauthenticated RSVP processing for a password-protected event must be rejected.' );
		$this->assertCount(
			0,
			$this->get_rsvp_provider()->get_attendees_by_id( $ticket_id ),
			'No attendee should have been created for the password-protected event.'
		);
	}

	/**
	 * @test
	 */
	public function it_should_reject_a_private_event_ticket_smuggled_via_product_id() {
		wp_set_current_user( 0 );

		// The "cover" ticket: publicly accessible, used only to pass the outer ticket_id check.
		$public_post_id   = $this->factory()->post->create( [ 'post_status' => 'publish' ] );
		$public_ticket_id = $this->create_rsvp_ticket( $public_post_id );

		// The actual target: a private event's ticket, smuggled in via product_id[].
		$private_post_id   = $this->factory()->post->create( [ 'post_status' => 'private' ] );
		$private_ticket_id = $this->create_rsvp_ticket( $private_post_id );

		$this->set_process_request( $public_ticket_id );
		// Override product_id/quantity to point at the private ticket instead of the cover one.
		$_POST['product_id'] = [ $private_ticket_id ];
		unset( $_POST[ "quantity_{$public_ticket_id}" ] );
		$_POST[ "quantity_{$private_ticket_id}" ] = 1;

		$this->process_and_capture();

		$this->assertCount(
			0,
			$this->get_rsvp_provider()->get_attendees_by_id( $private_ticket_id ),
			'No attendee should have been created for the private event smuggled in via product_id.'
		);
	}

	/**
	 * @test
	 */
	public function it_should_reject_rsvp_without_a_valid_nonce() {
		wp_set_current_user( 0 );

		$post_id   = $this->factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$this->set_process_request( $ticket_id );
		$_POST['nonce'] = 'not-a-valid-nonce';

		$response = $this->process_and_capture();

		$this->assertFalse( $response['success'], 'RSVP processing without a valid nonce must be rejected.' );
		$this->assertCount(
			0,
			$this->get_rsvp_provider()->get_attendees_by_id( $ticket_id ),
			'No attendee should have been created without a valid nonce.'
		);
	}

	/**
	 * @test
	 */
	public function it_should_reject_rsvp_before_the_ticket_start_date() {
		wp_set_current_user( 0 );

		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_rsvp_ticket( $post_id, [
			'meta_input' => [
				'_ticket_start_date' => date( 'Y-m-d H:i:s', strtotime( '+1 day' ) ),
				'_ticket_end_date'   => date( 'Y-m-d H:i:s', strtotime( '+2 days' ) ),
			],
		] );

		$this->set_process_request( $ticket_id );

		$response = $this->process_and_capture();

		$this->assertFalse( $response['success'], 'RSVP processing before the ticket sale start date must be rejected.' );
		$this->assertCount(
			0,
			$this->get_rsvp_provider()->get_attendees_by_id( $ticket_id ),
			'No attendee should have been created before the ticket sale start date.'
		);
	}

	/**
	 * @test
	 */
	public function it_should_reject_rsvp_after_the_ticket_end_date() {
		wp_set_current_user( 0 );

		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_rsvp_ticket( $post_id, [
			'meta_input' => [
				'_ticket_start_date' => date( 'Y-m-d H:i:s', strtotime( '-2 days' ) ),
				'_ticket_end_date'   => date( 'Y-m-d H:i:s', strtotime( '-1 day' ) ),
			],
		] );

		$this->set_process_request( $ticket_id );

		$response = $this->process_and_capture();

		$this->assertFalse( $response['success'], 'RSVP processing after the ticket sale end date must be rejected.' );
		$this->assertCount(
			0,
			$this->get_rsvp_provider()->get_attendees_by_id( $ticket_id ),
			'No attendee should have been created after the ticket sale end date.'
		);
	}

	/**
	 * @test
	 */
	public function it_should_still_allow_unauthenticated_rsvp_for_a_public_event() {
		wp_set_current_user( 0 );

		$post_id   = $this->factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$this->set_process_request( $ticket_id );

		$response = $this->process_and_capture();

		$this->assertTrue( $response['success'], 'Unauthenticated RSVP processing for a public event should still succeed.' );
		$this->assertCount(
			1,
			$this->get_rsvp_provider()->get_attendees_by_id( $ticket_id ),
			'An attendee should have been created for the public event.'
		);
	}
}
