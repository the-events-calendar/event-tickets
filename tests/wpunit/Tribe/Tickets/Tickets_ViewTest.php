<?php
namespace Tribe\Tickets;

use Closure;
use Generator;
use Codeception\TestCase\WPTestCase;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use Tribe__Tickets__Tickets_View as Tickets_View;

class Tickets_ViewTest extends WPTestCase {

	use SnapshotAssertions;
	public function setUp() {
		// before
		parent::setUp();

		// your set up methods here
	}

	public function tearDown() {
		// your tear down methods here

		// then
		parent::tearDown();
	}

	/**
	 * @test
	 * it should be instantiatable
	 */
	public function it_should_be_instantiatable() {
		$sut = $this->make_instance();

		$this->assertInstanceOf( Tickets_View::class, $sut );
	}

	/**
	 * @return Tickets_View
	 */
	private function make_instance() {
		return new Tickets_View();
	}
	
	/**
	 * Placeholder for post ids.
	 */
	public function placehold_post_ids( string $snapshot, array $ids ): string {
		return str_replace(
			array_values( $ids ),
			array_map( static fn( string $name ) => "{{ $name }}", array_keys( $ids ) ),
			$snapshot
		);
	}

	/**
	 * @test
	 * it should allow registering new RSVP states specifying label only
	 *
	 * The "old" way should still work.
	 */
	public function it_should_allow_registering_new_rsvp_states_specifying_label_only() {
		$rsvp_options = [
			'yes-plus-one'    => 'Going +1',
			'yes-plus-family' => 'Going +family',
			'yes-with-mt'     => 'Going (with MT)',
		];

		add_filter(
			'event_tickets_rsvp_options',
			function ( $options ) use ( $rsvp_options ) {
				return array_merge( $options, $rsvp_options );
			}
		);

		$sut = $this->make_instance();

		$options = $sut->get_rsvp_options( null, false );

		foreach ( $rsvp_options as $rsvp_option => $label ) {
			$this->assertArrayHasKey( $rsvp_option, $options );
			$this->assertEquals( $label, $options[ $rsvp_option ]['label'] );
		}
	}

	/**
	 * @test
	 * it should default the decrease_stock_by arg to 1 if not passed
	 */
	public function it_should_default_the_decrease_stock_by_arg_to_1_if_not_passed() {
		$rsvp_options = [
			'yes-plus-one'    => 'Going +1',
			'yes-plus-family' => 'Going +family',
			'yes-with-mt'     => 'Going (with MT)',
		];

		add_filter(
			'event_tickets_rsvp_options',
			function ( $options ) use ( $rsvp_options ) {
				return array_merge( $options, $rsvp_options );
			}
		);

		$sut = $this->make_instance();

		$options = $sut->get_rsvp_options( null, false );

		foreach ( $rsvp_options as $rsvp_option => $label ) {
			$this->assertArrayHasKey( $rsvp_option, $options );
			$this->assertEquals( 1, $options[ $rsvp_option ]['decrease_stock_by'] );
		}
	}

	/**
	 * @test
	 * it should prune RSVP options that do not have right format
	 */
	public function it_should_prune_rsvp_options_that_do_not_have_right_format() {
		$rsvp_options = [
			// good
			'yes-plus-one'    => [ 'label' => 'Going +1', 'decrease_stock_by' => 2 ],
			// good even without stock
			'maybe'           => [ 'label' => 'Maybe' ],
			// no label
			'yes-plus-family' => [ 'Going +family' ],
			// ok stock but no label
			'yes-with-mt'     => [ 'Going (with MT)', 'decrease_stock_by' => 0 ],
		];

		add_filter(
			'event_tickets_rsvp_options',
			function ( $options ) use ( $rsvp_options ) {
				return array_merge( $options, $rsvp_options );
			}
		);

		$sut = $this->make_instance();

		$options = $sut->get_rsvp_options( null, false );

		foreach ( [ 'yes-plus-one', 'maybe' ] as $rsvp_option ) {
			$this->assertArrayHasKey( $rsvp_option, $options );
		}
		foreach ( [ 'yes-plus-family', 'yes-with-mt' ] as $bad_rsvp_option ) {
			$this->assertArrayNotHasKey( $bad_rsvp_option, $options );
		}
	}

	/**
	 * @test
	 * it should allow decrease_stock_by zero values
	 */
	public function it_should_allow_decrease_stock_by_zero_values() {
		$rsvp_options = [
			'maybe' => [ 'label' => 'Maybe', 'decrease_stock_by' => 0 ],
		];

		add_filter(
			'event_tickets_rsvp_options',
			function ( $options ) use ( $rsvp_options ) {
				return array_merge( $options, $rsvp_options );
			}
		);

		$sut = $this->make_instance();

		$options = $sut->get_rsvp_options( null, false );

		$this->assertArrayHasKey( 'maybe', $options );
		$this->assertEquals( 0, $options['maybe']['decrease_stock_by'] );
	}

	/**
	 * @test
	 * it should not allow options to have a negative decrease_stock_by value
	 */
	public function it_should_allow_options_to_have_a_negative_decrease_stock_by_value() {
		$rsvp_options = [
			'plus-one'           => [ 'label' => 'Plus one', 'decrease_stock_by' => 2 ],
			'not-going-plus-one' => [ 'label' => 'Not going plus one', 'decrease_stock_by' => - 2 ],
		];

		add_filter(
			'event_tickets_rsvp_options',
			function ( $options ) use ( $rsvp_options ) {
				return array_merge( $options, $rsvp_options );
			}
		);

		$sut = $this->make_instance();

		$options = $sut->get_rsvp_options( null, false );

		$this->assertArrayHasKey( 'plus-one', $options );
		$this->assertArrayNotHasKey( 'not-going-plus-one', $options );
		$this->assertEquals( 2, $options['plus-one']['decrease_stock_by'] );
	}

	/**
	 * @test
	 * it should not allow non int decrease_stock_by values
	 */
	public function it_should_not_allow_non_int_decrease_stock_by_values() {
		$rsvp_options = [
			'maybe' => [ 'label' => 'Maybe', 'decrease_stock_by' => .5 ],
		];

		add_filter(
			'event_tickets_rsvp_options',
			function ( $options ) use ( $rsvp_options ) {
				return array_merge( $options, $rsvp_options );
			}
		);

		$sut = $this->make_instance();

		$options = $sut->get_rsvp_options( null, false );

		$this->assertArrayNotHasKey( 'maybe', $options );
	}

	/**
	 * @test
	 * it should mark the default Going option to decrease_stock_by 1
	 */
	public function it_should_mark_the_default_going_option_to_decrease_stock_by_1() {
		$sut = $this->make_instance();

		$options = $sut->get_rsvp_options( null, false );

		$this->assertArrayHasKey( 'yes', $options );
		$this->assertEquals( 1, $options['yes']['decrease_stock_by'] );
	}

	/**
	 * @test
	 * it should mark default Not Going option to decrease stock by 0
	 */
	public function it_should_mark_default_not_going_option_to_decrease_stock_by_0() {
		$sut = $this->make_instance();

		$options = $sut->get_rsvp_options( null, false );

		$this->assertArrayHasKey( 'no', $options );
		$this->assertEquals( 0, $options['no']['decrease_stock_by'] );
	}
	
	public function provide_get_tickets_page_url_data(): Generator {
		yield 'with invalid post id' => [
			function (): array {
				return [
					PHP_INT_MAX,
					false,
				];
			},
		];
		
		yield 'with valid post id' => [
			function (): array {
				$post_id = wp_insert_post(
					[
						'post_type'   => 'post',
						'post_title'  => 'Test Post',
						'post_status' => 'publish',
					]
				);
				
				return [
					$post_id,
					true,
				];
			},
		];
		
		yield 'with valid event id' => [
			function (): array {
				$event_id = tribe_events()->set_args(
					[
						'title'      => 'Test Event',
						'status'     => 'publish',
						'start_date' => '2020-01-01 09:00:00',
						'end_date'   => '2020-01-01 11:30:00',
					]
				)->create()->ID;
				
				return [
					$event_id,
					true,
				];
			},
		];
	}
	
	/**
	 * @dataProvider provide_get_tickets_page_url_data
	 *
	 * @test
	 *
	 * it should return the valid tickets page url.
	 */
	public function should_get_tickets_page_url( Closure $fixture ): void {
		[ $post_id, $has_output ] = $fixture();
		
		$sut = $this->make_instance();
		$url = $sut->get_tickets_page_url( $post_id );
		$url = $this->placehold_post_ids( $url, [ 'post_id' => $post_id ] );
		
		if ( $has_output ) {
			$this->assertMatchesHtmlSnapshot( $url );
		} else {
			$this->assertEmpty( $url );
		}
	}
	
	/**
	 * @dataProvider provide_get_tickets_page_url_data
	 *
	 * @test
	 *
	 * it should return the valid tickets page url.
	 */
	public function should_get_tickets_page_url_for_plain_permalink( Closure $fixture ): void {
		[ $post_id, $has_output ] = $fixture();
		
		update_option( 'permalink_structure', '' );
		$sut = $this->make_instance();
		$url = $sut->get_tickets_page_url( $post_id );
		$url = $this->placehold_post_ids( $url, [ 'post_id' => $post_id ] );
		
		if ( $has_output ) {
			$this->assertMatchesHtmlSnapshot( $url );
		} else {
			$this->assertEmpty( $url );
		}
		
		// reset to default.
		update_option( 'permalink_structure', false );
	}

	/**
	 * Data provider for different permalink structures.
	 */
	public function provide_permalink_structures_data(): Generator {
		yield 'numeric permalinks' => [
			'structure' => '/archives/%post_id%',
			'description' => 'numeric',
		];

		yield 'day and name permalinks' => [
			'structure' => '/%year%/%monthnum%/%day%/%postname%/',
			'description' => 'day and name',
		];

		yield 'month and name permalinks' => [
			'structure' => '/%year%/%monthnum%/%postname%/',
			'description' => 'month and name',
		];

		yield 'post name permalinks' => [
			'structure' => '/%postname%/',
			'description' => 'post name',
		];

		yield 'custom category and name permalinks' => [
			'structure' => '/%category%/%postname%/',
			'description' => 'custom category and name',
		];

		yield 'custom with date permalinks' => [
			'structure' => '/blog/%year%/%monthnum%/%postname%/',
			'description' => 'custom with date',
		];
	}

	/**
	 * @dataProvider provide_permalink_structures_data
	 *
	 * @test
	 *
	 * it should return the valid tickets page url for different permalink structures.
	 */
	public function should_get_tickets_page_url_for_different_permalink_structures( string $structure, string $description ): void {
		// Set the permalink structure.
		update_option( 'permalink_structure', $structure );

		// Test with a regular post.
		$post_id = wp_insert_post( [
			'post_type'   => 'post',
			'post_title'  => 'Test Post',
			'post_status' => 'publish',
		] );

		$sut = $this->make_instance();
		$url = $sut->get_tickets_page_url( $post_id );
		$url = $this->placehold_post_ids( $url, [ 'post_id' => $post_id ] );

		// Verify the URL is not empty and contains expected elements.
		$this->assertNotEmpty( $url, "URL should not be empty for {$description} permalink structure" );
		$this->assertStringContainsString( 'tickets', $url, "URL should contain 'tickets' for {$description} permalink structure" );
		$this->assertStringContainsString( '{{ post_id }}', $url, "URL should contain post ID placeholder for {$description} permalink structure" );

		// Test with an event.
		$event_id = tribe_events()->set_args( [
			'title'      => 'Test Event',
			'status'     => 'publish',
			'start_date' => '2020-01-01 09:00:00',
			'end_date'   => '2020-01-01 11:30:00',
		] )->create()->ID;

		$event_url = $sut->get_tickets_page_url( $event_id );
		$event_url = $this->placehold_post_ids( $event_url, [ 'post_id' => $event_id ] );

		// Verify the event URL is not empty and contains expected elements.
		$this->assertNotEmpty( $event_url, "Event URL should not be empty for {$description} permalink structure" );
		$this->assertStringContainsString( 'tickets', $event_url, "Event URL should contain 'tickets' for {$description} permalink structure" );

		// Snapshot test for reproducibility.
		$this->assertMatchesHtmlSnapshot( $url );

		// Reset permalink structure.
		update_option( 'permalink_structure', false );
	}

	/**
	 * @dataProvider provide_permalink_structures_data
	 *
	 * @test
	 *
	 * it should preserve tickets parameter in canonical redirect for different permalink structures.
	 */
	public function should_preserve_tickets_parameter_in_canonical_redirect_for_different_permalink_structures( string $structure, string $description ): void {
		// Set the permalink structure.
		update_option( 'permalink_structure', $structure );

		// Test with a regular post.
		$post_id = wp_insert_post( [
			'post_type'   => 'post',
			'post_title'  => 'Test Post',
			'post_status' => 'publish',
		] );

		// Mock query vars.
		set_query_var( 'tribe-edit-orders', 1 );
		set_query_var( 'p', $post_id );

		$sut = $this->make_instance();
		$result = $sut->preserve_tickets_parameter_in_canonical_redirect( 'http://example.com/test' );

		// Verify the result is not empty and contains expected elements.
		$this->assertNotEmpty( $result, "Result should not be empty for {$description} permalink structure" );
		$this->assertStringContainsString( 'tickets', $result, "Result should contain 'tickets' for {$description} permalink structure" );
		$this->assertStringContainsString( (string) $post_id, $result, "Result should contain post ID for {$description} permalink structure" );

		// Test with an event.
		$event_id = tribe_events()->set_args( [
			'title'      => 'Test Event',
			'status'     => 'publish',
			'start_date' => '2020-01-01 09:00:00',
			'end_date'   => '2020-01-01 11:30:00',
		] )->create()->ID;

		// Mock query vars for event.
		set_query_var( 'p', $event_id );

		$event_result = $sut->preserve_tickets_parameter_in_canonical_redirect( 'http://example.com/test' );

		// Verify the event result is not empty and contains expected elements.
		$this->assertNotEmpty( $event_result, "Event result should not be empty for {$description} permalink structure" );
		$this->assertStringContainsString( 'tickets', $event_result, "Event result should contain 'tickets' for {$description} permalink structure" );

		// Reset query vars.
		set_query_var( 'tribe-edit-orders', null );
		set_query_var( 'p', null );

		// Reset permalink structure.
		update_option( 'permalink_structure', false );
	}

	/**
	 * Data provider for canonical redirect tests.
	 */
	public function provide_canonical_redirect_data(): Generator {
		yield 'no redirect URL' => [
			function (): array {
				$post_id = wp_insert_post( [
					'post_type'   => 'post',
					'post_title'  => 'Test Post',
					'post_status' => 'publish',
				] );

				return [
					'redirect_url' => '',
					'post_id'      => $post_id,
					'query_vars'   => [],
					'expected'     => '',
				];
			},
		];

		yield 'no tribe-edit-orders parameter' => [
			function (): array {
				$post_id = wp_insert_post( [
					'post_type'   => 'post',
					'post_title'  => 'Test Post',
					'post_status' => 'publish',
				] );

				return [
					'redirect_url' => 'http://example.com/test',
					'post_id'      => $post_id,
					'query_vars'   => [],
					'expected'     => 'http://example.com/test',
				];
			},
		];

		yield 'with tribe-edit-orders but no post ID' => [
			function (): array {
				return [
					'redirect_url' => 'http://example.com/test',
					'post_id'      => 0,
					'query_vars'   => [ 'tribe-edit-orders' => 1 ],
					'expected'     => 'http://example.com/test?tribe-edit-orders=1',
				];
			},
		];

		yield 'with tribe-edit-orders and post ID, plain permalinks' => [
			function (): array {
				$post_id = wp_insert_post( [
					'post_type'   => 'post',
					'post_title'  => 'Test Post',
					'post_status' => 'publish',
				] );

				return [
					'redirect_url'       => 'http://example.com/test',
					'post_id'            => $post_id,
					'query_vars'         => [ 'tribe-edit-orders' => 1, 'p' => $post_id ],
					'expected'           => 'http://example.com/test?tribe-edit-orders=1',
					'permalink_structure' => '',
				];
			},
		];

		yield 'with tribe-edit-orders and post ID, pretty permalinks' => [
			function (): array {
				$post_id = wp_insert_post( [
					'post_type'   => 'post',
					'post_title'  => 'Test Post',
					'post_status' => 'publish',
				] );

				return [
					'redirect_url'       => 'http://example.com/test',
					'post_id'            => $post_id,
					'query_vars'         => [ 'tribe-edit-orders' => 1, 'p' => $post_id ],
					'expected'           => true, // Will be generated URL
					'permalink_structure' => '/%postname%/',
				];
			},
		];

		yield 'with tribe-edit-orders and event ID, pretty permalinks' => [
			function (): array {
				$event_id = tribe_events()->set_args( [
					'title'      => 'Test Event',
					'status'     => 'publish',
					'start_date' => '2020-01-01 09:00:00',
					'end_date'   => '2020-01-01 11:30:00',
				] )->create()->ID;

				return [
					'redirect_url'       => 'http://example.com/test',
					'post_id'            => $event_id,
					'query_vars'         => [ 'tribe-edit-orders' => 1, 'p' => $event_id ],
					'expected'           => true, // Will be generated URL.
					'permalink_structure' => '/%postname%/',
				];
			},
		];
	}

	/**
	 * @dataProvider provide_canonical_redirect_data
	 *
	 * @test
	 *
	 * it should preserve tickets parameter in canonical redirect correctly.
	 */
	public function should_preserve_tickets_parameter_in_canonical_redirect( Closure $fixture ): void {
		[ 'redirect_url' => $redirect_url, 'post_id' => $post_id, 'query_vars' => $query_vars, 'expected' => $expected ] = $fixture();

		// Set permalink structure if specified.
		if ( isset( $fixture()['permalink_structure'] ) ) {
			update_option( 'permalink_structure', $fixture()['permalink_structure'] );
		}

		// Mock query vars.
		foreach ( $query_vars as $var => $value ) {
			set_query_var( $var, $value );
		}

		$sut = $this->make_instance();
		$result = $sut->preserve_tickets_parameter_in_canonical_redirect( $redirect_url );

		if ( $expected === true ) {
			// For generated URLs, ensure they're not empty and contain tickets.
			$this->assertNotEmpty( $result );
			$this->assertStringContainsString( 'tickets', $result );
			
			// For events, URL contains event slug; for posts, URL contains post ID.
			$post_type = get_post_type( $post_id );
			if ( 'tribe_events' === $post_type || 'tribe_event_series' === $post_type ) {
				// Events use slug-based URLs.
				$this->assertStringContainsString( 'tribe_events', $result );
			} else {
				// Posts use ID-based URLs.
				$this->assertStringContainsString( (string) $post_id, $result );
			}
		} else {
			// For specific expected values, check exact match.
			$this->assertEquals( $expected, $result );
		}

		// Reset query vars.
		foreach ( $query_vars as $var => $value ) {
			set_query_var( $var, null );
		}

		// Reset permalink structure.
		update_option( 'permalink_structure', false );
	}

	/**
	 * Data provider for handle_tickets_request tests.
	 */
	public function provide_handle_tickets_request_data(): Generator {
		yield 'no tribe-edit-orders parameter' => [
			function (): array {
				return [
					'input_vars' => [ 'some_var' => 'value' ],
					'expected'   => [ 'some_var' => 'value' ],
				];
			},
		];

		yield 'empty tribe-edit-orders parameter' => [
			function (): array {
				return [
					'input_vars' => [ 'tribe-edit-orders' => 0, 'some_var' => 'value' ],
					'expected'   => [ 'tribe-edit-orders' => 0, 'some_var' => 'value' ],
				];
			},
		];

		yield 'tribe-edit-orders but no post ID' => [
			function (): array {
				return [
					'input_vars' => [ 'tribe-edit-orders' => 1 ],
					'expected'   => [ 'tribe-edit-orders' => 1 ],
				];
			},
		];

		yield 'tribe-edit-orders with invalid post ID' => [
			function (): array {
				return [
					'input_vars' => [ 'tribe-edit-orders' => 1, 'p' => 99999 ],
					'expected'   => [ 'tribe-edit-orders' => 1, 'p' => 99999 ],
				];
			},
		];

		yield 'tribe-edit-orders with valid post' => [
			function (): array {
				$post_id = wp_insert_post( [
					'post_type'   => 'post',
					'post_title'  => 'Test Post',
					'post_status' => 'publish',
				] );

				return [
					'input_vars' => [ 'tribe-edit-orders' => 1, 'p' => $post_id ],
					'expected'   => [ 'tribe-edit-orders' => 1, 'p' => $post_id, 'post_type' => 'post' ],
					'post_id'    => $post_id,
				];
			},
		];

		yield 'tribe-edit-orders with valid page' => [
			function (): array {
				$page_id = wp_insert_post( [
					'post_type'   => 'page',
					'post_title'  => 'Test Page',
					'post_status' => 'publish',
				] );

				return [
					'input_vars' => [ 'tribe-edit-orders' => 1, 'p' => $page_id ],
					'expected'   => [ 'tribe-edit-orders' => 1, 'post_type' => 'page', 'page_id' => $page_id ],
					'post_id'    => $page_id,
				];
			},
		];

		yield 'tribe-edit-orders with valid event' => [
			function (): array {
				$event_id = tribe_events()->set_args( [
					'title'      => 'Test Event',
					'status'     => 'publish',
					'start_date' => '2020-01-01 09:00:00',
					'end_date'   => '2020-01-01 11:30:00',
				] )->create()->ID;

				return [
					'input_vars' => [ 'tribe-edit-orders' => 1, 'p' => $event_id ],
					'expected'   => [ 'tribe-edit-orders' => 1, 'p' => $event_id, 'post_type' => 'tribe_events' ],
					'post_id'    => $event_id,
				];
			},
		];
	}

	/**
	 * @dataProvider provide_handle_tickets_request_data
	 *
	 * @test
	 *
	 * it should handle tickets request correctly.
	 */
	public function should_handle_tickets_request( Closure $fixture ): void {
		[ 'input_vars' => $input_vars, 'expected' => $expected ] = $fixture();

		$sut = $this->make_instance();
		$result = $sut->handle_tickets_request( $input_vars );

		$this->assertEquals( $expected, $result );

		// Clean up post if created.
		if ( isset( $fixture()['post_id'] ) ) {
			wp_delete_post( $fixture()['post_id'], true );
		}
	}

	/**
	 * @test
	 * it should integrate canonical redirect with URL generation.
	 */
	public function should_integrate_canonical_redirect_with_url_generation(): void {
		// Create a test post.
		$post_id = wp_insert_post( [
			'post_type'   => 'post',
			'post_title'  => 'Test Post',
			'post_status' => 'publish',
		] );

		// Set pretty permalinks.
		update_option( 'permalink_structure', '/%postname%/' );

		// Mock query vars as if coming from canonical redirect.
		set_query_var( 'tribe-edit-orders', 1 );
		set_query_var( 'p', $post_id );

		$sut = $this->make_instance();
		
		// Test that get_tickets_page_url generates the expected URL.
		$tickets_url = $sut->get_tickets_page_url( $post_id );
		$this->assertNotEmpty( $tickets_url );
		$this->assertStringContainsString( 'tickets', $tickets_url );
		
		// For regular posts, URL should contain the post ID.
		$post_type = get_post_type( $post_id );
		if ( 'tribe_events' === $post_type || 'tribe_event_series' === $post_type ) {
			$this->assertStringContainsString( 'tribe_events', $tickets_url );
		} else {
			$this->assertStringContainsString( (string) $post_id, $tickets_url );
		}

		// Test that preserve_tickets_parameter_in_canonical_redirect uses the same URL.
		$canonical_url = $sut->preserve_tickets_parameter_in_canonical_redirect( 'http://example.com/redirect' );
		$this->assertEquals( $tickets_url, $canonical_url );

		// Test that handle_tickets_request processes the query vars correctly.
		$query_vars = [ 'tribe-edit-orders' => 1, 'p' => $post_id ];
		$processed_vars = $sut->handle_tickets_request( $query_vars );
		$this->assertEquals( 'post', $processed_vars['post_type'] );
		$this->assertEquals( $post_id, $processed_vars['p'] );

		// Clean up.
		wp_delete_post( $post_id, true );
		update_option( 'permalink_structure', false );
		set_query_var( 'tribe-edit-orders', null );
		set_query_var( 'p', null );
	}
}