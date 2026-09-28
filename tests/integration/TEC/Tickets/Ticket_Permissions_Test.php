<?php

namespace TEC\Tickets;

use Codeception\TestCase\WPTestCase;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

class Ticket_Permissions_Test extends WPTestCase {
	use Ticket_Maker;
	use With_Tickets_Commerce;

	public function tearDown(): void {
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	protected function log_in_as( string $role, array $args = [] ): int {
		$user_id = static::factory()->user->create( array_merge( [ 'role' => $role ], $args ) );
		wp_set_current_user( $user_id );

		return $user_id;
	}

	protected function ticket_on_a_post( int $author_id = 0 ): Ticket_Object {
		$post_id   = static::factory()->post->create( $author_id ? [ 'post_author' => $author_id ] : [] );
		$ticket_id = $this->create_tc_ticket( $post_id, 10 );

		return tribe( Ticket_Data::class )->load_ticket_object( $ticket_id );
	}

	/**
	 * @test
	 */
	public function it_should_let_whoever_can_edit_the_post_edit_and_delete_its_tickets(): void {
		$permissions = tribe( Ticket_Permissions::class );
		$ticket      = $this->ticket_on_a_post();

		$this->log_in_as( 'administrator' );
		$this->assertTrue( $permissions->current_user_can_edit_tickets_of( (int) $ticket->get_event_id() ) );
		$this->assertTrue( $permissions->current_user_can_edit_ticket( $ticket ) );
		$this->assertTrue( $permissions->current_user_can_delete_ticket( $ticket ) );

		$this->log_in_as( 'subscriber' );
		$this->assertFalse( $permissions->current_user_can_edit_tickets_of( (int) $ticket->get_event_id() ) );
		$this->assertFalse( $permissions->current_user_can_edit_ticket( $ticket ) );
		$this->assertFalse( $permissions->current_user_can_delete_ticket( $ticket ) );

		wp_set_current_user( 0 );
		$this->assertFalse( $permissions->current_user_can_edit_ticket( $ticket ) );
	}

	/**
	 * @test
	 */
	public function it_should_let_the_author_of_the_post_edit_its_tickets(): void {
		$author_id   = static::factory()->user->create( [ 'role' => 'author' ] );
		$ticket      = $this->ticket_on_a_post( $author_id );
		$permissions = tribe( Ticket_Permissions::class );

		wp_set_current_user( $author_id );
		$this->assertTrue( $permissions->current_user_can_edit_ticket( $ticket ) );

		$this->log_in_as( 'author' );
		$this->assertFalse( $permissions->current_user_can_edit_ticket( $ticket ), 'Another author cannot edit someone else\'s post.' );
	}

	/**
	 * @test
	 */
	public function it_should_let_the_legacy_delete_filter_refuse_a_delete_but_not_an_edit(): void {
		$ticket      = $this->ticket_on_a_post();
		$permissions = tribe( Ticket_Permissions::class );
		$this->log_in_as( 'administrator' );
		$seen = [];
		add_filter(
			'tribe_tickets_current_user_can_delete_ticket',
			static function ( bool $can, int $ticket_id, string $provider_class ) use ( &$seen ): bool {
				$seen[] = [ $ticket_id, $provider_class ];

				return false;
			},
			10,
			3
		);

		$this->assertTrue( $permissions->current_user_can_edit_ticket( $ticket ) );
		$this->assertFalse( $permissions->current_user_can_delete_ticket( $ticket ) );
		$this->assertSame( [ [ $ticket->ID, $ticket->provider_class ] ], $seen );
	}

	/**
	 * @test
	 */
	public function it_should_answer_for_a_ticket_through_the_permissions_resolved_for_it(): void {
		$ticket      = $this->ticket_on_a_post();
		$permissions = tribe( Ticket_Permissions::class );
		$this->log_in_as( 'administrator' );

		$this->assertSame( $permissions, $permissions->for_ticket( $ticket ), 'By default the base permissions answer.' );

		$stricter = new class() extends Ticket_Permissions {
			public function current_user_can_edit_ticket( Ticket_Object $ticket ): bool {
				return false;
			}
		};
		add_filter(
			'tec_tickets_ticket_permissions',
			static function ( Ticket_Permissions $default, Ticket_Object $for ) use ( $stricter, $ticket ) {
				return $for->ID === $ticket->ID ? $stricter : $default;
			},
			10,
			2
		);

		$this->assertSame( $stricter, $permissions->for_ticket( $ticket ) );
		$this->assertFalse( $permissions->for_ticket( $ticket )->current_user_can_edit_ticket( $ticket ) );
		$this->assertFalse( $permissions->for_ticket( $ticket )->current_user_can_delete_ticket( $ticket ), 'Delete builds on edit.' );
		$this->assertTrue( $permissions->current_user_can_edit_ticket( $ticket ), 'The base answer is unchanged.' );
	}

	/**
	 * @test
	 */
	public function it_should_ignore_a_filter_that_returns_something_else(): void {
		$ticket      = $this->ticket_on_a_post();
		$permissions = tribe( Ticket_Permissions::class );
		add_filter( 'tec_tickets_ticket_permissions', '__return_null' );

		$this->assertSame( $permissions, $permissions->for_ticket( $ticket ) );
	}
}
