<?php
/**
 * Tests for the V1 RSVP Attendee Repository's privacy handler methods.
 *
 * @since TBD
 */

namespace TEC\Tickets\RSVP\V1;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\RSVP\Contracts\Attendee_Privacy_Handler;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe__Tickets__Repositories__Attendee__RSVP as RSVP_Attendee_Repository;
use WP_Post;

/**
 * Class Attendee_Repository_Test
 *
 * @since TBD
 */
class Attendee_Repository_Test extends WPTestCase {
	use Attendee_Maker;
	use RSVP_Ticket_Maker;

	/**
	 * @before
	 */
	public function set_up_rsvp(): void {
		add_filter( 'tribe_tickets_post_types', static function () {
			return [ 'post', 'tribe_events' ];
		} );

		add_filter( 'tribe_tickets_rsvp_send_mail', '__return_false' );
	}

	/**
	 * @test
	 */
	public function test_implements_attendee_privacy_handler_interface(): void {
		$repository = new RSVP_Attendee_Repository();

		$this->assertInstanceOf( Attendee_Privacy_Handler::class, $repository );
	}

	/**
	 * @test
	 */
	public function test_get_attendees_by_email_returns_matching_attendees(): void {
		$post_id   = $this->factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );
		$email     = 'test-privacy@example.com';

		$attendee_ids = [];
		for ( $i = 0; $i < 3; $i++ ) {
			$attendee_ids[] = $this->create_attendee_for_ticket( $ticket_id, $post_id, [
				'email' => $email,
			] );
		}

		$repository = new RSVP_Attendee_Repository();
		$result     = $repository->get_attendees_by_email( $email, 1, 10 );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'posts', $result );
		$this->assertArrayHasKey( 'has_more', $result );
		$this->assertCount( 3, $result['posts'] );
		$this->assertFalse( $result['has_more'] );

		$returned_ids = array_map( static function ( $post ) {
			return $post->ID;
		}, $result['posts'] );

		foreach ( $attendee_ids as $attendee_id ) {
			$this->assertContains( $attendee_id, $returned_ids );
		}
	}

	/**
	 * @test
	 */
	public function test_get_attendees_by_email_returns_wp_post_objects(): void {
		$post_id   = $this->factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );
		$email     = 'wppost-test@example.com';

		$this->create_attendee_for_ticket( $ticket_id, $post_id, [
			'email' => $email,
		] );

		$repository = new RSVP_Attendee_Repository();
		$result     = $repository->get_attendees_by_email( $email, 1, 10 );

		$this->assertNotEmpty( $result['posts'] );

		foreach ( $result['posts'] as $post ) {
			$this->assertInstanceOf( WP_Post::class, $post );
		}
	}

	/**
	 * @test
	 */
	public function test_get_attendees_by_email_pagination_works(): void {
		$post_id   = $this->factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );
		$email     = 'pagination-test@example.com';

		for ( $i = 0; $i < 5; $i++ ) {
			$this->create_attendee_for_ticket( $ticket_id, $post_id, [
				'email' => $email,
			] );
		}

		$repository = new RSVP_Attendee_Repository();

		$page1 = $repository->get_attendees_by_email( $email, 1, 2 );

		$this->assertCount( 2, $page1['posts'] );
		$this->assertTrue( $page1['has_more'] );

		$page2 = $repository->get_attendees_by_email( $email, 2, 2 );

		$this->assertCount( 2, $page2['posts'] );
		$this->assertTrue( $page2['has_more'] );

		$page3 = $repository->get_attendees_by_email( $email, 3, 2 );

		$this->assertCount( 1, $page3['posts'] );
		$this->assertFalse( $page3['has_more'] );

		$page1_ids = array_map( static fn( $p ) => $p->ID, $page1['posts'] );
		$page2_ids = array_map( static fn( $p ) => $p->ID, $page2['posts'] );
		$page3_ids = array_map( static fn( $p ) => $p->ID, $page3['posts'] );

		$this->assertEmpty( array_intersect( $page1_ids, $page2_ids ) );
		$this->assertEmpty( array_intersect( $page2_ids, $page3_ids ) );
		$this->assertEmpty( array_intersect( $page1_ids, $page3_ids ) );
	}

	/**
	 * @test
	 */
	public function test_get_attendees_by_email_returns_empty_for_no_matches(): void {
		$post_id   = $this->factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$this->create_attendee_for_ticket( $ticket_id, $post_id, [
			'email' => 'existing@example.com',
		] );

		$repository = new RSVP_Attendee_Repository();
		$result     = $repository->get_attendees_by_email( 'nonexistent@example.com', 1, 10 );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result['posts'] );
		$this->assertFalse( $result['has_more'] );
	}

	/**
	 * @test
	 */
	public function test_delete_attendee_removes_post(): void {
		$post_id     = $this->factory()->post->create();
		$ticket_id   = $this->create_rsvp_ticket( $post_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id );

		$this->assertInstanceOf( WP_Post::class, get_post( $attendee_id ) );

		$repository = new RSVP_Attendee_Repository();
		$result     = $repository->delete_attendee( $attendee_id );

		$this->assertTrue( $result['success'] );
		$this->assertNull( get_post( $attendee_id ) );
	}

	/**
	 * @test
	 */
	public function test_delete_attendee_returns_event_id(): void {
		$post_id     = $this->factory()->post->create();
		$ticket_id   = $this->create_rsvp_ticket( $post_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id );

		$repository = new RSVP_Attendee_Repository();
		$result     = $repository->delete_attendee( $attendee_id );

		$this->assertTrue( $result['success'] );
		$this->assertArrayHasKey( 'event_id', $result );
		$this->assertSame( $post_id, $result['event_id'] );
	}

	/**
	 * @test
	 */
	public function test_delete_attendee_returns_failure_for_invalid_id(): void {
		$repository = new RSVP_Attendee_Repository();
		$result     = $repository->delete_attendee( 999999 );

		$this->assertFalse( $result['success'] );
		$this->assertNull( $result['event_id'] );
	}

	/**
	 * @test
	 */
	public function test_get_ticket_id_returns_correct_product_id(): void {
		$post_id     = $this->factory()->post->create();
		$ticket_id   = $this->create_rsvp_ticket( $post_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id );

		$repository = new RSVP_Attendee_Repository();
		$result     = $repository->get_ticket_id( $attendee_id );

		$this->assertSame( $ticket_id, $result );
	}

	/**
	 * @test
	 */
	public function test_get_ticket_id_returns_zero_for_invalid_attendee(): void {
		$repository = new RSVP_Attendee_Repository();
		$result     = $repository->get_ticket_id( 999999 );

		$this->assertSame( 0, $result );
	}
}
