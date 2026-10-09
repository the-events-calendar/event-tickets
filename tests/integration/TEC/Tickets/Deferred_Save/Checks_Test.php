<?php

namespace TEC\Tickets\Deferred_Save;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Deferred_Save\Payload\Parser;
use TEC\Tickets\Deferred_Save\Payload\Rejections;
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
	private Rejections $rejections;

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

	protected function error_keys( string $part ): array {
		return array_values(
			array_map(
				static fn( array $error ) => $error['key'],
				array_filter( $this->rejections->all(), static fn( array $error ) => $error['part'] === $part )
			)
		);
	}

	protected function payload_level_rejections(): array {
		return array_values( array_filter( $this->rejections->all(), static fn( array $error ) => null === $error['part'] ) );
	}

	protected function run_checks( $raw, ?int $post_id = null ): Payload {
		$parsed  = ( new Parser() )->parse( $raw );
		$checked = tribe( Checks::class )->run( $parsed->payload(), $post_id ?? $this->post_id );

		$this->rejections = $parsed->rejections()->merge( $checked->rejections() );

		return $checked->payload();
	}

	/**
	 * @test
	 */
	public function it_should_reject_the_whole_payload_for_a_user_who_cannot_edit_the_post(): void {
		$this->given_two_posts_with_tickets();
		$this->log_in_as( 'subscriber' );
		$data = [ 'ticket_name' => 'x' ];

		$checked = $this->run_checks(
			[
				'update' => [ $this->ticket_id => $data ],
				'create' => [ $data ],
				'delete' => [ $this->second_ticket_id ],
				'move'   => [ $this->foreign_ticket_id => $this->other_post_id ],
			]
		);

		$this->assertCount( 1, $this->payload_level_rejections() );
		$this->assertFalse( $checked->has_changes() );
		$this->assertCount( 1, $this->rejections->all() );
		$this->assertNull( $this->rejections->all()[0]['part'] );
		$this->assertNull( $this->rejections->all()[0]['key'] );
	}

	/**
	 * @test
	 */
	public function it_should_reject_the_whole_payload_for_a_logged_out_user(): void {
		$this->given_two_posts_with_tickets();
		wp_set_current_user( 0 );

		$checked = $this->run_checks( [ 'delete' => [ $this->ticket_id ] ] );

		$this->assertCount( 1, $this->payload_level_rejections() );
		$this->assertSame( [], $checked->get_delete() );
	}

	/**
	 * @test
	 */
	public function it_should_pass_an_editor_at_post_level_and_keep_the_owned_entries(): void {
		$this->given_two_posts_with_tickets();
		$this->log_in_as( 'editor' );
		$data            = [ 'ticket_name' => 'x', 'custom_field' => 'rides along' ];
		$third_ticket_id = $this->create_tc_ticket( $this->post_id, 50 );

		$checked = $this->run_checks(
			[
				'update' => [ $this->ticket_id => $data ],
				'create' => [ $data, $data ],
				'delete' => [ $this->second_ticket_id ],
				'move'   => [ $third_ticket_id => $this->other_post_id ],
			]
		);

		$this->assertSame( [], $this->payload_level_rejections() );
		$this->assertSame( [], $this->rejections->all() );
		$this->assertSame( [ $this->ticket_id => $data + [ 'ticket_id' => $this->ticket_id ] ], $checked->get_update() );
		$this->assertSame( [ $data, $data ], $checked->get_create() );
		$this->assertSame( [ $this->second_ticket_id ], $checked->get_delete() );
		$this->assertSame( [ $third_ticket_id => $this->other_post_id ], $checked->get_move() );
	}

	/**
	 * @test
	 */
	public function it_should_let_the_author_of_the_post_edit_it_and_delete_only_the_tickets_they_created(): void {
		$this->given_two_posts_with_tickets();
		$author_id = $this->log_in_as( 'author' );
		wp_update_post( [ 'ID' => $this->post_id, 'post_author' => $author_id ] );
		$own_ticket_id = $this->create_tc_ticket( $this->post_id, 50 );

		$checked = $this->run_checks(
			[
				'update' => [ $this->ticket_id => [ 'ticket_name' => 'x' ] ],
				'delete' => [ $this->second_ticket_id, $own_ticket_id ],
			]
		);

		$this->assertSame( [], $this->payload_level_rejections() );
		$this->assertSame( [ $this->ticket_id ], array_keys( $checked->get_update() ) );
		$this->assertSame( [ $own_ticket_id ], $checked->get_delete() );
		$this->assertSame( [ $this->second_ticket_id ], $this->error_keys( 'delete' ), 'The ticket was not created by the author.' );
	}

	/**
	 * Deliberate: tickets are published posts, so deleting one asks for `delete_published_posts`,
	 * which a Contributor lacks even for the tickets they created on their own draft.
	 *
	 * @test
	 */
	public function it_should_let_a_contributor_create_tickets_on_their_draft_but_not_delete_them(): void {
		$contributor_id = $this->log_in_as( 'contributor' );
		$this->post_id  = static::factory()->post->create( [ 'post_author' => $contributor_id, 'post_status' => 'draft' ] );
		$own_ticket_id  = $this->create_tc_ticket( $this->post_id, 10 );

		$checked = $this->run_checks(
			[
				'create' => [ [ 'ticket_name' => 'x' ] ],
				'delete' => [ $own_ticket_id ],
			]
		);

		$this->assertSame( [], $this->payload_level_rejections() );
		$this->assertCount( 1, $checked->get_create() );
		$this->assertSame( [], $checked->get_delete() );
		$this->assertSame( [ $own_ticket_id ], $this->error_keys( 'delete' ) );
	}

	/**
	 * The editors put back the block of a refused delete or move, unless the ticket is no longer on the post.
	 *
	 * @test
	 */
	public function it_should_mark_the_rejections_of_tickets_that_are_not_on_the_post(): void {
		$this->given_two_posts_with_tickets();
		$this->log_in_as( 'editor' );
		// A refusal that is not about where the ticket is.
		add_filter( 'tec_tickets_user_can_delete_ticket', '__return_false' );

		$this->run_checks(
			[
				'delete' => [ $this->foreign_ticket_id, $this->ticket_id ],
				'move'   => [ $this->second_foreign_ticket_id => $this->other_post_id ],
			]
		);

		$not_on_post = [];
		foreach ( $this->rejections->all() as $rejection ) {
			$not_on_post[ $rejection['key'] ] = ! empty( $rejection['not_on_post'] );
		}
		$this->assertSame(
			[
				$this->second_foreign_ticket_id => true,
				$this->foreign_ticket_id        => true,
				$this->ticket_id                => false,
			],
			$not_on_post
		);
	}

	/**
	 * @test
	 */
	public function it_should_reject_a_ticket_of_another_post_per_entry_and_keep_its_siblings(): void {
		$this->given_two_posts_with_tickets();
		$this->log_in_as( 'editor' );
		$data                    = [ 'ticket_name' => 'x' ];
		$third_ticket_id         = $this->create_tc_ticket( $this->post_id, 50 );
		$third_foreign_ticket_id = $this->create_tc_ticket( $this->other_post_id, 60 );

		$checked = $this->run_checks(
			[
				'update' => [ $this->ticket_id => $data, $this->foreign_ticket_id => $data ],
				'delete' => [ $this->second_foreign_ticket_id, $this->second_ticket_id ],
				'move'   => [ $third_foreign_ticket_id => $this->other_post_id, $third_ticket_id => $this->other_post_id ],
			]
		);

		$this->assertSame( [], $this->payload_level_rejections() );
		$this->assertSame( [ $this->ticket_id => $data + [ 'ticket_id' => $this->ticket_id ] ], $checked->get_update() );
		$this->assertSame( [ $this->second_ticket_id ], $checked->get_delete() );
		$this->assertSame( [ $third_ticket_id => $this->other_post_id ], $checked->get_move() );
		$this->assertSame( [ $this->foreign_ticket_id ], $this->error_keys( 'update' ) );
		$this->assertSame( [ $this->second_foreign_ticket_id ], $this->error_keys( 'delete' ) );
		$this->assertSame( [ $third_foreign_ticket_id ], $this->error_keys( 'move' ) );
		$this->assertNotEmpty( $this->rejections->all()[0]['message'] );
	}

	/**
	 * @test
	 */
	public function it_should_reject_ids_that_are_not_tickets(): void {
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
			$this->error_keys( 'update' )
		);
	}

	/**
	 * @test
	 */
	public function it_should_not_treat_an_rsvp_attendee_on_the_post_as_a_ticket(): void {
		$this->given_two_posts_with_tickets();
		$this->log_in_as( 'editor' );
		$rsvp_ticket_id    = $this->create_rsvp_ticket( $this->post_id );
		$rsvp_attendee_id  = $this->create_attendee_for_ticket( $rsvp_ticket_id, $this->post_id );
		$moved_attendee_id = $this->create_attendee_for_ticket( $rsvp_ticket_id, $this->post_id );
		$data              = [ 'ticket_name' => 'x' ];

		$checked = $this->run_checks(
			[
				'update' => [ $rsvp_attendee_id => $data, $rsvp_ticket_id => $data ],
				'delete' => [ $this->post_id ],
				'move'   => [ $moved_attendee_id => $this->other_post_id ],
			]
		);

		$this->assertSame( [ $rsvp_ticket_id => $data + [ 'ticket_id' => $rsvp_ticket_id ] ], $checked->get_update() );
		$this->assertSame( [], $checked->get_delete() );
		$this->assertSame( [], $checked->get_move() );
		$this->assertSame( [ $rsvp_attendee_id ], $this->error_keys( 'update' ) );
		$this->assertSame( [ $this->post_id ], $this->error_keys( 'delete' ) );
		$this->assertSame( [ $moved_attendee_id ], $this->error_keys( 'move' ) );
	}

	/**
	 * @test
	 */
	public function it_should_reject_a_move_destination_the_user_cannot_edit_or_that_does_not_exist(): void {
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
		$this->assertSame( [ $this->ticket_id ], $this->error_keys( 'move' ) );

		$checked = $this->run_checks( [ 'move' => [ $this->ticket_id => 999999999 ] ] );

		$this->assertSame( [], $checked->get_move() );
		$this->assertSame( [ $this->ticket_id ], $this->error_keys( 'move' ) );
	}

	/**
	 * @test
	 */
	public function it_should_let_the_delete_filter_deny_one_ticket_and_leave_the_rest(): void {
		$this->given_two_posts_with_tickets();
		$this->log_in_as( 'editor' );
		$denied = $this->ticket_id;
		$seen   = [];

		add_filter(
			'tec_tickets_user_can_delete_ticket',
			static function ( bool $can, \Tribe__Tickets__Ticket_Object $ticket ) use ( $denied, &$seen ): bool {
				$seen[] = $ticket->ID;

				return $ticket->ID === $denied ? false : $can;
			},
			10,
			2
		);
		add_filter( 'tribe_tickets_current_user_can_delete_ticket', '__return_false' );

		$checked = $this->run_checks( [ 'delete' => [ $this->ticket_id, $this->second_ticket_id ] ] );

		$this->assertSame( [ $this->second_ticket_id ], $checked->get_delete(), 'The legacy delete filter is not asked.' );
		$this->assertSame( [ $this->ticket_id ], $this->error_keys( 'delete' ) );
		$this->assertSame( [ $this->ticket_id, $this->second_ticket_id ], $seen );
	}

	/**
	 * @test
	 */
	public function it_should_not_ask_the_delete_filter_about_update_or_move_entries(): void {
		$this->given_two_posts_with_tickets();
		$this->log_in_as( 'editor' );
		add_filter( 'tec_tickets_user_can_delete_ticket', '__return_false' );

		$checked = $this->run_checks(
			[
				'update' => [ $this->ticket_id => [ 'ticket_name' => 'x' ] ],
				'move'   => [ $this->second_ticket_id => $this->other_post_id ],
			]
		);

		$this->assertSame( [], $this->rejections->all() );
	}

	/**
	 * @test
	 */
	public function it_should_ask_the_permissions_resolved_for_each_ticket(): void {
		$this->given_two_posts_with_tickets();
		$this->log_in_as( 'editor' );
		$data     = [ 'ticket_name' => 'x' ];
		$stricter = new class() extends \TEC\Tickets\Ticket_Permissions {
			protected function can_edit_ticket( \Tribe__Tickets__Ticket_Object $ticket, int $user_id ): bool {
				return false;
			}
		};
		$third_ticket_id = $this->create_tc_ticket( $this->post_id, 50 );
		$denied          = [ $this->ticket_id, $this->second_ticket_id, $third_ticket_id ];
		add_filter(
			'tec_tickets_ticket_permissions',
			static function ( $default, \Tribe__Tickets__Ticket_Object $ticket ) use ( $stricter, $denied ) {
				return in_array( $ticket->ID, $denied, true ) ? $stricter : $default;
			},
			10,
			2
		);

		// A ticket is updated, moved or deleted, never two of them, so each part names its own ticket.
		$checked = $this->run_checks(
			[
				'update' => [ $this->ticket_id => $data ],
				'delete' => [ $this->second_ticket_id ],
				'move'   => [ $third_ticket_id => $this->other_post_id ],
			]
		);

		$this->assertSame( [], $this->payload_level_rejections(), 'The post-level check still passes for an editor.' );
		$this->assertFalse( $checked->has_changes() );
		$this->assertSame( [ $this->ticket_id ], $this->error_keys( 'update' ) );
		$this->assertSame( [ $this->second_ticket_id ], $this->error_keys( 'delete' ), 'Delete builds on the edit answer.' );
		$this->assertSame( [ $third_ticket_id ], $this->error_keys( 'move' ) );
	}

	/**
	 * @test
	 */
	public function it_should_pass_an_empty_payload_without_touching_the_user(): void {
		$this->given_two_posts_with_tickets();
		wp_set_current_user( 0 );

		$checked = $this->run_checks( [] );

		$this->assertSame( [], $this->payload_level_rejections() );
		$this->assertFalse( $checked->has_changes() );
		$this->assertSame( [], $this->rejections->all() );
	}

	/**
	 * ECP normalises an occurrence's provisional ID to its event through this filter. ET's pull requests
	 * do not run the ft_integration suite, so the test answers the filter the way ECP does.
	 *
	 * @test
	 */
	public function it_should_normalise_the_post_and_the_move_destination_through_the_event_id_filter(): void {
		$this->given_two_posts_with_tickets();
		$this->log_in_as( 'editor' );
		$provisional = [
			$this->post_id + 10000000       => $this->post_id,
			$this->other_post_id + 10000000 => $this->other_post_id,
		];
		add_filter(
			'tec_tickets_filter_event_id',
			static fn( $id ) => $provisional[ (int) $id ] ?? $id
		);

		$checked = $this->run_checks(
			[
				'delete' => [ $this->second_ticket_id ],
				'move'   => [ $this->ticket_id => $this->other_post_id + 10000000 ],
			],
			$this->post_id + 10000000
		);

		$this->assertSame( [], $this->rejections->all() );
		$this->assertSame( [ $this->second_ticket_id ], $checked->get_delete() );
		$this->assertSame( [ $this->ticket_id => $this->other_post_id ], $checked->get_move(), 'The destination is passed on as it was checked.' );
	}
}
