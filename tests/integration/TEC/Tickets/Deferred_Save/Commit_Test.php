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
	public function it_fires_the_same_actions_in_the_same_order_as_the_classic_ajax_save_and_delete(): void {
		$this->log_in_as_admin();
		$post_id = static::factory()->post->create();
		$metabox = tribe( 'tickets.metabox' );

		// Today's path: create, update and delete through the classic AJAX handlers.
		$this->record_ticket_actions();
		$_POST = [
			'post_id'     => $post_id,
			'data'        => $this->ticket_data( 'AJAX ticket' ),
			'ticket_type' => 'default',
			'nonce'       => wp_create_nonce( 'add_ticket_nonce' ),
		];
		$added = $metabox->ajax_ticket_add( true );
		$this->assertIsArray( $added, 'The AJAX add must succeed for the comparison to mean anything.' );
		$ajax_ticket_ids = tribe_tickets()->where( 'event', $post_id )->get_ids();
		$ajax_ticket_id  = (int) end( $ajax_ticket_ids );
		$_POST = [
			'post_id'     => $post_id,
			'data'        => $this->ticket_data( 'AJAX ticket renamed', [ 'ticket_id' => $ajax_ticket_id ] ),
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
	public function it_returns_created_ids_by_position_in_the_order_sent(): void {
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
	public function one_failing_entry_does_not_stop_the_others(): void {
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
	public function a_create_without_a_provider_is_rejected(): void {
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
	public function an_update_is_saved_through_the_tickets_own_provider_whatever_the_data_says(): void {
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
	public function an_update_keeps_the_ticket_type_and_menu_order_it_does_not_mention(): void {
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
	public function a_delete_removes_the_ticket_and_tells_listeners(): void {
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
	public function errors_from_the_checks_and_from_the_replay_are_both_reported(): void {
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
	public function the_routes_filter_can_send_entries_to_another_post(): void {
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
	public function the_ticket_type_is_sanitized_before_it_reaches_the_meta(): void {
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
	public function a_route_to_a_post_the_user_cannot_edit_is_rejected(): void {
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
	public function a_payload_with_too_many_entries_is_rejected_as_a_whole(): void {
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
	public function a_move_lands_the_ticket_and_its_attendees_on_the_destination(): void {
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
	public function a_ticket_that_is_updated_and_moved_is_updated_first_then_moved(): void {
		$this->log_in_as_admin();
		$post_id        = static::factory()->post->create();
		$destination_id = static::factory()->post->create();
		$ticket_id      = $this->create_tc_ticket( $post_id, 10 );

		$result = $this->commit()->run(
			[
				'update' => [ $ticket_id => [ 'ticket_name' => 'Renamed then moved' ] ],
				'move'   => [ $ticket_id => $destination_id ],
			],
			$post_id
		);

		$this->assertSame( [], $result->get_errors() );
		$this->assertSame( 'Renamed then moved', get_the_title( $ticket_id ) );
		$this->assertSame( [ $ticket_id ], tribe_tickets()->where( 'event', $destination_id )->get_ids() );
	}

	/**
	 * @test
	 */
	public function a_refused_move_is_reported_and_the_rest_commits(): void {
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
	public function parts_run_in_update_move_create_delete_order(): void {
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
	public function an_empty_payload_commits_nothing_and_a_malformed_one_reports_it(): void {
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
}
