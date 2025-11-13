<?php

namespace Tribe\Tickets;

use Closure;
use Codeception\TestCase\WPTestCase;
use Generator;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tests\Traits\WP_Send_Json_Mocks;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Tickets__Metabox as Metabox;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use TEC\Tickets\Commerce\Module as Commerce;
use Tribe__Events__Main as TEC;
use Tribe__Date_Utils as Date_Utils;

class MetaboxTest extends WPTestCase {
	use SnapshotAssertions;
	use Ticket_Maker;
	use RSVP_Ticket_Maker;
	use With_Uopz;
	use WP_Send_Json_Mocks;

	/**
	 * @before
	 */
	public function ensure_ticketable_post_types(): void {
		$ticketable   = tribe_get_option( 'ticket-enabled-post-types', [] );
		$ticketable[] = 'post';
		$ticketable[] = TEC::POSTTYPE;
		tribe_update_option( 'ticket-enabled-post-types', array_values( array_unique( $ticketable ) ) );
		// Set up a fake "now".
		$date = new \DateTime( '2019-09-11 22:00:00', new \DateTimeZone( 'America/New_York' ) );
		$now  = $date->getTimestamp();
		// Alter the concept of the `now` timestamp to return the timestamp for `2019-09-11 22:00:00` in NY timezone.
		uopz_set_return(
			'strtotime', static function ( $str ) use ( $now ) {
			return $str === 'now' ? $now : strtotime( $str );
		},  true
		);
		// Make sure that `now` (string) will be resolved to the fake date object.
		uopz_set_return( Date_Utils::class, 'build_date_object', $date );
	}

	/**
	 * Low-level registration of the Commerce provider. There is no need for a full-blown registration
	 * at this stage: having the module as active and as a valid provider is enough.
	 *
	 * @before
	 */
	public function activate_commerce_tickets(): void {
		add_filter(
			'tribe_tickets_get_modules',
			static function ( array $modules ): array {
				$modules[ Commerce::class ] = 'Tickets Commerce';

				return $modules;
			}
		);
		// Regenerate the Tickets Data API to pick up the filtered providers.
		tribe()->singleton( 'tickets.data_api', new \Tribe__Tickets__Data_API() );
	}

	public function get_panels_provider(): Generator {
		yield 'post without ticket' => [
			function (): array {
				$post_id   = $this->factory()->post->create();
				$ticket_id = null;

				return [ $post_id, $ticket_id ];
			},
		];

		yield 'post with ticket' => [
			function (): array {
				$post_id   = $this->factory()->post->create();
				$ticket_id = $this->create_tc_ticket( $post_id, 23 );

				return [ $post_id, $ticket_id ];
			},
		];

		yield 'event without ticket' => [
			function (): array {
				$post_id   = tribe_events()->set_args(
					[
						'title'      => 'Test event',
						'status'     => 'publish',
						'start_date' => '2021-01-01 10:00:00',
						'end_date'   => '2021-01-01 12:00:00',
					]
				)->create()->ID;
				$ticket_id = null;

				return [ $post_id, $ticket_id ];
			},
		];

		yield 'event with ticket' => [
			function (): array {
				$post_id   = tribe_events()->set_args(
					[
						'title'      => 'Test event',
						'status'     => 'publish',
						'start_date' => '2021-01-01 10:00:00',
						'end_date'   => '2021-01-01 12:00:00',
					]
				)->create()->ID;
				$ticket_id = $this->create_tc_ticket( $post_id, 23 );

				return [ $post_id, $ticket_id ];
			},
		];

		yield 'post with RSVP' => [
			function (): array {
				$post_id   = $this->factory()->post->create();
				$ticket_id = $this->create_rsvp_ticket(
					$post_id,
					[
						'meta_input' => [
							'_ticket_start_date' => '2021-01-01 10:00:00',
							'_ticket_end_date'   => '2021-01-31 12:00:00',
						],
					]
				);

				return [ $post_id, $ticket_id ];
			},
		];

		yield 'event with RSVP' => [
			function (): array {
				$post_id   = tribe_events()->set_args(
					[
						'title'      => 'Test event',
						'status'     => 'publish',
						'start_date' => '2021-01-01 10:00:00',
						'end_date'   => '2021-01-01 12:00:00',
					]
				)->create()->ID;
				$ticket_id = $this->create_rsvp_ticket(
					$post_id,
					[
						'meta_input' => [
							'_ticket_start_date' => '2021-01-01 10:00:00',
							'_ticket_end_date'   => '2021-01-31 12:00:00',
						],
					]
				);

				return [ $post_id, $ticket_id ];
			},
		];

		yield 'post with ticket and sale price' => [
			function (): array {
				$post_id   = $this->factory()->post->create();
				$ticket_id = $this->create_tc_ticket(
					$post_id,
					20,
					[
						'ticket_add_sale_price'  => 'on',
						'ticket_sale_price'      => 10,
						'ticket_sale_start_date' => '2010-03-01',
						'ticket_sale_end_date'   => '2040-03-01',
					]
				);

				return [ $post_id, $ticket_id ];
			},
		];

		yield 'event with ticket and sale price' => [
			function (): array {
				$post_id = tribe_events()->set_args(
					[
						'title'      => 'Test Event with sale price',
						'status'     => 'publish',
						'start_date' => '2022-10-01 10:00:00',
						'duration'   => 2 * HOUR_IN_SECONDS,
					]
				)->create()->ID;

				$ticket_id = $this->create_tc_ticket(
					$post_id,
					20,
					[
						'ticket_add_sale_price'  => 'on',
						'ticket_sale_price'      => 10,
						'ticket_sale_start_date' => '2010-03-01',
						'ticket_sale_end_date'   => '2040-03-01',
					]
				);

				return [ $post_id, $ticket_id ];
			},
		];
	}

	public function placehold_post_ids( string $snapshot, array $ids ): string {
		return str_replace(
			array_values( $ids ),
			array_map( static fn( string $name ) => "{{ $name }}", array_keys( $ids ) ),
			$snapshot
		);
	}

	/**
	 * @dataProvider get_panels_provider
	 */
	public function test_get_panels( Closure $fixture ): void {
		[ $post_id, $ticket_id ] = $fixture();
		$this->set_fn_return( 'wp_create_nonce', '33333333' );

		$metabox = tribe( Metabox::class );
		$panels  = $metabox->get_panels( $post_id, $ticket_id );
		$html    = implode( '', $panels );
		$html    = $this->placehold_post_ids(
			$html,
			[
				'post_id'   => $post_id,
				'ticket_id' => $ticket_id,
			]
		);
		// Depending on the Common versions, the assets might be loaded from ET or TEC; this should not break the tests.
		$html = str_replace( 'the-events-calendar/common', 'event-tickets/common', $html );

		$this->assertMatchesHtmlSnapshot( $html );
	}

	public function panels_with_no_provider_data_provider(): \Generator {
		yield 'post' => [
			static function () {
				return static::factory()->post->create( [ 'post_type' => 'post' ] );
			},
		];

		yield 'event' => [
			static function () {
				return tribe_events()->set_args(
					[
						'title'      => 'Test Event',
						'status'     => 'publish',
						'start_date' => '2022-10-01 10:00:00',
						'duration'   => 2 * HOUR_IN_SECONDS,
					]
				)->create()->ID;
			},
		];
	}

	/**
	 * @dataProvider panels_with_no_provider_data_provider
	 */
	public function test_get_panels_with_no_providers( Closure $fixture ): void {
		// Equivalent to deactivating Commerce.
		add_filter( 'tribe_tickets_get_modules', '__return_empty_array' );
		$post_id = $fixture();
		$this->set_fn_return( 'wp_create_nonce', '33333333' );

		$metabox = tribe( Metabox::class );
		$panels  = $metabox->get_panels( $post_id );
		$html    = implode( '', $panels );
		$html    = $this->placehold_post_ids(
			$html,
			[
				'post_id' => $post_id,
			]
		);
		// Depending on the Common versions, the assets might be loaded from ET or TEC; this should not break the tests.
		$html = str_replace( 'the-events-calendar/common', 'event-tickets/common', $html );

		$this->assertMatchesHtmlSnapshot( $html );
	}

	/**
	 * Data provider for ajax_panels permission tests.
	 *
	 * @return Generator
	 */
	public function ajax_panels_permission_provider(): Generator {
		yield 'auto-draft post owned by current user should succeed' => [
			function (): array {
				$user_id = $this->factory()->user->create( [ 'role' => 'editor' ] );
				wp_set_current_user( $user_id );
				
				$post_id = $this->factory()->post->create( [
					'post_status' => 'auto-draft',
					'post_author' => $user_id,
				] );

				return [
					'post_id'     => $post_id,
					'should_pass' => true,
				];
			},
		];

		yield 'auto-draft post NOT owned by current user should fail' => [
			function (): array {
				$author_id = $this->factory()->user->create( [ 'role' => 'editor' ] );
				$other_user_id = $this->factory()->user->create( [ 'role' => 'editor' ] );
				
				$post_id = $this->factory()->post->create( [
					'post_status' => 'auto-draft',
					'post_author' => $author_id,
				] );
				
				// Switch to different user.
				wp_set_current_user( $other_user_id );

				return [
					'post_id'     => $post_id,
					'should_pass' => false,
					'error_message' => 'You do not have permission to access this content.',
				];
			},
		];

		yield 'published post with edit permissions should succeed' => [
			function (): array {
				$user_id = $this->factory()->user->create( [ 'role' => 'editor' ] );
				wp_set_current_user( $user_id );
				
				$post_id = $this->factory()->post->create( [
					'post_status' => 'publish',
					'post_author' => $user_id,
				] );

				return [
					'post_id'     => $post_id,
					'should_pass' => true,
				];
			},
		];

		yield 'published post without edit permissions should fail' => [
			function (): array {
				$author_id = $this->factory()->user->create( [ 'role' => 'editor' ] );
				$subscriber_id = $this->factory()->user->create( [ 'role' => 'subscriber' ] );
				
				$post_id = $this->factory()->post->create( [
					'post_status' => 'publish',
					'post_author' => $author_id,
				] );
				
				// Switch to subscriber (no edit permissions).
				wp_set_current_user( $subscriber_id );

				return [
					'post_id'     => $post_id,
					'should_pass' => false,
					'error_message' => 'You do not have permission to access this content.',
				];
			},
		];

		yield 'password protected post with edit permissions should succeed' => [
			function (): array {
				$user_id = $this->factory()->user->create( [ 'role' => 'editor' ] );
				wp_set_current_user( $user_id );
				
				$post_id = $this->factory()->post->create( [
					'post_status'   => 'publish',
					'post_author'   => $user_id,
					'post_password' => 'secret123',
				] );

				return [
					'post_id'     => $post_id,
					'should_pass' => true,
				];
			},
		];

		yield 'draft post with edit permissions should succeed' => [
			function (): array {
				$user_id = $this->factory()->user->create( [ 'role' => 'editor' ] );
				wp_set_current_user( $user_id );
				
				$post_id = $this->factory()->post->create( [
					'post_status' => 'draft',
					'post_author' => $user_id,
				] );

				return [
					'post_id'     => $post_id,
					'should_pass' => true,
				];
			},
		];

		yield 'event auto-draft owned by current user should succeed' => [
			function (): array {
				$user_id = $this->factory()->user->create( [ 'role' => 'editor' ] );
				wp_set_current_user( $user_id );
				
				// Create auto-draft event using wp_insert_post to ensure correct status.
				$post_id = wp_insert_post( [
					'post_type'   => TEC::POSTTYPE,
					'post_status' => 'auto-draft',
					'post_author' => $user_id,
					'post_title'  => 'Test Event',
				] );

				return [
					'post_id'     => $post_id,
					'should_pass' => true,
				];
			},
		];

		yield 'event auto-draft NOT owned by current user should fail' => [
			function (): array {
				$author_id = $this->factory()->user->create( [ 'role' => 'editor' ] );
				$other_user_id = $this->factory()->user->create( [ 'role' => 'editor' ] );
				
				// Create auto-draft event using wp_insert_post to ensure correct status.
				$post_id = wp_insert_post( [
					'post_type'   => TEC::POSTTYPE,
					'post_status' => 'auto-draft',
					'post_author' => $author_id,
					'post_title'  => 'Test Event',
				] );
				
				// Verify it's actually auto-draft.
				$post = get_post( $post_id );
				
				// Switch to different user.
				wp_set_current_user( $other_user_id );

				return [
					'post_id'     => $post_id,
					'should_pass' => false,
					'error_message' => 'You do not have permission to access this content.',
				];
			},
		];
	}

	/**
	 * Test ajax_panels permission logic.
	 *
	 * @dataProvider ajax_panels_permission_provider
	 *
	 * @param Closure $fixture The fixture closure.
	 *
	 * @return void
	 */
	public function test_ajax_panels_permissions( Closure $fixture ): void {
		$data = $fixture();
		
		$metabox = tribe( Metabox::class );

		// Simulate AJAX request.
		$_POST = [
			'post_id' => $data['post_id'],
		];
		$_REQUEST = $_POST;

		// Mock both success and error responses.
		$wp_send_json_success = $this->mock_wp_send_json_success();
		$wp_send_json_error = $this->mock_wp_send_json_error();
		
		$metabox->ajax_panels();

		if ( $data['should_pass'] ) {
			// Should have called wp_send_json_success, not wp_send_json_error.
			$this->assertTrue( 
				$wp_send_json_success->was_called(), 
				'Expected ajax_panels to succeed but it called wp_send_json_error instead.'
			);
			$this->assertFalse(
				$wp_send_json_error->was_called(),
				'Expected ajax_panels to succeed but wp_send_json_error was called.'
			);
		} else {
			// Should have called wp_send_json_error, not wp_send_json_success.
			$this->assertTrue(
				$wp_send_json_error->was_called(),
				'Expected ajax_panels to fail but it called wp_send_json_success instead.'
			);
			$this->assertFalse(
				$wp_send_json_success->was_called(),
				'Expected ajax_panels to fail but wp_send_json_success was called.'
			);
			
			// Verify the error message contains expected text.
			$calls = $wp_send_json_error->get_calls();
			$error_message = $calls[0][0];
			$this->assertStringContainsString(
				$data['error_message'],
				$error_message,
				'Expected specific error message.'
			);
		}
	}

	/**
	 * Test ajax_panels with invalid post ID.
	 *
	 * @return void
	 */
	public function test_ajax_panels_with_invalid_post_id(): void {
		$user_id = $this->factory()->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $user_id );

		$metabox = tribe( Metabox::class );

		// Simulate AJAX request with invalid post ID.
		$_POST = [
			'post_id' => 999999,
		];
		$_REQUEST = $_POST;

		// Mock error JSON response.
		$wp_send_json_error = $this->mock_wp_send_json_error();
		
		$metabox->ajax_panels();
		
		// Verify error was sent.
		$this->assertTrue( $wp_send_json_error->was_called(), 'Expected wp_send_json_error to be called.' );
		
		// Get the error message.
		$calls = $wp_send_json_error->get_calls();
		$error_message = $calls[0][0];
		
		$this->assertStringContainsString( 'Invalid Post ID', $error_message );
	}

	/**
	 * Test ajax_panels with missing post ID.
	 *
	 * @return void
	 */
	public function test_ajax_panels_with_missing_post_id(): void {
		$user_id = $this->factory()->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $user_id );

		$metabox = tribe( Metabox::class );

		// Simulate AJAX request with no post ID.
		$_POST = [];
		$_REQUEST = $_POST;

		// Mock error JSON response.
		$wp_send_json_error = $this->mock_wp_send_json_error();
		
		$metabox->ajax_panels();
		
		// Verify error was sent.
		$this->assertTrue( $wp_send_json_error->was_called(), 'Expected wp_send_json_error to be called.' );
		
		// Get the error message.
		$calls = $wp_send_json_error->get_calls();
		$error_message = $calls[0][0];
		
		$this->assertStringContainsString( 'Invalid Post ID', $error_message );
	}

	public function tearDown() {
		parent::tearDown();
		uopz_unset_return( 'strtotime' );
		uopz_unset_return( Date_Utils::class, 'build_date_object' );
	}
}
