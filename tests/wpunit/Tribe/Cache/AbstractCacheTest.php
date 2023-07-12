<?php

class AbstractCacheTest extends \Codeception\TestCase\WPTestCase {


	/**
	 * Test fetch_posts_with_ticket_types method
	 * Create multiple events & tickets with the meta_key of `_tribe_some_kind_of_ticket_for_event` and `_tec_some_kind_of_ticket_for_event`.
	 *
	 * @test
	 */
	public function fetch_posts_with_used_meta_keys() {
		// Create a mock of the abstract class
		$mock_for_abstract_class = $this->getMockBuilder( Tribe__Tickets__Cache__Abstract_Cache::class )
										->disableOriginalConstructor()
										->getMockForAbstractClass();

		$reflector = new \ReflectionObject( $mock_for_abstract_class );

		$method = $reflector->getMethod( 'fetch_posts_with_ticket_types' );
		$method->setAccessible( true );

		$posts_with_tribe_tickets = $this->factory()->post->create_many( 3, [ 'post_type' => 'post' ] );
		$posts_with_tribe_tickets = array_merge( $posts_with_tribe_tickets, $this->factory()->post->create_many( 3, [ 'post_type' => 'page' ] ) );

		$posts_with_tec_tickets = $this->factory()->post->create_many( 3, [ 'post_type' => 'post' ] );
		$posts_with_tec_tickets = array_merge( $posts_with_tec_tickets, $this->factory()->post->create_many( 3, [ 'post_type' => 'page' ] ) );

		// not relevant, any post type can be related to any other post type as "a ticket".
		$ticket_post_type = 'ticket_type';
		register_post_type( $ticket_post_type );

		// create a ticket for each post and relate the ticket to the post.
		foreach ( $posts_with_tribe_tickets as $id ) {
			$ticket_id = $this->factory()->post->create( [ 'post_type' => $ticket_post_type ] );
			update_post_meta( $ticket_id, '_tribe_some_kind_of_ticket_for_event', $id );
		}

		foreach ( $posts_with_tec_tickets as $id ) {
			$ticket_id = $this->factory()->post->create( [ 'post_type' => $ticket_post_type ] );
			update_post_meta( $ticket_id, '_tec_some_kind_of_ticket_for_event', $id );
		}


		// Generate filtered list of attendees.
		$events = $method->invoke( $mock_for_abstract_class, null );

		$this->assertCount( 6, $events );
	}

	/**
	 * Test fetch_posts_with_ticket_types method with specific post types
	 *
	 * @test
	 */
	public function fetch_posts_with_ticket_types_with_post_types() {
		// Create a mock of the abstract class
		$mock_for_abstract_class = $this->getMockBuilder( Tribe__Tickets__Cache__Abstract_Cache::class )
										->disableOriginalConstructor()
										->getMockForAbstractClass();

		$reflector = new \ReflectionObject( $mock_for_abstract_class );

		$method = $reflector->getMethod( 'fetch_posts_with_ticket_types' );
		$method->setAccessible( true );

		// Create posts of specific types
		$posts_with_tickets = $this->factory()->post->create_many( 3, [ 'post_type' => 'post' ] );
		$posts_with_tickets = array_merge( $posts_with_tickets, $this->factory()->post->create_many( 3, [ 'post_type' => 'page' ] ) );

		// Create a ticket for each post and relate the ticket to the post
		foreach ( $posts_with_tickets as $id ) {
			$ticket_id = $this->factory()->post->create( [ 'post_type' => 'ticket_type' ] );
			update_post_meta( $ticket_id, '_tribe_some_kind_of_ticket_for_event', $id );
		}

		// Generate filtered list of attendees.
		$events = $method->invoke( $mock_for_abstract_class, [ 'post', 'page' ] );

		$this->assertCount( 6, $events );
	}

	/**
	 * Test fetch_posts_with_ticket_types method with invalid post types
	 *
	 * @test
	 */
	public function fetch_posts_with_ticket_types_with_invalid_post_types() {
		// Create a mock of the abstract class
		$mock_for_abstract_class = $this->getMockBuilder( Tribe__Tickets__Cache__Abstract_Cache::class )
										->disableOriginalConstructor()
										->getMockForAbstractClass();

		$reflector = new \ReflectionObject( $mock_for_abstract_class );

		$method = $reflector->getMethod( 'fetch_posts_with_ticket_types' );
		$method->setAccessible( true );

		// Generate filtered list of attendees.
		$events = $method->invoke( $mock_for_abstract_class, [ 'invalid_post_type' ] );

		$this->assertEmpty( $events );
	}

	/**
	 * Test fetch_posts_with_ticket_types method with no posts of the specified types
	 *
	 * @test
	 */
	public function fetch_posts_with_ticket_types_with_no_posts_of_specified_types() {
		// Create a mock of the abstract class
		$mock_for_abstract_class = $this->getMockBuilder( Tribe__Tickets__Cache__Abstract_Cache::class )
										->disableOriginalConstructor()
										->getMockForAbstractClass();

		$reflector = new \ReflectionObject( $mock_for_abstract_class );

		$method = $reflector->getMethod( 'fetch_posts_with_ticket_types' );
		$method->setAccessible( true );

		// Generate filtered list of attendees.
		$events = $method->invoke( $mock_for_abstract_class, [ 'post_type_with_no_posts' ] );

		$this->assertEmpty( $events );
	}

	/**
	 * Test fetch_posts_with_ticket_types method with posts that have no tickets
	 *
	 * @test
	 */
	public function fetch_posts_with_ticket_types_with_posts_that_have_no_tickets() {
		// Create a mock of the abstract class
		$mock_for_abstract_class = $this->getMockBuilder( Tribe__Tickets__Cache__Abstract_Cache::class )
										->disableOriginalConstructor()
										->getMockForAbstractClass();

		$reflector = new \ReflectionObject( $mock_for_abstract_class );

		$method = $reflector->getMethod( 'fetch_posts_with_ticket_types' );
		$method->setAccessible( true );

		// Create posts of a specific type
		$posts = $this->factory()->post->create_many( 3, [ 'post_type' => 'post' ] );

		// Generate filtered list of attendees.
		$events = $method->invoke( $mock_for_abstract_class, [ 'post' ] );

		$this->assertEmpty( $events );
	}

	/**
	 * Test fetch_posts_with_ticket_types method with posts that have tickets but are in 'trash' or 'auto-draft' status
	 *
	 * @test
	 */
	public function fetch_posts_with_ticket_types_with_trashed_or_auto_draft_posts() {
		// Create a mock of the abstract class
		$mock_for_abstract_class = $this->getMockBuilder( Tribe__Tickets__Cache__Abstract_Cache::class )
										->disableOriginalConstructor()
										->getMockForAbstractClass();

		$reflector = new \ReflectionObject( $mock_for_abstract_class );

		$method = $reflector->getMethod( 'fetch_posts_with_ticket_types' );
		$method->setAccessible( true );

		// Create posts of a specific type and status
		$posts = $this->factory()->post->create_many( 3, [ 'post_type' => 'post', 'post_status' => 'trash' ] );
		$posts = array_merge( $posts, $this->factory()->post->create_many( 3, [ 'post_type' => 'post', 'post_status' => 'auto-draft' ] ) );

		// Generate filtered list of attendees.
		$events = $method->invoke( $mock_for_abstract_class, [ 'post' ] );

		$this->assertEmpty( $events );
	}

	/**
	 * Test fetch_posts_with_ticket_types method with posts that have tickets but the tickets are not related to any
	 * event
	 *
	 * @test
	 */
	public function fetch_posts_should_return_empty_when_post_has_no_tickets() {
		// Create a mock of the abstract class
		$mock_for_abstract_class = $this->getMockBuilder( Tribe__Tickets__Cache__Abstract_Cache::class )
										->disableOriginalConstructor()
										->getMockForAbstractClass();

		$reflector = new \ReflectionObject( $mock_for_abstract_class );

		$method = $reflector->getMethod( 'fetch_posts_with_ticket_types' );
		$method->setAccessible( true );

		// Create posts of a specific type
		$posts = $this->factory()->post->create_many( 3, [ 'post_type' => 'post' ] );

		// Generate filtered list of attendees.
		$events = $method->invoke( $mock_for_abstract_class, [ 'post' ] );

		$this->assertEmpty( $events );
	}
}