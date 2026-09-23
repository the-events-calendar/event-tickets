<?php

namespace TEC\Tickets\Deferred_Save;

use Codeception\TestCase\WPTestCase;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;

class Checks_Test extends WPTestCase {
	use Ticket_Maker;
	use RSVP_Ticket_Maker;
	use Attendee_Maker;
	use With_Tickets_Commerce;

	private int $post_id;
	private int $other_post_id;
	private int $ticket_id;
	private int $second_ticket_id;
	private int $foreign_ticket_id;
	private int $second_foreign_ticket_id;

	/**
	 * Fixtures are created from the test body, inside the per-test transaction. PHPUnit runs
	 * before-hook methods ahead of `setUp()`, before the transaction starts, so rows created in
	 * one are never rolled back and leak into later tests. Do not annotate this method as a hook.
	 */
	protected function given_two_posts_with_tickets(): void {
		$this->post_id                  = static::factory()->post->create();
		$this->other_post_id            = static::factory()->post->create();
		$this->ticket_id                = $this->create_tc_ticket( $this->post_id, 10 );
		$this->second_ticket_id         = $this->create_tc_ticket( $this->post_id, 20 );
		$this->foreign_ticket_id        = $this->create_tc_ticket( $this->other_post_id, 30 );
		$this->second_foreign_ticket_id = $this->create_tc_ticket( $this->other_post_id, 40 );
	}

	public function tearDown(): void {
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	protected function log_in_as( string $role ): int {
		$user_id = static::factory()->user->create( [ 'role' => $role ] );
		wp_set_current_user( $user_id );

		return $user_id;
	}

	protected function error_keys( Payload $payload, string $part ): array {
		return array_values(
			array_map(
				static fn( array $error ) => $error['key'],
				array_filter( $payload->get_errors(), static fn( array $error ) => $error['part'] === $part )
			)
		);
	}

	protected function run_checks( array $raw ): Payload {
		return tribe( Checks::class )->run( Payload::from_array( $raw ), $this->post_id );
	}

	/**
	 * @test
	 */
	public function a_user_who_cannot_edit_the_post_gets_the_whole_payload_rejected(): void {
		$this->given_two_posts_with_tickets();
		$this->log_in_as( 'subscriber' );
		$data = [ 'ticket_name' => 'x' ];

		$checked = $this->run_checks(
			[
				'update' => [ $this->ticket_id => $data ],
				'create' => [ $data ],
				'delete' => [ $this->second_ticket_id ],
				'move'   => [ $this->ticket_id => $this->other_post_id ],
			]
		);

		$this->assertFalse( $checked->is_valid() );
		$this->assertFalse( $checked->has_changes() );
		$this->assertCount( 1, $checked->get_errors() );
		$this->assertNull( $checked->get_errors()[0]['part'] );
		$this->assertNull( $checked->get_errors()[0]['key'] );
	}

	/**
	 * @test
	 */
	public function a_logged_out_user_gets_the_whole_payload_rejected(): void {
		$this->given_two_posts_with_tickets();
		wp_set_current_user( 0 );

		$checked = $this->run_checks( [ 'delete' => [ $this->ticket_id ] ] );

		$this->assertFalse( $checked->is_valid() );
		$this->assertSame( [], $checked->get_delete() );
	}

	/**
	 * @test
	 */
	public function an_editor_passes_the_post_level_check_and_owned_entries_survive(): void {
		$this->given_two_posts_with_tickets();
		$this->log_in_as( 'editor' );
		$data = [ 'ticket_name' => 'x', 'custom_field' => 'rides along' ];

		$checked = $this->run_checks(
			[
				'update' => [ $this->ticket_id => $data ],
				'create' => [ $data, $data ],
				'delete' => [ $this->second_ticket_id ],
				'move'   => [ $this->ticket_id => $this->other_post_id ],
			]
		);

		$this->assertTrue( $checked->is_valid() );
		$this->assertSame( [], $checked->get_errors() );
		$this->assertSame( [ $this->ticket_id => $data + [ 'ticket_id' => $this->ticket_id ] ], $checked->get_update() );
		$this->assertSame( [ $data, $data ], $checked->get_create() );
		$this->assertSame( [ $this->second_ticket_id ], $checked->get_delete() );
		$this->assertSame( [ $this->ticket_id => $this->other_post_id ], $checked->get_move() );
	}

	/**
	 * @test
	 */
	public function the_author_of_the_post_can_edit_it(): void {
		$this->given_two_posts_with_tickets();
		$author_id = $this->log_in_as( 'author' );
		wp_update_post( [ 'ID' => $this->post_id, 'post_author' => $author_id ] );

		$checked = $this->run_checks( [ 'delete' => [ $this->ticket_id ] ] );

		$this->assertTrue( $checked->is_valid() );
		$this->assertSame( [ $this->ticket_id ], $checked->get_delete() );
	}

	/**
	 * @test
	 */
	public function a_ticket_on_another_post_is_rejected_per_entry_and_siblings_survive(): void {
		$this->given_two_posts_with_tickets();
		$this->log_in_as( 'editor' );
		$data = [ 'ticket_name' => 'x' ];

		$checked = $this->run_checks(
			[
				'update' => [ $this->ticket_id => $data, $this->foreign_ticket_id => $data ],
				'delete' => [ $this->second_foreign_ticket_id, $this->second_ticket_id ],
				'move'   => [ $this->foreign_ticket_id => $this->other_post_id, $this->ticket_id => $this->other_post_id ],
			]
		);

		$this->assertTrue( $checked->is_valid() );
		$this->assertSame( [ $this->ticket_id => $data + [ 'ticket_id' => $this->ticket_id ] ], $checked->get_update() );
		$this->assertSame( [ $this->second_ticket_id ], $checked->get_delete() );
		$this->assertSame( [ $this->ticket_id => $this->other_post_id ], $checked->get_move() );
		$this->assertSame( [ $this->foreign_ticket_id ], $this->error_keys( $checked, 'update' ) );
		$this->assertSame( [ $this->second_foreign_ticket_id ], $this->error_keys( $checked, 'delete' ) );
		$this->assertSame( [ $this->foreign_ticket_id ], $this->error_keys( $checked, 'move' ) );
		$this->assertNotEmpty( $checked->get_errors()[0]['message'] );
	}

	/**
	 * @test
	 */
	public function ids_that_are_not_tickets_are_rejected(): void {
		$this->given_two_posts_with_tickets();
		$this->log_in_as( 'editor' );
		$attendee_id = $this->create_attendee_for_ticket( $this->ticket_id, $this->post_id );
		$data        = [ 'ticket_name' => 'x' ];

		$checked = $this->run_checks(
			[
				'update' => [
					$this->post_id       => $data,
					$this->other_post_id => $data,
					$attendee_id         => $data,
					999999999            => $data,
					$this->ticket_id     => $data,
				],
			]
		);

		$this->assertSame( [ $this->ticket_id => $data + [ 'ticket_id' => $this->ticket_id ] ], $checked->get_update() );
		$this->assertEqualSets(
			[ $this->post_id, $this->other_post_id, $attendee_id, 999999999 ],
			$this->error_keys( $checked, 'update' )
		);
	}

	/**
	 * @test
	 */
	public function an_rsvp_attendee_on_the_post_is_not_a_ticket(): void {
		$this->given_two_posts_with_tickets();
		$this->log_in_as( 'editor' );
		$rsvp_ticket_id   = $this->create_rsvp_ticket( $this->post_id );
		$rsvp_attendee_id = $this->create_attendee_for_ticket( $rsvp_ticket_id, $this->post_id );
		$data             = [ 'ticket_name' => 'x' ];

		$checked = $this->run_checks(
			[
				'update' => [ $rsvp_attendee_id => $data, $rsvp_ticket_id => $data ],
				'delete' => [ $this->post_id ],
				'move'   => [ $rsvp_attendee_id => $this->other_post_id ],
			]
		);

		$this->assertSame( [ $rsvp_ticket_id => $data + [ 'ticket_id' => $rsvp_ticket_id ] ], $checked->get_update() );
		$this->assertSame( [], $checked->get_delete() );
		$this->assertSame( [], $checked->get_move() );
		$this->assertSame( [ $rsvp_attendee_id ], $this->error_keys( $checked, 'update' ) );
		$this->assertSame( [ $this->post_id ], $this->error_keys( $checked, 'delete' ) );
		$this->assertSame( [ $rsvp_attendee_id ], $this->error_keys( $checked, 'move' ) );
	}

	/**
	 * @test
	 */
	public function a_move_destination_the_user_cannot_edit_or_that_does_not_exist_is_rejected(): void {
		$this->given_two_posts_with_tickets();
		$author_id = $this->log_in_as( 'author' );
		wp_update_post( [ 'ID' => $this->post_id, 'post_author' => $author_id ] );
		$own_other_post_id = static::factory()->post->create( [ 'post_author' => $author_id ] );

		$checked = $this->run_checks(
			[
				'move' => [
					$this->ticket_id        => $this->other_post_id,
					$this->second_ticket_id => $own_other_post_id,
				],
			]
		);

		$this->assertSame( [ $this->second_ticket_id => $own_other_post_id ], $checked->get_move() );
		$this->assertSame( [ $this->ticket_id ], $this->error_keys( $checked, 'move' ) );

		$checked = $this->run_checks( [ 'move' => [ $this->ticket_id => 999999999 ] ] );

		$this->assertSame( [], $checked->get_move() );
		$this->assertSame( [ $this->ticket_id ], $this->error_keys( $checked, 'move' ) );
	}

	/**
	 * @test
	 */
	public function the_delete_filter_can_deny_one_ticket_and_leave_the_rest(): void {
		$this->given_two_posts_with_tickets();
		$this->log_in_as( 'editor' );
		$denied = $this->ticket_id;
		$seen   = [];

		add_filter(
			'tribe_tickets_current_user_can_delete_ticket',
			static function ( bool $can, int $ticket_id, string $provider_class ) use ( $denied, &$seen ): bool {
				$seen[] = [ $ticket_id, $provider_class ];

				return $ticket_id === $denied ? false : $can;
			},
			10,
			3
		);

		$checked = $this->run_checks( [ 'delete' => [ $this->ticket_id, $this->second_ticket_id ] ] );

		$this->assertSame( [ $this->second_ticket_id ], $checked->get_delete() );
		$this->assertSame( [ $this->ticket_id ], $this->error_keys( $checked, 'delete' ) );
		$this->assertSame(
			[
				[ $this->ticket_id, \TEC\Tickets\Commerce\Module::class ],
				[ $this->second_ticket_id, \TEC\Tickets\Commerce\Module::class ],
			],
			$seen
		);
	}

	/**
	 * @test
	 */
	public function the_delete_filter_is_not_asked_about_update_or_move_entries(): void {
		$this->given_two_posts_with_tickets();
		$this->log_in_as( 'editor' );
		add_filter( 'tribe_tickets_current_user_can_delete_ticket', '__return_false' );

		$checked = $this->run_checks(
			[
				'update' => [ $this->ticket_id => [ 'ticket_name' => 'x' ] ],
				'move'   => [ $this->second_ticket_id => $this->other_post_id ],
			]
		);

		$this->assertSame( [], $checked->get_errors() );
	}

	/**
	 * @test
	 */
	public function an_already_rejected_payload_comes_back_unchanged(): void {
		$this->given_two_posts_with_tickets();
		$this->log_in_as( 'editor' );
		$payload = Payload::from_array( 'not an array' );

		$checked = tribe( Checks::class )->run( $payload, $this->post_id );

		$this->assertFalse( $checked->is_valid() );
		$this->assertSame( $payload->get_errors(), $checked->get_errors() );
	}

	/**
	 * @test
	 */
	public function an_empty_payload_passes_without_touching_the_user(): void {
		$this->given_two_posts_with_tickets();
		wp_set_current_user( 0 );

		$checked = $this->run_checks( [] );

		$this->assertTrue( $checked->is_valid() );
		$this->assertFalse( $checked->has_changes() );
		$this->assertSame( [], $checked->get_errors() );
	}
}
