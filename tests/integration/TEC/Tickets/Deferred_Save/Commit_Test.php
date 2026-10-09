<?php

namespace TEC\Tickets\Deferred_Save;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Module;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use Tribe__Tickets__RSVP as RSVP;

class Commit_Test extends WPTestCase {
	use Ticket_Maker;
	use RSVP_Ticket_Maker;
	use Attendee_Maker;
	use With_Tickets_Commerce;

	/**
	 * Every action a ticket write fires today, through ticket_add(), the providers, the AJAX and REST callers.
	 */
	private const TICKET_ACTIONS = [
		'tec_tickets_ticket_pre_save',
		'event_tickets_after_create_ticket',
		'event_tickets_after_update_ticket',
		'event_tickets_after_save_ticket',
		'tec_tickets_commerce_after_create_ticket',
		'tec_tickets_commerce_after_update_ticket',
		'tec_tickets_commerce_after_save_ticket',
		'tribe_tickets_ticket_add',
		'tec_tickets_ticket_add',
		'tec_tickets_ticket_update',
		'tec_tickets_ticket_upserted',
		'tribe_tickets_ticket_added',
		'tec_tickets_commerce_ticket_deleted',
		'event_tickets_attendee_ticket_deleted',
		'tribe_tickets_ticket_deleted',
		'tribe_tickets_ticket_type_before_move',
		'tribe_tickets_ticket_type_moved',
	];

	private array $recorded = [];
	private bool $recording = false;

	public function tearDown(): void {
		$_POST = [];
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	protected function log_in_as_admin(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	protected function record_ticket_actions(): void {
		$this->recorded = [];
		if ( $this->recording ) {
			return;
		}
		$this->recording = true;
		foreach ( self::TICKET_ACTIONS as $action ) {
			add_action(
				$action,
				function () use ( $action ) {
					$this->recorded[] = $action;
				}
			);
		}
	}

	protected function ticket_data( string $name, array $overrides = [] ): array {
		return array_merge(
			[
				'ticket_name'        => $name,
				'ticket_description' => 'A ticket',
				'ticket_price'       => '10',
				'ticket_provider'    => Module::class,
				'tribe-ticket'       => [ 'mode' => 'own', 'capacity' => '25' ],
			],
			$overrides
		);
	}

	protected function commit(): Commit {
		return tribe( Commit::class );
	}

	/**
	 * Each error as `[ part, key, applied ]`: `applied` says the write happened and something after it failed.
	 */
	protected function error_outcomes( Result $result ): array {
		return array_map(
			static fn( array $error ) => [ $error['part'], $error['key'], $error['applied'] ?? false ],
			$result->get_errors()
		);
	}

	protected function rsvp_ids_on( int $post_id ): array {
		return array_map(
			'intval',
			get_posts(
				[
					'post_type'  => RSVP::get_instance()->ticket_object,
					'meta_key'   => RSVP::get_instance()->get_event_key(),
					'meta_value' => $post_id,
					'fields'     => 'ids',
				]
			)
		);
	}

	protected function error_keys( Result $result, string $part ): array {
		return array_values(
			array_map(
				static fn( array $error ) => $error['key'],
				array_filter( $result->get_errors(), static fn( array $error ) => $error['part'] === $part )
			)
		);
	}

	/**
	 * @test
	 */
	public function it_should_fire_the_same_actions_in_the_same_order_as_the_classic_ajax_save_and_delete(): void {
		$this->log_in_as_admin();
		$post_id = static::factory()->post->create();
		$metabox = tribe( 'tickets.metabox' );

		// Today's path: create, update and delete through the classic AJAX handlers.
		$this->record_ticket_actions();
		$_POST = [
			'post_id'     => $post_id,
			'data'        => http_build_query( $this->ticket_data( 'AJAX ticket' ) ),
			'ticket_type' => 'default',
			'nonce'       => wp_create_nonce( 'add_ticket_nonce' ),
		];
		$added = $metabox->ajax_ticket_add( true );
		$this->assertIsArray( $added, 'The AJAX add must succeed for the comparison to mean anything.' );
		$ajax_ticket_ids = tribe_tickets()->where( 'event', $post_id )->get_ids();
		$ajax_ticket_id  = (int) end( $ajax_ticket_ids );
		$_POST = [
			'post_id'     => $post_id,
			'data'        => http_build_query( $this->ticket_data( 'AJAX ticket renamed', [ 'ticket_id' => $ajax_ticket_id ] ) ),
			'ticket_type' => 'default',
			'nonce'       => wp_create_nonce( 'add_ticket_nonce' ),
		];
		$this->assertIsArray( $metabox->ajax_ticket_add( true ) );
		$_POST = [
			'post_id'   => $post_id,
			'ticket_id' => $ajax_ticket_id,
			'nonce'     => wp_create_nonce( 'remove_ticket_nonce' ),
		];
		$this->assertIsArray( $metabox->ajax_ticket_delete( true ) );
		$ajax_actions = $this->recorded;
		$_POST        = [];

		// The new path: the same three changes through Commit.
		$this->record_ticket_actions();
		$created = $this->commit()->run( [ 'create' => [ $this->ticket_data( 'Commit ticket' ) ] ], $post_id );
		$this->assertSame( [], $created->get_errors() );
		$commit_ticket_id = $created->get_created()[0];
		$this->commit()->run( [ 'update' => [ $commit_ticket_id => $this->ticket_data( 'Commit ticket renamed' ) ] ], $post_id );
		$this->commit()->run( [ 'delete' => [ $commit_ticket_id ] ], $post_id );
		$commit_actions = $this->recorded;

		$this->assertSame( $ajax_actions, $commit_actions );
		$this->assertSame(
			[
				// Create.
				'tec_tickets_ticket_pre_save',
				'tec_tickets_commerce_after_create_ticket',
				'tec_tickets_commerce_after_save_ticket',
				'event_tickets_after_create_ticket',
				'event_tickets_after_save_ticket',
				'tribe_tickets_ticket_add',
				'tec_tickets_ticket_add',
				'tec_tickets_ticket_upserted',
				'tribe_tickets_ticket_added',
				// Update.
				'tec_tickets_ticket_pre_save',
				'tec_tickets_commerce_after_update_ticket',
				'tec_tickets_commerce_after_save_ticket',
				'event_tickets_after_update_ticket',
				'event_tickets_after_save_ticket',
				'tribe_tickets_ticket_add',
				'tec_tickets_ticket_update',
				'tec_tickets_ticket_upserted',
				'tribe_tickets_ticket_added',
				// Delete.
				'tec_tickets_commerce_ticket_deleted',
				'event_tickets_attendee_ticket_deleted',
				'tribe_tickets_ticket_deleted',
			],
			$commit_actions
		);
	}

	/**
	 * @test
	 */
	public function it_should_return_created_ids_by_position_in_the_order_sent(): void {
		$this->log_in_as_admin();
		$post_id = static::factory()->post->create();

		$result = $this->commit()->run(
			[
				'create' => [
					0 => $this->ticket_data( 'First' ),
					1 => $this->ticket_data( 'Second' ),
					2 => $this->ticket_data( 'Third' ),
				],
			],
			$post_id
		);

		$created = $result->get_created();
		$this->assertSame( [ 0, 1, 2 ], array_keys( $created ) );
		$this->assertSame( [ 'First', 'Second', 'Third' ], array_map( 'get_the_title', $created ) );
		$this->assertTrue( $created[0] < $created[1] && $created[1] < $created[2] );
		$this->assertEqualSets( $created, tribe_tickets()->where( 'event', $post_id )->get_ids() );
	}

	/**
	 * @test
	 */
	public function it_should_not_let_one_failing_entry_stop_the_others(): void {
		$this->log_in_as_admin();
		$post_id = static::factory()->post->create();

		$result = $this->commit()->run(
			[
				'create' => [
					$this->ticket_data( 'Good one' ),
					$this->ticket_data( 'Bad provider', [ 'ticket_provider' => 'Not_A_Provider' ] ),
					$this->ticket_data( 'Good two' ),
				],
			],
			$post_id
		);

		$this->assertSame( [ 0, 2 ], array_keys( $result->get_created() ) );
		$this->assertSame( [ 1 ], $this->error_keys( $result, 'create' ) );
		$this->assertNotEmpty( $result->get_errors()[0]['message'] );
		$this->assertCount( 2, tribe_tickets()->where( 'event', $post_id )->get_ids() );
	}

	/**
	 * @test
	 */
	public function it_should_reject_a_create_without_a_provider(): void {
		$this->log_in_as_admin();
		$post_id = static::factory()->post->create();
		$data    = $this->ticket_data( 'No provider' );
		unset( $data['ticket_provider'] );

		$result = $this->commit()->run( [ 'create' => [ $data ] ], $post_id );

		$this->assertSame( [], $result->get_created() );
		$this->assertSame( [ 0 ], $this->error_keys( $result, 'create' ) );
		$this->assertSame( [], tribe_tickets()->where( 'event', $post_id )->get_ids() );
	}

	/**
	 * @test
	 */
	public function it_should_save_an_update_through_the_tickets_own_provider_whatever_the_data_says(): void {
		$this->log_in_as_admin();
		$post_id        = static::factory()->post->create();
		$rsvp_ticket_id = $this->create_rsvp_ticket( $post_id );

		$result = $this->commit()->run(
			[ 'update' => [ $rsvp_ticket_id => [ 'ticket_name' => 'Renamed RSVP', 'ticket_provider' => Module::class ] ] ],
			$post_id
		);

		$this->assertSame( [], $result->get_errors() );
		$this->assertSame( 'Renamed RSVP', get_the_title( $rsvp_ticket_id ) );
		$this->assertSame( RSVP::class, tribe_tickets_get_ticket_provider( $rsvp_ticket_id )->class_name );
	}

	/**
	 * @test
	 */
	public function it_should_keep_the_ticket_type_and_menu_order_an_update_does_not_mention(): void {
		$this->log_in_as_admin();
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_ticket( $post_id, 10 );
		update_post_meta( $ticket_id, '_type', 'series_pass' );
		wp_update_post( [ 'ID' => $ticket_id, 'menu_order' => 7 ] );

		$result = $this->commit()->run( [ 'update' => [ $ticket_id => [ 'ticket_name' => 'Still a pass' ] ] ], $post_id );

		$this->assertSame( [], $result->get_errors() );
		$this->assertSame( 'Still a pass', get_the_title( $ticket_id ) );
		$this->assertSame( 'series_pass', get_post_meta( $ticket_id, '_type', true ) );
		$this->assertSame( 7, get_post( $ticket_id )->menu_order );
	}

	/**
	 * @test
	 */
	public function it_should_remove_the_ticket_on_delete_and_tell_listeners(): void {
		$this->log_in_as_admin();
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_ticket( $post_id, 10 );
		$told      = [];
		add_action(
			'tribe_tickets_ticket_deleted',
			static function ( $deleted_post_id ) use ( &$told ) {
				$told[] = $deleted_post_id;
			}
		);

		$result = $this->commit()->run( [ 'delete' => [ $ticket_id ] ], $post_id );

		$this->assertSame( [], $result->get_errors() );
		$this->assertNull( get_post( $ticket_id ) );
		$this->assertSame( [ $post_id ], $told );
	}

	/**
	 * @test
	 */
	public function it_should_report_errors_from_the_checks_and_from_the_replay(): void {
		$this->log_in_as_admin();
		$post_id       = static::factory()->post->create();
		$other_post_id = static::factory()->post->create();
		$foreign_id    = $this->create_tc_ticket( $other_post_id, 10 );

		$result = $this->commit()->run(
			[
				'update' => [ $foreign_id => [ 'ticket_name' => 'Not yours' ] ],
				'create' => [ $this->ticket_data( 'Bad', [ 'ticket_provider' => 'Nope' ] ), $this->ticket_data( 'Good' ) ],
			],
			$post_id
		);

		$this->assertSame( [ $foreign_id ], $this->error_keys( $result, 'update' ) );
		$this->assertSame( [ 0 ], $this->error_keys( $result, 'create' ) );
		$this->assertSame( [ 1 ], array_keys( $result->get_created() ) );
		$this->assertNotSame( 'Not yours', get_the_title( $foreign_id ), 'The foreign ticket must not change.' );
	}

	/**
	 * @test
	 */
	public function it_should_let_the_routes_filter_send_entries_to_another_post(): void {
		$this->log_in_as_admin();
		$post_id       = static::factory()->post->create();
		$other_post_id = static::factory()->post->create();
		$seen          = [];
		add_filter(
			'tec_tickets_deferred_save_routes',
			static function ( array $routes, int $routed_post_id, Payload $payload ) use ( $other_post_id, &$seen ): array {
				$seen = [ array_keys( $routes ), $routed_post_id ];

				return [ $other_post_id => $payload ];
			},
			10,
			3
		);

		$result = $this->commit()->run(
			[ 'create' => [ 2 => $this->ticket_data( 'Routed A' ), 5 => $this->ticket_data( 'Routed B' ) ] ],
			$post_id
		);

		$this->assertSame( [ [ $post_id ], $post_id ], $seen );
		$this->assertSame( [ 2, 5 ], array_keys( $result->get_created() ) );
		$this->assertSame( [], tribe_tickets()->where( 'event', $post_id )->get_ids() );
		$this->assertEqualSets( array_values( $result->get_created() ), tribe_tickets()->where( 'event', $other_post_id )->get_ids() );
	}

	/**
	 * @test
	 */
	public function it_should_sanitize_the_ticket_type_before_it_reaches_the_meta(): void {
		$this->log_in_as_admin();
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_ticket( $post_id, 10 );

		$result = $this->commit()->run(
			[
				'create' => [
					$this->ticket_data( 'Array type', [ 'ticket_type' => [ 'nested' => 'array' ] ] ),
					$this->ticket_data( 'HTML type', [ 'ticket_type' => '<b>series_pass</b>' ] ),
				],
				'update' => [ $ticket_id => [ 'ticket_name' => 'Array type on update', 'ticket_type' => [ 'x' ] ] ],
			],
			$post_id
		);

		$this->assertSame( [], $result->get_errors() );
		$this->assertSame( 'default', get_post_meta( $result->get_created()[0], '_type', true ) );
		$this->assertSame( 'series_pass', get_post_meta( $result->get_created()[1], '_type', true ) );
		$this->assertSame( 'default', get_post_meta( $ticket_id, '_type', true ) );
	}

	/**
	 * @test
	 */
	public function it_should_reject_a_route_to_a_post_the_user_cannot_edit(): void {
		$author_id = static::factory()->user->create( [ 'role' => 'author' ] );
		wp_set_current_user( $author_id );
		$post_id       = static::factory()->post->create( [ 'post_author' => $author_id ] );
		$other_post_id = static::factory()->post->create( [ 'post_author' => static::factory()->user->create( [ 'role' => 'editor' ] ) ] );
		add_filter(
			'tec_tickets_deferred_save_routes',
			static function ( array $routes, int $routed_post_id, Payload $payload ) use ( $other_post_id ): array {
				return [ $other_post_id => $payload ];
			},
			10,
			3
		);

		$result = $this->commit()->run( [ 'create' => [ $this->ticket_data( 'Routed away' ) ] ], $post_id );

		$this->assertSame( [], $result->get_created() );
		$this->assertCount( 1, $result->get_errors() );
		$this->assertNull( $result->get_errors()[0]['part'] );
		$this->assertSame( [], tribe_tickets()->where( 'event', $other_post_id )->get_ids() );
	}

	/**
	 * @test
	 */
	public function it_should_check_a_different_payload_routed_under_the_saved_posts_own_id(): void {
		$this->log_in_as_admin();
		$post_id           = static::factory()->post->create();
		$other_post_id     = static::factory()->post->create();
		$ticket_id         = $this->create_tc_ticket( $post_id, 10 );
		$foreign_ticket_id = $this->create_tc_ticket( $other_post_id, 10 );
		add_filter(
			'tec_tickets_deferred_save_routes',
			static function ( array $routes, int $routed_post_id ) use ( $foreign_ticket_id ): array {
				return [ $routed_post_id => new Payload( [ $foreign_ticket_id => [ 'ticket_name' => 'Hijacked' ] ] ) ];
			},
			10,
			2
		);

		$result = $this->commit()->run( [ 'update' => [ $ticket_id => [ 'ticket_name' => 'Renamed' ] ] ], $post_id );

		$this->assertContains( $foreign_ticket_id, $this->error_keys( $result, 'update' ) );
		$this->assertNotSame( 'Hijacked', get_the_title( $foreign_ticket_id ) );
	}

	/**
	 * @test
	 */
	public function it_should_write_a_route_to_an_occurrence_id_against_the_post_it_was_checked_against(): void {
		$this->log_in_as_admin();
		$post_id       = static::factory()->post->create();
		$other_post_id = static::factory()->post->create();
		$occurrence_id = $other_post_id + 100000;
		add_filter(
			'tec_tickets_filter_event_id',
			static fn( $id ) => (int) $id === $occurrence_id ? $other_post_id : $id
		);
		add_filter(
			'tec_tickets_deferred_save_routes',
			static fn( array $routes, int $routed_post_id, Payload $payload ): array => [ $occurrence_id => $payload ],
			10,
			3
		);

		$result = $this->commit()->run( [ 'create' => [ $this->ticket_data( 'On the occurrence' ) ] ], $post_id );

		$this->assertSame( [], $result->get_errors() );
		$this->assertSame( array_values( $result->get_created() ), tribe_tickets()->where( 'event', $other_post_id )->get_ids() );
	}

	/**
	 * @return \Generator<string,array{0:callable}>
	 */
	public function routes_that_drop_entries_provider(): \Generator {
		yield 'no routes' => [ static fn() => [] ];
		yield 'null' => [ static fn() => null ];
		yield 'not a payload' => [ static fn( array $routes, int $post_id ) => [ $post_id => 'nope' ] ];
		yield 'key that is not an int' => [ static fn( array $routes, int $post_id, Payload $payload ) => [ " $post_id" => $payload ] ];
	}

	/**
	 * @test
	 * @dataProvider routes_that_drop_entries_provider
	 */
	public function it_should_report_every_checked_entry_no_route_replayed( callable $routes ): void {
		$this->log_in_as_admin();
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_ticket( $post_id, 10 );
		add_filter( 'tec_tickets_deferred_save_routes', $routes, 10, 3 );

		$result = $this->commit()->run(
			[
				'update' => [ $ticket_id => [ 'ticket_name' => 'Renamed' ] ],
				'create' => [ 3 => $this->ticket_data( 'New' ) ],
			],
			$post_id
		);

		$this->assertSame( [], $result->get_created() );
		$this->assertSame( [ $ticket_id ], $this->error_keys( $result, 'update' ) );
		$this->assertSame( [ 3 ], $this->error_keys( $result, 'create' ) );
	}

	/**
	 * @test
	 */
	public function it_should_skip_a_routed_payload_whose_keys_are_not_ints_instead_of_failing(): void {
		$this->log_in_as_admin();
		$post_id       = static::factory()->post->create();
		$other_post_id = static::factory()->post->create();
		add_filter(
			'tec_tickets_deferred_save_routes',
			static fn( array $routes, int $routed_post_id, Payload $payload ): array => [
				$routed_post_id => $payload,
				$other_post_id  => new Payload( [ 'abc' => [ 'ticket_name' => 'x' ] ], [ 'k' => [ 'ticket_name' => 'y' ] ] ),
			],
			10,
			3
		);

		$result = $this->commit()->run( [ 'create' => [ $this->ticket_data( 'Kept' ) ] ], $post_id );

		$this->assertSame( [ 0 ], array_keys( $result->get_created() ) );
		$this->assertSame( [], $result->get_errors(), 'The skipped route carried nothing the checks passed.' );
		$this->assertSame( [], tribe_tickets()->where( 'event', $other_post_id )->get_ids() );
	}

	/**
	 * @return \Generator<string,array{0:mixed}>
	 */
	public function invalid_price_provider(): \Generator {
		yield 'negative' => [ '-25' ];
		yield 'not a number' => [ 'abc' ];
		yield 'not a scalar' => [ [ '10' ] ];
	}

	/**
	 * @test
	 * @dataProvider invalid_price_provider
	 */
	public function it_should_reject_an_invalid_tickets_commerce_price_as_the_block_editor_endpoint_does( $price ): void {
		$this->log_in_as_admin();
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_ticket( $post_id, 10 );

		$result = $this->commit()->run(
			[
				'update' => [ $ticket_id => $this->ticket_data( 'Updated', [ 'ticket_price' => $price ] ) ],
				'create' => [ $this->ticket_data( 'Created', [ 'ticket_price' => $price ] ) ],
			],
			$post_id
		);

		$this->assertSame( [], $result->get_created() );
		$this->assertSame( [ $ticket_id ], $this->error_keys( $result, 'update' ) );
		$this->assertSame( [ 0 ], $this->error_keys( $result, 'create' ) );
		$this->assertSame( '10', (string) get_post_meta( $ticket_id, '_price', true ) );
		$this->assertSame( [ $ticket_id ], tribe_tickets()->where( 'event', $post_id )->get_ids() );
	}

	/**
	 * @test
	 */
	public function it_should_save_a_blank_tickets_commerce_price_as_free_and_leave_rsvp_prices_alone(): void {
		$this->log_in_as_admin();
		$post_id = static::factory()->post->create();

		$result = $this->commit()->run(
			[
				'create' => [
					$this->ticket_data( 'Free', [ 'ticket_price' => ' ' ] ),
					$this->ticket_data( 'RSVP', [ 'ticket_price' => 'abc', 'ticket_provider' => RSVP::class ] ),
				],
			],
			$post_id
		);

		$this->assertSame( [], $result->get_errors() );
		$this->assertSame( [ 0, 1 ], array_keys( $result->get_created() ) );
	}

	/**
	 * @test
	 */
	public function it_should_reject_a_payload_with_too_many_entries_as_a_whole(): void {
		$this->log_in_as_admin();
		$post_id = static::factory()->post->create();
		add_filter( 'tec_tickets_deferred_save_max_entries', static fn() => 2 );

		$result = $this->commit()->run(
			[ 'create' => [ $this->ticket_data( 'One' ), $this->ticket_data( 'Two' ), $this->ticket_data( 'Three' ) ] ],
			$post_id
		);

		$this->assertSame( [], $result->get_created() );
		$this->assertCount( 1, $result->get_errors() );
		$this->assertNull( $result->get_errors()[0]['part'] );
		$this->assertSame( [], tribe_tickets()->where( 'event', $post_id )->get_ids() );
	}

	/**
	 * @test
	 */
	public function it_should_move_the_ticket_and_its_attendees_to_the_destination(): void {
		$this->log_in_as_admin();
		$post_id        = static::factory()->post->create();
		$destination_id = static::factory()->post->create();
		$ticket_id      = $this->create_tc_ticket( $post_id, 10 );
		$attendee_id    = $this->create_attendee_for_ticket( $ticket_id, $post_id );
		$fired          = [];
		foreach ( [ 'tribe_tickets_ticket_type_before_move', 'tribe_tickets_ticket_type_moved' ] as $action ) {
			add_action(
				$action,
				static function ( ...$args ) use ( $action, &$fired ) {
					// The source post ID is read from post meta, so it arrives as a string, as it does today.
					$fired[] = [ $action, array_map( 'intval', array_slice( $args, 0, 3 ) ) ];
				},
				10,
				4
			);
		}

		$result = $this->commit()->run( [ 'move' => [ $ticket_id => $destination_id ] ], $post_id );

		$this->assertSame( [], $result->get_errors() );
		$this->assertSame( [ $ticket_id ], tribe_tickets()->where( 'event', $destination_id )->get_ids() );
		$this->assertSame( [], tribe_tickets()->where( 'event', $post_id )->get_ids() );
		$this->assertSame( (string) $destination_id, get_post_meta( $attendee_id, Module::ATTENDEE_EVENT_KEY, true ) );
		$this->assertSame(
			[
				[ 'tribe_tickets_ticket_type_before_move', [ $ticket_id, $destination_id, get_current_user_id() ] ],
				[ 'tribe_tickets_ticket_type_moved', [ $ticket_id, $destination_id, $post_id ] ],
			],
			$fired
		);
	}

	/**
	 * @test
	 */
	public function it_should_refuse_updating_and_moving_the_same_ticket(): void {
		$this->log_in_as_admin();
		$post_id        = static::factory()->post->create();
		$destination_id = static::factory()->post->create();
		$ticket_id      = $this->create_tc_ticket( $post_id, 10 );
		$title          = get_the_title( $ticket_id );

		$result = $this->commit()->run(
			[
				'update' => [ $ticket_id => [ 'ticket_name' => 'Renamed then moved' ] ],
				'move'   => [ $ticket_id => $destination_id ],
			],
			$post_id
		);

		// One rejection names the ticket; both of its entries are dropped.
		$this->assertSame( [ $ticket_id ], $this->error_keys( $result, 'update' ) );
		$this->assertSame( $title, get_the_title( $ticket_id ) );
		$this->assertSame( [ $ticket_id ], tribe_tickets()->where( 'event', $post_id )->get_ids() );
	}

	/**
	 * @test
	 */
	public function it_should_report_a_refused_move_and_commit_the_rest(): void {
		$this->log_in_as_admin();
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_ticket( $post_id, 10 );

		// The move function refuses a move to the post the ticket is already on.
		$result = $this->commit()->run(
			[
				'move'   => [ $ticket_id => $post_id ],
				'create' => [ $this->ticket_data( 'Still created' ) ],
			],
			$post_id
		);

		$this->assertSame( [ $ticket_id ], $this->error_keys( $result, 'move' ) );
		$this->assertSame( [ 0 ], array_keys( $result->get_created() ) );
	}

	/**
	 * @test
	 */
	public function it_should_run_the_parts_in_update_move_create_delete_order(): void {
		$this->log_in_as_admin();
		$post_id        = static::factory()->post->create();
		$destination_id = static::factory()->post->create();
		$to_update      = $this->create_tc_ticket( $post_id, 10 );
		$to_move        = $this->create_tc_ticket( $post_id, 20 );
		$to_delete      = $this->create_tc_ticket( $post_id, 30 );
		$this->record_ticket_actions();

		$result = $this->commit()->run(
			[
				'delete' => [ $to_delete ],
				'create' => [ $this->ticket_data( 'New' ) ],
				'move'   => [ $to_move => $destination_id ],
				'update' => [ $to_update => [ 'ticket_name' => 'Updated' ] ],
			],
			$post_id
		);

		$this->assertSame( [], $result->get_errors() );
		$milestones = array_values(
			array_filter(
				$this->recorded,
				static fn( string $action ) => in_array( $action, [ 'tec_tickets_ticket_update', 'tribe_tickets_ticket_type_moved', 'tec_tickets_ticket_add', 'tribe_tickets_ticket_deleted' ], true )
			)
		);
		$this->assertSame( [ 'tec_tickets_ticket_update', 'tribe_tickets_ticket_type_moved', 'tec_tickets_ticket_add', 'tribe_tickets_ticket_deleted' ], $milestones );
	}

	/**
	 * @test
	 */
	public function it_should_turn_an_exception_while_saving_one_entry_into_that_entrys_error(): void {
		$this->log_in_as_admin();
		$post_id = static::factory()->post->create();
		add_action(
			'tec_tickets_ticket_pre_save',
			static function ( int $saved_post_id, $ticket, array $data ) {
				if ( 'Explodes' === ( $data['ticket_name'] ?? '' ) ) {
					throw new \TypeError( 'Unsupported operand types: string - int' );
				}
			},
			10,
			3
		);

		$result = $this->commit()->run(
			[ 'create' => [ $this->ticket_data( 'Fine' ), $this->ticket_data( 'Explodes' ), $this->ticket_data( 'Also fine' ) ] ],
			$post_id
		);

		$this->assertSame( [ 0, 2 ], array_keys( $result->get_created() ) );
		$this->assertSame( [ 1 ], $this->error_keys( $result, 'create' ) );
		$this->assertStringNotContainsString( 'Unsupported operand', $result->get_errors()[0]['message'], 'Internals stay out of the message shown to the editor.' );
		$this->assertSame( [ 'Fine', 'Also fine' ], array_map( 'get_the_title', tribe_tickets()->where( 'event', $post_id )->get_ids() ) );
	}

	/**
	 * @test
	 */
	public function it_should_save_text_and_markup_as_the_classic_ajax_save_does(): void {
		$this->log_in_as_admin();
		$post_id = static::factory()->post->create();
		$data    = $this->ticket_data(
			'<b>Bold</b> 2 < 3 name',
			[
				'ticket_description' => '<p>Kept</p>',
				'ticket_sku'         => 'SKU-1',
			]
		);

		// Today's path: tickets.js posts the edit form serialized, so `data` reaches the request helper as a string.
		$_POST = [
			'post_id'     => $post_id,
			'data'        => http_build_query( $data ),
			'ticket_type' => 'default',
			'nonce'       => wp_create_nonce( 'add_ticket_nonce' ),
		];
		$this->assertIsArray( tribe( 'tickets.metabox' )->ajax_ticket_add( true ) );
		$_POST          = [];
		$ajax_ticket_id = (int) tribe_tickets()->where( 'event', $post_id )->first()->ID;

		$result    = $this->commit()->run( [ 'create' => [ $data ] ], $post_id );
		$ticket_id = $result->get_created()[0];

		$this->assertStringContainsString( '<p>Kept</p>', get_post_field( 'post_excerpt', $ticket_id, 'raw' ) );
		$this->assertSame( get_post_field( 'post_title', $ajax_ticket_id, 'raw' ), get_post_field( 'post_title', $ticket_id, 'raw' ) );
		$this->assertSame( get_post_field( 'post_excerpt', $ajax_ticket_id, 'raw' ), get_post_field( 'post_excerpt', $ticket_id, 'raw' ) );
		$this->assertSame( get_post_meta( $ajax_ticket_id, '_sku', true ), get_post_meta( $ticket_id, '_sku', true ) );
	}

	/**
	 * @test
	 */
	public function it_should_commit_nothing_for_an_empty_payload_and_report_a_malformed_one(): void {
		$this->log_in_as_admin();
		$post_id = static::factory()->post->create();

		$empty = $this->commit()->run( null, $post_id );
		$this->assertSame( [], $empty->get_created() );
		$this->assertSame( [], $empty->get_errors() );
		$this->assertSame( [ 'created' => [], 'errors' => [] ], $empty->to_array() );

		$bad = $this->commit()->run( 'nope', $post_id );
		$this->assertSame( [], $bad->get_created() );
		$this->assertCount( 1, $bad->get_errors() );
		$this->assertNull( $bad->get_errors()[0]['part'] );
	}

	/**
	 * @test
	 */
	public function it_should_refuse_a_payload_over_the_cap_before_reading_its_entries(): void {
		$this->log_in_as_admin();
		$post_id = static::factory()->post->create();
		add_filter( 'tec_tickets_deferred_save_max_entries', static fn() => 2 );

		// Entries the parser would reject one by one: the cap counts them too, so they cannot be used to make the parser work.
		$result = $this->commit()->run( [ 'create' => [ 'not data', 'not data', 'not data' ] ], $post_id );

		$this->assertSame( [], $result->get_created() );
		$this->assertCount( 1, $result->get_errors() );
		$this->assertNull( $result->get_errors()[0]['part'] );
	}

	/**
	 * @test
	 */
	public function it_should_keep_a_created_ticket_when_a_listener_throws_after_the_save(): void {
		$this->log_in_as_admin();
		$post_id = static::factory()->post->create();
		add_action(
			'tribe_tickets_ticket_added',
			static function () {
				throw new \RuntimeException( 'A listener failed.' );
			}
		);

		$result = $this->commit()->run( [ 'create' => [ $this->ticket_data( 'Saved anyway' ) ] ], $post_id );

		// The ticket exists: reporting it as not saved would make the editor create it again; the error says the save did not finish.
		$this->assertSame( [ 0 ], array_keys( $result->get_created() ) );
		$this->assertSame( [ [ 'create', 0, true ] ], $this->error_outcomes( $result ) );
		$this->assertSame( [ 'Saved anyway' ], array_map( 'get_the_title', tribe_tickets()->where( 'event', $post_id )->get_ids() ) );
	}

	/**
	 * @test
	 */
	public function it_should_stop_flagging_manual_updates_after_an_entry_throws(): void {
		$this->log_in_as_admin();
		$post_id = static::factory()->post->create();
		add_action(
			'tec_tickets_ticket_pre_save',
			static function () {
				throw new \TypeError( 'Unsupported operand types: string - int' );
			}
		);

		$this->commit()->run( [ 'create' => [ $this->ticket_data( 'Explodes' ) ] ], $post_id );

		// `ticket_add()` turns the flag on and off around the save; an exception skipped the off.
		$this->assertFalse( has_filter( 'updated_postmeta', [ tribe( 'tickets.handler' ), 'flag_manual_update' ] ) );
	}

	/**
	 * @test
	 */
	public function it_should_write_against_the_post_the_saved_id_normalises_to(): void {
		$this->log_in_as_admin();
		$post_id     = static::factory()->post->create();
		$provisional = $post_id + 10000000;
		// As ECP maps an occurrence's provisional ID to its post.
		add_filter( 'tec_tickets_filter_event_id', static fn( $id ) => (int) $id === $provisional ? $post_id : $id );

		$result = $this->commit()->run( [ 'create' => [ $this->ticket_data( 'On the real post' ) ] ], $provisional );

		$this->assertSame( [ 0 ], array_keys( $result->get_created() ) );
		$this->assertSame( [ 'On the real post' ], array_map( 'get_the_title', tribe_tickets()->where( 'event', $post_id )->get_ids() ) );
	}

	/**
	 * @test
	 */
	public function it_should_report_a_ticket_saved_before_an_internal_listener_threw_as_created(): void {
		$this->log_in_as_admin();
		$post_id = static::factory()->post->create();
		// Fired inside `ticket_add()`, after the provider saved the ticket.
		add_action(
			'tec_tickets_ticket_add',
			static function () {
				throw new \RuntimeException( 'A listener inside ticket_add() failed.' );
			}
		);

		$result = $this->commit()->run( [ 'create' => [ $this->ticket_data( 'Saved before the throw' ) ] ], $post_id );

		$ticket_ids = tribe_tickets()->where( 'event', $post_id )->get_ids();
		$this->assertCount( 1, $ticket_ids );
		// The ticket exists, so the editor must know its ID and never create it again.
		$this->assertSame( [ 0 => (int) $ticket_ids[0] ], $result->get_created() );
		// `ticket_add()` never returned, so what it wrote is not known: not saved, and the editor sends it again as an update.
		$this->assertSame( [ [ 'create', 0, false ] ], $this->error_outcomes( $result ) );
		$this->assertFalse( has_filter( 'updated_postmeta', [ tribe( 'tickets.handler' ), 'flag_manual_update' ] ) );
	}

	/**
	 * @test
	 */
	public function it_should_report_a_delete_as_applied_when_a_listener_throws_after_it(): void {
		$this->log_in_as_admin();
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_ticket( $post_id, 10 );
		add_action(
			'tribe_tickets_ticket_deleted',
			static function () {
				throw new \RuntimeException( 'A deleted listener failed.' );
			}
		);

		$result = $this->commit()->run( [ 'delete' => [ $ticket_id ] ], $post_id );

		$this->assertNull( get_post( $ticket_id ) );
		// Deleted, so not refused: the editor must not bring the ticket back. Something after the delete failed, and the result says so.
		$this->assertSame( [ [ 'delete', $ticket_id, true ] ], $this->error_outcomes( $result ) );
	}

	/**
	 * @test
	 */
	public function it_should_report_a_move_as_applied_when_a_listener_throws_after_it(): void {
		$this->log_in_as_admin();
		$post_id        = static::factory()->post->create();
		$destination_id = static::factory()->post->create();
		$ticket_id      = $this->create_tc_ticket( $post_id, 10 );
		$attendee_id    = $this->create_attendee_for_ticket( $ticket_id, $post_id );
		// Ahead of the listener that moves the attendees along.
		add_action(
			'tribe_tickets_ticket_type_moved',
			static function () {
				throw new \RuntimeException( 'A moved listener failed.' );
			},
			5
		);

		$result = $this->commit()->run( [ 'move' => [ $ticket_id => $destination_id ] ], $post_id );

		$this->assertSame( [ $ticket_id ], tribe_tickets()->where( 'event', $destination_id )->get_ids() );
		// The ticket moved and its attendee did not: moved, so not refused, and not cleanly either.
		$this->assertSame( (string) $post_id, get_post_meta( $attendee_id, Module::ATTENDEE_EVENT_KEY, true ) );
		$this->assertSame( [ [ 'move', $ticket_id, true ] ], $this->error_outcomes( $result ) );
	}

	/**
	 * @test
	 */
	public function it_should_report_an_update_as_applied_when_a_listener_throws_after_it(): void {
		$this->log_in_as_admin();
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_ticket( $post_id, 10 );
		add_action(
			'tribe_tickets_ticket_added',
			static function () {
				throw new \RuntimeException( 'A listener failed.' );
			}
		);

		$result = $this->commit()->run( [ 'update' => [ $ticket_id => $this->ticket_data( 'Renamed anyway' ) ] ], $post_id );

		$this->assertSame( 'Renamed anyway', get_the_title( $ticket_id ) );
		$this->assertSame( [ [ 'update', $ticket_id, true ] ], $this->error_outcomes( $result ) );
	}

	/**
	 * @test
	 */
	public function it_should_report_a_ticket_attached_before_an_earlier_listener_threw_as_created(): void {
		$this->log_in_as_admin();
		$post_id = static::factory()->post->create();
		// RSVP passes the ticket's post in `meta_input`: the ticket is on the post before any save listener runs.
		add_action(
			'save_post_' . RSVP::get_instance()->ticket_object,
			static function () {
				throw new \RuntimeException( 'An earlier save listener failed.' );
			}
		);

		$result = $this->commit()->run(
			[ 'create' => [ $this->ticket_data( 'RSVP on the post before the throw', [ 'ticket_provider' => RSVP::class ] ) ] ],
			$post_id
		);

		$ticket_ids = $this->rsvp_ids_on( $post_id );
		$this->assertCount( 1, $ticket_ids );
		$this->assertSame( [ 0 => $ticket_ids[0] ], $result->get_created() );
		$this->assertSame( [ [ 'create', 0, false ] ], $this->error_outcomes( $result ) );
	}

	/**
	 * @test
	 */
	public function it_should_report_a_ticket_never_attached_to_the_post_as_not_saved(): void {
		$this->log_in_as_admin();
		$post_id = static::factory()->post->create();
		// Tickets Commerce relates the ticket to its post after the insert; failing before that leaves no ticket on the post.
		add_action(
			'save_post_' . \TEC\Tickets\Commerce\Ticket::POSTTYPE,
			static function () {
				throw new \RuntimeException( 'An earlier save listener failed.' );
			}
		);

		$result = $this->commit()->run( [ 'create' => [ $this->ticket_data( 'Never on the post' ) ] ], $post_id );

		$this->assertSame( [], $result->get_created() );
		$this->assertSame( [ [ 'create', 0, false ] ], $this->error_outcomes( $result ) );
		$this->assertSame( [], tribe_tickets()->where( 'event', $post_id )->get_ids() );
	}

	/**
	 * @test
	 */
	public function it_should_save_a_create_sent_again_with_its_key_over_the_ticket_it_created(): void {
		$this->log_in_as_admin();
		$post_id = static::factory()->post->create();
		$key     = '3f2c1a9e-7b4d-4c1e-9a8f-0d6b5e4c3a21';
		$first   = $this->commit()->run( [ 'create' => [ $this->ticket_data( 'First try', [ Commit::CREATE_KEY => $key ] ) ] ], $post_id );

		// The answer never reached the editor, which sends the create again, edited since.
		$again = $this->commit()->run( [ 'create' => [ $this->ticket_data( 'Second try', [ Commit::CREATE_KEY => $key ] ) ] ], $post_id );

		$ticket_ids = tribe_tickets()->where( 'event', $post_id )->get_ids();
		$this->assertCount( 1, $ticket_ids );
		$this->assertSame( $first->get_created(), $again->get_created() );
		$this->assertSame( [], $again->get_errors() );
		$this->assertSame( 'Second try', get_the_title( $ticket_ids[0] ) );
	}

	/**
	 * @test
	 */
	public function it_should_match_a_create_key_only_among_the_tickets_of_the_post(): void {
		$this->log_in_as_admin();
		$post_id  = static::factory()->post->create();
		$other_id = static::factory()->post->create();
		$key      = 'a-key-sent-for-another-post';
		$this->commit()->run( [ 'create' => [ $this->ticket_data( 'On the other post', [ Commit::CREATE_KEY => $key ] ) ] ], $other_id );

		$result = $this->commit()->run( [ 'create' => [ $this->ticket_data( 'On this post', [ Commit::CREATE_KEY => $key ] ) ] ], $post_id );

		$this->assertSame( [], $result->get_errors() );
		$this->assertSame( [ 'On this post' ], array_map( 'get_the_title', tribe_tickets()->where( 'event', $post_id )->get_ids() ) );
		$this->assertSame( [ 'On the other post' ], array_map( 'get_the_title', tribe_tickets()->where( 'event', $other_id )->get_ids() ) );
	}

	/**
	 * @test
	 */
	public function it_should_check_a_create_sent_again_as_the_update_it_becomes(): void {
		$this->log_in_as_admin();
		$post_id = static::factory()->post->create();
		$key     = 'a-key-whose-ticket-is-locked';
		$this->commit()->run( [ 'create' => [ $this->ticket_data( 'Locked', [ Commit::CREATE_KEY => $key ] ) ] ], $post_id );
		add_filter( 'tec_tickets_user_can_edit_ticket', '__return_false' );

		$result = $this->commit()->run( [ 'create' => [ $this->ticket_data( 'Changed anyway', [ Commit::CREATE_KEY => $key ] ) ] ], $post_id );

		$this->assertSame( [ [ 'create', 0, false ] ], $this->error_outcomes( $result ) );
		$this->assertSame( [ 'Locked' ], array_map( 'get_the_title', tribe_tickets()->where( 'event', $post_id )->get_ids() ) );
	}

	/**
	 * @test
	 */
	public function it_should_keep_the_id_of_a_create_sent_again_when_its_update_throws(): void {
		$this->log_in_as_admin();
		$post_id = static::factory()->post->create();
		$key     = 'a-key-whose-update-throws';
		$first   = $this->commit()->run( [ 'create' => [ $this->ticket_data( 'First try', [ Commit::CREATE_KEY => $key ] ) ] ], $post_id );
		add_action(
			'tec_tickets_ticket_update',
			static function () {
				throw new \RuntimeException( 'An update listener failed.' );
			}
		);

		$again = $this->commit()->run( [ 'create' => [ $this->ticket_data( 'Second try', [ Commit::CREATE_KEY => $key ] ) ] ], $post_id );

		// The ticket exists: without its ID the editor would keep treating it as a ticket to create.
		$this->assertSame( $first->get_created(), $again->get_created() );
		$this->assertSame( [ [ 'create', 0, false ] ], $this->error_outcomes( $again ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_copy_the_create_key_to_a_duplicated_ticket(): void {
		$this->log_in_as_admin();
		$post_id     = static::factory()->post->create();
		$key         = 'a-key-whose-ticket-is-copied';
		$original_id = $this->commit()->run( [ 'create' => [ $this->ticket_data( 'Original', [ Commit::CREATE_KEY => $key ] ) ] ], $post_id )->get_created()[0];
		$copy_id     = tribe( Module::class )->duplicate_ticket( $post_id, $original_id );
		$copy_title  = get_the_title( $copy_id );

		$this->commit()->run( [ 'create' => [ $this->ticket_data( 'Sent again', [ Commit::CREATE_KEY => $key ] ) ] ], $post_id );

		// A copy is another ticket: the key of the save that created the original must not find it.
		$this->assertSame( '', get_post_meta( $copy_id, Commit::CREATE_KEY_META, true ) );
		$this->assertSame( 'Sent again', get_the_title( $original_id ) );
		$this->assertSame( $copy_title, get_the_title( $copy_id ) );
	}

	/**
	 * @test
	 */
	public function it_should_keep_the_id_of_a_create_the_provider_did_not_finish_and_let_the_next_save_finish_it(): void {
		$this->log_in_as_admin();
		$post_id   = static::factory()->post->create();
		$data      = $this->ticket_data( 'Unfinished', [ 'ticket_price' => '25', 'tribe-ticket' => [ 'mode' => 'own', 'capacity' => '5' ] ] );
		$event_key = tribe( Module::class )->get_event_key();
		// Tickets Commerce relates the ticket to its post before it writes the price and the capacity.
		$throw = static function ( $meta_id, $object_id, $meta_key ) use ( $event_key ) {
			if ( $event_key === $meta_key ) {
				throw new \RuntimeException( 'A listener failed while the ticket was being saved.' );
			}
		};
		add_action( 'added_post_meta', $throw, 10, 3 );

		$result = $this->commit()->run( [ 'create' => [ $data ] ], $post_id );

		$ticket_ids = array_map( 'intval', tribe_tickets()->where( 'event', $post_id )->get_ids() );
		$this->assertCount( 1, $ticket_ids );
		$this->assertNotSame( '25', get_post_meta( $ticket_ids[0], '_price', true ) );
		// The ticket exists, so the editor gets its ID; its settings were not all written, so it is not reported as saved.
		$this->assertSame( [ 0 => $ticket_ids[0] ], $result->get_created() );
		$this->assertSame( [ [ 'create', 0, false ] ], $this->error_outcomes( $result ) );

		// What the editor sends next: the same ticket, as an update.
		remove_action( 'added_post_meta', $throw );
		$repair = $this->commit()->run( [ 'update' => [ $ticket_ids[0] => $data ] ], $post_id );

		$this->assertSame( [], $repair->get_errors() );
		$this->assertSame( $ticket_ids, array_map( 'intval', tribe_tickets()->where( 'event', $post_id )->get_ids() ) );
		$this->assertSame( '25', get_post_meta( $ticket_ids[0], '_price', true ) );
		$this->assertSame( '5', get_post_meta( $ticket_ids[0], '_tribe_ticket_capacity', true ) );
	}
}
