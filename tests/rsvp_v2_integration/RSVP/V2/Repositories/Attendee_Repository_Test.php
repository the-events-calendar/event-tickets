<?php
/**
 * Tests for the V2 Attendee Repository.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2\Repositories
 */

namespace TEC\Tickets\RSVP\V2\Repositories;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Attendee;
use TEC\Tickets\RSVP\Contracts\Attendee_Privacy_Handler;
use Tribe\Tickets\Test\RSVP_V2\TC_RSVP_Attendee_Maker;
use Tribe\Tickets\Test\RSVP_V2\TC_RSVP_Ticket_Maker;
use WP_Post;

/**
 * Class Attendee_Repository_Test
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2\Repositories
 */
class Attendee_Repository_Test extends WPTestCase {
	use TC_RSVP_Ticket_Maker;
	use TC_RSVP_Attendee_Maker;

	/**
	 * @before
	 */
	public function set_up_ticketable_post_types(): void {
		// Enable post as ticket type.
		add_filter( 'tribe_tickets_post_types', static function () {
			return [ 'post' ];
		} );
	}

	/**
	 * @test
	 */
	public function it_should_filter_by_event(): void {
		$post_1_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );
		$post_2_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );

		$ticket_1_id = $this->create_tc_rsvp_ticket( $post_1_id );
		$ticket_2_id = $this->create_tc_rsvp_ticket( $post_2_id );

		$attendee_1_id = $this->create_tc_rsvp_attendee( $ticket_1_id, $post_1_id );
		$attendee_2_id = $this->create_tc_rsvp_attendee( $ticket_2_id, $post_2_id );

		$repo = new Attendee_Repository();
		$attendees = $repo->by( 'event', $post_1_id )->all();

		$attendee_ids = array_map( static fn( $attendee ) => $attendee->ID, $attendees );

		$this->assertContains( $attendee_1_id, $attendee_ids, 'Attendee for post 1 should be returned' );
		$this->assertNotContains( $attendee_2_id, $attendee_ids, 'Attendee for post 2 should not be returned' );
	}

	/**
	 * @test
	 */
	public function it_should_filter_by_ticket(): void {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );

		$ticket_1_id = $this->create_tc_rsvp_ticket( $post_id );
		$ticket_2_id = $this->create_tc_rsvp_ticket( $post_id );

		$attendee_1_id = $this->create_tc_rsvp_attendee( $ticket_1_id, $post_id );
		$attendee_2_id = $this->create_tc_rsvp_attendee( $ticket_2_id, $post_id );

		$repo = new Attendee_Repository();
		$attendees = $repo->by( 'ticket', $ticket_1_id )->all();

		$attendee_ids = array_map( static fn( $attendee ) => $attendee->ID, $attendees );

		$this->assertContains( $attendee_1_id, $attendee_ids, 'Attendee for ticket 1 should be returned' );
		$this->assertNotContains( $attendee_2_id, $attendee_ids, 'Attendee for ticket 2 should not be returned' );
	}

	/**
	 * @test
	 */
	public function it_should_filter_by_going_status(): void {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );

		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		// Create going attendees.
		$going_ids = $this->create_going_tc_rsvp_attendees( 2, $ticket_id, $post_id );

		// Create not going attendees.
		$not_going_ids = $this->create_not_going_tc_rsvp_attendees( 3, $ticket_id, $post_id );

		$repo = new Attendee_Repository();
		$going_attendees = $repo->by( 'going', true )->all();

		$going_attendee_ids = array_map( static fn( $attendee ) => $attendee->ID, $going_attendees );

		foreach ( $going_ids as $going_id ) {
			$this->assertContains( $going_id, $going_attendee_ids, 'Going attendee should be returned' );
		}

		foreach ( $not_going_ids as $not_going_id ) {
			$this->assertNotContains( $not_going_id, $going_attendee_ids, 'Not going attendee should not be returned' );
		}
	}

	/**
	 * @test
	 */
	public function it_should_filter_by_not_going_status(): void {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );

		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		// Create going attendees.
		$going_ids = $this->create_going_tc_rsvp_attendees( 2, $ticket_id, $post_id );

		// Create not going attendees.
		$not_going_ids = $this->create_not_going_tc_rsvp_attendees( 3, $ticket_id, $post_id );

		$repo = new Attendee_Repository();
		$not_going_attendees = $repo->by( 'not_going', true )->all();

		$not_going_attendee_ids = array_map( static fn( $attendee ) => $attendee->ID, $not_going_attendees );

		foreach ( $not_going_ids as $not_going_id ) {
			$this->assertContains( $not_going_id, $not_going_attendee_ids, 'Not going attendee should be returned' );
		}

		foreach ( $going_ids as $going_id ) {
			$this->assertNotContains( $going_id, $not_going_attendee_ids, 'Going attendee should not be returned' );
		}
	}

	/**
	 * @test
	 */
	public function it_should_filter_by_rsvp_status_meta(): void {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );

		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		// Create attendees with different statuses.
		$yes_attendee_id = $this->create_tc_rsvp_attendee( $ticket_id, $post_id, [ 'rsvp_status' => 'yes' ] );
		$no_attendee_id = $this->create_tc_rsvp_attendee( $ticket_id, $post_id, [ 'rsvp_status' => 'no' ] );

		$repo = new Attendee_Repository();
		$yes_attendees = $repo->by( 'rsvp_status', 'yes' )->all();

		$this->assertCount( 1, $yes_attendees, 'Should return only one attendee with status yes' );
		$this->assertSame( $yes_attendee_id, $yes_attendees[0]->ID );
	}

	/**
	 * @test
	 */
	public function it_should_return_count(): void {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );

		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		$this->create_many_tc_rsvp_attendees( 5, $ticket_id, $post_id );

		$repo = new Attendee_Repository();
		$count = $repo->count();

		$this->assertSame( 5, $count );
	}

	/**
	 * @test
	 */
	public function it_should_chain_multiple_filters(): void {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );

		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		// Create going and not going attendees.
		$this->create_going_tc_rsvp_attendees( 3, $ticket_id, $post_id );
		$this->create_not_going_tc_rsvp_attendees( 2, $ticket_id, $post_id );

		$repo = new Attendee_Repository();
		$going_for_event = $repo
			->by( 'event', $post_id )
			->by( 'going', true )
			->all();

		$this->assertCount( 3, $going_for_event, 'Should return only going attendees for the event' );
	}

	/**
	 * @test
	 */
	public function test_implements_attendee_privacy_handler_interface(): void {
		$repo = new Attendee_Repository();

		$this->assertInstanceOf(
			Attendee_Privacy_Handler::class,
			$repo,
			'Attendee_Repository should implement Attendee_Privacy_Handler interface'
		);
	}

	/**
	 * @test
	 */
	public function test_get_attendees_by_email_returns_matching_attendees(): void {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		$target_email = 'target@example.com';
		$other_email = 'other@example.com';

		$target_attendee_1 = $this->create_tc_rsvp_attendee( $ticket_id, $post_id, [ 'email' => $target_email ] );
		$target_attendee_2 = $this->create_tc_rsvp_attendee( $ticket_id, $post_id, [ 'email' => $target_email ] );
		$other_attendee = $this->create_tc_rsvp_attendee( $ticket_id, $post_id, [ 'email' => $other_email ] );

		$repo = new Attendee_Repository();
		$result = $repo->get_attendees_by_email( $target_email, 1, 10 );

		$this->assertArrayHasKey( 'posts', $result );
		$this->assertArrayHasKey( 'has_more', $result );
		$this->assertCount( 2, $result['posts'] );

		$ids = array_map( fn( $post ) => $post->ID, $result['posts'] );
		$this->assertContains( $target_attendee_1, $ids );
		$this->assertContains( $target_attendee_2, $ids );
		$this->assertNotContains( $other_attendee, $ids );
	}

	/**
	 * @test
	 */
	public function test_get_attendees_by_email_returns_wp_post_objects(): void {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		$this->create_tc_rsvp_attendee( $ticket_id, $post_id, [ 'email' => 'test@example.com' ] );

		$repo = new Attendee_Repository();
		$result = $repo->get_attendees_by_email( 'test@example.com', 1, 10 );

		$this->assertCount( 1, $result['posts'] );
		$this->assertInstanceOf( WP_Post::class, $result['posts'][0] );
	}

	/**
	 * @test
	 */
	public function test_get_attendees_by_email_only_returns_rsvp_attendees(): void {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		$shared_email = 'shared@example.com';

		$rsvp_attendee = $this->create_tc_rsvp_attendee( $ticket_id, $post_id, [ 'email' => $shared_email ] );

		// Create a plain TC attendee without RSVP status meta.
		$tc_attendee_id = wp_insert_post( [
			'post_type'   => Attendee::POSTTYPE,
			'post_status' => 'publish',
			'post_title'  => 'Plain TC Attendee',
			'meta_input'  => [
				Attendee::$event_relation_meta_key  => $post_id,
				Attendee::$ticket_relation_meta_key => $ticket_id,
				Attendee::$email_meta_key           => $shared_email,
			],
		] );

		$repo = new Attendee_Repository();
		$result = $repo->get_attendees_by_email( $shared_email, 1, 10 );

		$ids = array_map( fn( $post ) => $post->ID, $result['posts'] );
		$this->assertContains( $rsvp_attendee, $ids, 'RSVP attendee should be included' );
		$this->assertNotContains( $tc_attendee_id, $ids, 'Non-RSVP TC attendee should be excluded' );
	}

	/**
	 * @test
	 */
	public function test_get_attendees_by_email_pagination_works(): void {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		$email = 'paginated@example.com';

		for ( $i = 0; $i < 5; $i++ ) {
			$this->create_tc_rsvp_attendee( $ticket_id, $post_id, [ 'email' => $email ] );
		}

		$repo = new Attendee_Repository();

		$page_1 = $repo->get_attendees_by_email( $email, 1, 2 );
		$this->assertCount( 2, $page_1['posts'] );
		$this->assertTrue( $page_1['has_more'] );

		$page_2 = ( new Attendee_Repository() )->get_attendees_by_email( $email, 2, 2 );
		$this->assertCount( 2, $page_2['posts'] );
		$this->assertTrue( $page_2['has_more'] );

		$page_3 = ( new Attendee_Repository() )->get_attendees_by_email( $email, 3, 2 );
		$this->assertCount( 1, $page_3['posts'] );
		$this->assertFalse( $page_3['has_more'] );

		$page_1_ids = array_map( fn( $post ) => $post->ID, $page_1['posts'] );
		$page_2_ids = array_map( fn( $post ) => $post->ID, $page_2['posts'] );
		$page_3_ids = array_map( fn( $post ) => $post->ID, $page_3['posts'] );
		$all_ids = array_merge( $page_1_ids, $page_2_ids, $page_3_ids );
		$this->assertCount( 5, array_unique( $all_ids ), 'All pages together should return 5 unique attendees' );
	}

	/**
	 * @test
	 */
	public function test_get_attendees_by_email_returns_empty_for_no_matches(): void {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		$this->create_tc_rsvp_attendee( $ticket_id, $post_id, [ 'email' => 'existing@example.com' ] );

		$repo = new Attendee_Repository();
		$result = $repo->get_attendees_by_email( 'nonexistent@example.com', 1, 10 );

		$this->assertEmpty( $result['posts'] );
		$this->assertFalse( $result['has_more'] );
	}

	/**
	 * @test
	 */
	public function test_delete_attendee_removes_post(): void {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );
		$attendee_id = $this->create_tc_rsvp_attendee( $ticket_id, $post_id );

		$this->assertInstanceOf( WP_Post::class, get_post( $attendee_id ), 'Attendee should exist before deletion' );

		$repo = new Attendee_Repository();
		$result = $repo->delete_attendee( $attendee_id );

		$this->assertTrue( $result['success'] );
		$this->assertNull( get_post( $attendee_id ), 'Attendee should not exist after deletion' );
	}

	/**
	 * @test
	 */
	public function test_delete_attendee_returns_event_id(): void {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );
		$attendee_id = $this->create_tc_rsvp_attendee( $ticket_id, $post_id );

		$repo = new Attendee_Repository();
		$result = $repo->delete_attendee( $attendee_id );

		$this->assertArrayHasKey( 'event_id', $result );
		$this->assertSame( $post_id, $result['event_id'] );
	}

	/**
	 * @test
	 */
	public function test_get_ticket_id_returns_correct_product_id(): void {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );
		$attendee_id = $this->create_tc_rsvp_attendee( $ticket_id, $post_id );

		$repo = new Attendee_Repository();
		$result = $repo->get_ticket_id( $attendee_id );

		$this->assertSame( $ticket_id, $result );
	}

	/**
	 * @test
	 */
	public function test_get_ticket_id_returns_zero_for_invalid_attendee(): void {
		$repo = new Attendee_Repository();
		$result = $repo->get_ticket_id( 999999 );

		$this->assertSame( 0, $result );
	}

	/**
	 * @test
	 */
	public function test_get_field_returns_full_name(): void {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		$full_name = 'John Doe';
		$attendee_id = $this->create_tc_rsvp_attendee( $ticket_id, $post_id, [ 'full_name' => $full_name ] );

		$repo = new Attendee_Repository();
		$result = $repo->get_field( $attendee_id, 'full_name' );

		$this->assertSame( $full_name, $result );
	}

	/**
	 * @test
	 */
	public function test_get_field_returns_email(): void {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		$email = 'john.doe@example.com';
		$attendee_id = $this->create_tc_rsvp_attendee( $ticket_id, $post_id, [ 'email' => $email ] );

		$repo = new Attendee_Repository();
		$result = $repo->get_field( $attendee_id, 'email' );

		$this->assertSame( $email, $result );
	}
}
