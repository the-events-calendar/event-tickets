<?php
namespace Tribe\Tickets;

use Tribe__Events__Main as Main;
use Tribe__Tickets__Cache__Transient_Cache as Cache;

class Transient_CacheTest extends \Codeception\TestCase\WPTestCase {

	public function setUp() {
		// before
		parent::setUp();

		// your set up methods here
		( new Cache() )->reset_all();
	}

	public function tearDown() {
		// your tear down methods here

		// then
		parent::tearDown();
	}

	public function _before() {
		tribe_events()->per_page( -1 )->delete();
		tribe_tickets()->per_page( -1 )->delete();
	}

	/**
	 * @test
	 * it should be instantiatable
	 */
	public function it_should_be_instantiatable() {
		$sut = $this->make_instance();

		$this->assertInstanceOf( Cache::class, $sut );
	}

	/**
	 * @return Cache
	 */
	private function make_instance() {

		return new Cache();
	}

	/**
	 * @test
	 * it should return a list of all the posts without a ticket
	 */
	public function it_should_return_a_list_of_all_the_posts_without_a_ticket() {
		tribe_update_option( 'ticket-enabled-post-types', [ 'page', 'post' ] );

		$ids_without_tickets = $this->factory()->post->create_many( 3, [ 'post_type' => 'post' ] );
		$ids_without_tickets = array_merge( $ids_without_tickets, $this->factory()->post->create_many( 3, [ 'post_type' => 'page' ] ) );

		$sut = $this->make_instance();
		$ids = $sut->posts_without_ticket_types();

		$this->assertEqualSets( $ids_without_tickets, $ids );
	}

	/**
	 * @test
	 * it should return a list of all the posts with a ticket assigned
	 */
	public function it_should_return_a_list_of_all_the_posts_with_a_ticket_assigned() {
		tribe_update_option( 'ticket-enabled-post-types', [ 'page', 'post' ] );

		$posts_with_tickets = $this->factory()->post->create_many( 3, [ 'post_type' => 'post' ] );
		$posts_with_tickets = array_merge( $posts_with_tickets, $this->factory()->post->create_many( 3, [ 'post_type' => 'page' ] ) );

		// not relevant, any post type can be related to any other post type as "a ticket"
		$ticket_post_type = 'ticket_type';
		register_post_type( $ticket_post_type );

		// create a ticket for each post and relate the ticket to the post
		foreach ( $posts_with_tickets as $id ) {
			$ticket_id = $this->factory()->post->create( [ 'post_type' => $ticket_post_type ] );
			update_post_meta( $ticket_id, '_tribe_some_kind_of_ticket_for_event', $id );
		}

		$sut = $this->make_instance();
		$ids = $sut->posts_with_ticket_types();

		$this->assertEqualSets( $posts_with_tickets, $ids );
	}

	/**
	 * @test
	 * it should not list posts with tickets but unsupported
	 */
	public function it_should_not_list_posts_with_tickets_but_unsupported() {
		tribe_update_option( 'ticket-enabled-post-types', [ 'page', 'post' ] );
		register_post_type( 'unsupported_type' );

		$batch_1 = $this->factory()->post->create_many( 3, [ 'post_type' => 'post' ] );
		$batch_2 = $this->factory()->post->create_many( 3, [ 'post_type' => 'page' ] );
		$batch_3 = $this->factory()->post->create_many( 3, [ 'post_type' => 'unsupported_type' ] );

		// not relevant, any post type can be related to any other post type as "a ticket"
		$ticket_post_type = 'ticket_type';
		register_post_type( $ticket_post_type );

		// create a ticket for each post and relate the ticket to the post
		foreach ( array_merge( $batch_1, $batch_2, $batch_3 ) as $id ) {
			$ticket_id = $this->factory()->post->create( [ 'post_type' => $ticket_post_type ] );
			update_post_meta( $ticket_id, '_tribe_some_kind_of_ticket_for_event', $id );
		}

		$sut = $this->make_instance();
		$ids = $sut->posts_with_ticket_types();

		$this->assertEqualSets( array_merge( $batch_1, $batch_2 ), $ids );
	}

	/**
	 * @test
	 * it should not list posts without tickets but of unsupported
	 * More of a sanity check here.
	 */
	public function it_should_not_list_posts_without_tickets_but_of_unsupported() {
		tribe_update_option( 'ticket-enabled-post-types', [ 'page', 'post' ] );
		register_post_type( 'unsupported_type' );

		$batch_1 = $this->factory()->post->create_many( 3, [ 'post_type' => 'post' ] );
		$batch_2 = $this->factory()->post->create_many( 3, [ 'post_type' => 'page' ] );
		$batch_3 = $this->factory()->post->create_many( 3, [ 'post_type' => 'unsupported_type' ] );

		// not relevant, any post type can be related to any other post type as "a ticket"
		$ticket_post_type = 'ticket_type';
		register_post_type( $ticket_post_type );

		// create a ticket for each post and relate the ticket to the post
		foreach ( array_merge( $batch_1, $batch_2 ) as $id ) {
			$ticket_id = $this->factory()->post->create( [ 'post_type' => $ticket_post_type ] );
			update_post_meta( $ticket_id, '_tribe_some_kind_of_ticket_for_event', $id );
		}

		$sut = $this->make_instance();
		$ids = $sut->posts_without_ticket_types();

		$this->assertEmpty( $ids );
	}

	/**
	 * @test
	 * it should only list posts that have a ticket among the supported ones
	 */
	public function it_should_only_list_posts_that_have_a_ticket_among_the_supported_ones() {
		register_post_type( 'supported_type' );
		tribe_update_option( 'ticket-enabled-post-types', [ 'supported_type' ] );

		$with_tickets    = $this->factory()->post->create_many( 3, [ 'post_type' => 'supported_type' ] );
		$without_tickets = $this->factory()->post->create_many( 3, [ 'post_type' => 'supported_type' ] );

		// not relevant, any post type can be related to any other post type as "a ticket"
		$ticket_post_type = 'ticket_type';
		register_post_type( $ticket_post_type );

		// create a ticket for each post and relate the ticket to the post
		foreach ( $with_tickets as $id ) {
			$ticket_id = $this->factory()->post->create( [ 'post_type' => $ticket_post_type ] );
			update_post_meta( $ticket_id, '_tribe_some_kind_of_ticket_for_event', $id );
		}

		$sut = $this->make_instance();

		$this->assertEqualSets( $with_tickets, $sut->posts_with_ticket_types() );
		$this->assertEqualSets( $without_tickets, $sut->posts_without_ticket_types() );
	}

	/**
	 * @test
	 * it should correctly list posts with and without tickets across post types and ticket types
	 */
	public function it_should_correctly_list_posts_with_and_without_tickets_across_post_types_and_ticket_types() {
		register_post_type( 'supported_type_1' );
		register_post_type( 'supported_type_2' );
		tribe_update_option( 'ticket-enabled-post-types', [ 'supported_type_1', 'supported_type_2' ] );

		$with_tickets_1    = $this->factory()->post->create_many( 3, [ 'post_type' => 'supported_type_1' ] );
		$without_tickets_1 = $this->factory()->post->create_many( 3, [ 'post_type' => 'supported_type_1' ] );
		$with_tickets_2    = $this->factory()->post->create_many( 3, [ 'post_type' => 'supported_type_2' ] );
		$without_tickets_2 = $this->factory()->post->create_many( 3, [ 'post_type' => 'supported_type_2' ] );

		// not relevant, any post type can be related to any other post type as "a ticket"
		$ticket_post_type_1 = 'ticket_type_1';
		$ticket_post_type_2 = 'ticket_type_1';
		register_post_type( $ticket_post_type_1 );
		register_post_type( $ticket_post_type_2 );

		// create a ticket for each post and relate the ticket to the post
		// if the id of the post is even also add a ticket of the second type
		foreach ( array_merge( $with_tickets_1, $with_tickets_2 ) as $id ) {
			$ticket_id = $this->factory()->post->create( [ 'post_type' => $ticket_post_type_1 ] );
			update_post_meta( $ticket_id, '_tribe_' . $ticket_post_type_1 . '_for_event', $id );
			if ( $id % 2 === 0 ) {
				$ticket_id = $this->factory()->post->create( [ 'post_type' => $ticket_post_type_1 ] );
				update_post_meta( $ticket_id, '_tribe_' . $ticket_post_type_1 . '_for_event', $id );
			}
		}

		$sut = $this->make_instance();

		$this->assertEqualSets( array_merge( $with_tickets_1, $with_tickets_2 ), $sut->posts_with_ticket_types() );
		$this->assertEqualSets( array_merge( $without_tickets_1, $without_tickets_2 ), $sut->posts_without_ticket_types() );
	}

	/**
	 * @test
	 * it should exclude past events when events are supported
	 */
	public function it_should_exclude_past_events_when_events_are_supported() {
		register_post_type( 'supported_type_1' );
		tribe_update_option( 'ticket-enabled-post-types', [ 'supported_type_1', Main::POSTTYPE ] );

		$with_tickets_1           = $this->factory()->post->create_many( 3, [ 'post_type' => 'supported_type_1' ] );
		$without_tickets_1        = $this->factory()->post->create_many( 3, [ 'post_type' => 'supported_type_1' ] );
		$events_with_tickets      = $this->factory()->post->create_many( 3,
			[ 'post_type' => Main::POSTTYPE, 'meta_input' => [ '_EventStartDate' => date( \Tribe__Date_Utils::DBDATETIMEFORMAT, strtotime( '+10 days' ) ) ] ] );
		$events_without_tickets   = $this->factory()->post->create_many( 3,
			[ 'post_type' => Main::POSTTYPE, 'meta_input' => [ '_EventStartDate' => date( \Tribe__Date_Utils::DBDATETIMEFORMAT, strtotime( '+10 days' ) ) ] ] );
		$past_events_with_tickets = $this->factory()->post->create_many( 3,
			[ 'post_type' => Main::POSTTYPE, 'meta_input' => [ '_EventStartDate' => date( \Tribe__Date_Utils::DBDATETIMEFORMAT, strtotime( '-10 days' ) ) ] ] );

		// not relevant, any post type can be related to any other post type as "a ticket"
		$ticket_post_type_1 = 'ticket_type_1';
		$ticket_post_type_2 = 'ticket_type_1';
		register_post_type( $ticket_post_type_1 );
		register_post_type( $ticket_post_type_2 );

		// create a ticket for each post and relate the ticket to the post
		// if the id of the post is even also add a ticket of the second type
		foreach ( array_merge( $with_tickets_1, $events_with_tickets, $past_events_with_tickets ) as $id ) {
			$ticket_id = $this->factory()->post->create( [ 'post_type' => $ticket_post_type_1 ] );
			update_post_meta( $ticket_id, '_tribe_' . $ticket_post_type_1 . '_for_event', $id );
			if ( $id % 2 === 0 ) {
				$ticket_id = $this->factory()->post->create( [ 'post_type' => $ticket_post_type_1 ] );
				update_post_meta( $ticket_id, '_tribe_' . $ticket_post_type_1 . '_for_event', $id );
			}
		}

		$sut = $this->make_instance();

		$this->assertEqualSets( array_merge( $with_tickets_1, $events_with_tickets ), $sut->posts_with_ticket_types() );
		$this->assertEqualSets( array_merge( $without_tickets_1, $events_without_tickets, $past_events_with_tickets ), $sut->posts_without_ticket_types() );
	}
}