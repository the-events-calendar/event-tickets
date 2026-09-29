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
		$this->assertTrue( $permissions->user_can_edit_tickets_of( (int) $ticket->get_event_id() ) );
		$this->assertTrue( $permissions->user_can_edit_ticket( $ticket ) );
		$this->assertTrue( $permissions->user_can_delete_ticket( $ticket ) );

		$this->log_in_as( 'subscriber' );
		$this->assertFalse( $permissions->user_can_edit_tickets_of( (int) $ticket->get_event_id() ) );
		$this->assertFalse( $permissions->user_can_edit_ticket( $ticket ) );
		$this->assertFalse( $permissions->user_can_delete_ticket( $ticket ) );

		wp_set_current_user( 0 );
		$this->assertFalse( $permissions->user_can_edit_ticket( $ticket ) );
	}

	/**
	 * @test
	 */
	public function it_should_let_the_author_of_the_post_edit_its_tickets(): void {
		$author_id   = static::factory()->user->create( [ 'role' => 'author' ] );
		$ticket      = $this->ticket_on_a_post( $author_id );
		$permissions = tribe( Ticket_Permissions::class );

		wp_set_current_user( $author_id );
		$this->assertTrue( $permissions->user_can_edit_ticket( $ticket ) );

		$this->log_in_as( 'author' );
		$this->assertFalse( $permissions->user_can_edit_ticket( $ticket ), 'Another author cannot edit someone else\'s post.' );
	}

	/**
	 * @test
	 */
	public function it_should_not_ask_the_legacy_delete_filter(): void {
		$ticket = $this->ticket_on_a_post();
		$this->log_in_as( 'administrator' );
		add_filter( 'tribe_tickets_current_user_can_delete_ticket', '__return_false' );

		$this->assertTrue( tribe( Ticket_Permissions::class )->user_can_delete_ticket( $ticket ) );
	}

	/**
	 * @test
	 */
	public function it_should_require_delete_post_on_the_ticket_to_delete_it(): void {
		$author_id   = static::factory()->user->create( [ 'role' => 'author' ] );
		$post_id     = static::factory()->post->create( [ 'post_author' => $author_id ] );
		$permissions = tribe( Ticket_Permissions::class );

		$this->log_in_as( 'administrator' );
		$admins_ticket = tribe( Ticket_Data::class )->load_ticket_object( $this->create_tc_ticket( $post_id, 10 ) );
		wp_set_current_user( $author_id );
		$authors_ticket = tribe( Ticket_Data::class )->load_ticket_object( $this->create_tc_ticket( $post_id, 20 ) );

		$this->assertTrue( $permissions->user_can_edit_ticket( $admins_ticket ) );
		$this->assertFalse( current_user_can( 'delete_post', $admins_ticket->ID ) );
		$this->assertFalse( $permissions->user_can_delete_ticket( $admins_ticket ), 'An author cannot delete a ticket someone else created.' );
		$this->assertTrue( $permissions->user_can_delete_ticket( $authors_ticket ), 'An author can delete a ticket they created.' );
	}

	/**
	 * @test
	 */
	public function it_should_refuse_a_post_id_that_resolves_to_nothing(): void {
		$ticket      = $this->ticket_on_a_post();
		$permissions = tribe( Ticket_Permissions::class );
		$this->log_in_as( 'administrator' );
		$GLOBALS['post'] = get_post( (int) $ticket->get_event_id() );

		$this->assertFalse( $permissions->user_can_edit_tickets_of( 0 ), 'The global post does not answer for post 0.' );

		add_filter( 'tec_tickets_filter_event_id', '__return_null' );
		$this->assertFalse( $permissions->user_can_edit_tickets_of( (int) $ticket->get_event_id() ) );
		$this->assertFalse( $permissions->user_can_edit_ticket( $ticket ) );
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
			protected function can_edit_ticket( Ticket_Object $ticket, int $user_id ): bool {
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
		$this->assertFalse( $permissions->for_ticket( $ticket )->user_can_edit_ticket( $ticket ) );
		$this->assertFalse( $permissions->for_ticket( $ticket )->user_can_delete_ticket( $ticket ), 'Delete builds on edit.' );
		$this->assertTrue( $permissions->user_can_edit_ticket( $ticket ), 'The base answer is unchanged.' );
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

	/**
	 * @test
	 */
	public function it_should_answer_for_the_given_user_rather_than_the_current_one(): void {
		$ticket      = $this->ticket_on_a_post();
		$permissions = tribe( Ticket_Permissions::class );
		$admin_id    = static::factory()->user->create( [ 'role' => 'administrator' ] );
		$post_id     = (int) $ticket->get_event_id();

		$subscriber_id = $this->log_in_as( 'subscriber' );
		$this->assertTrue( $permissions->user_can_edit_tickets_of( $post_id, $admin_id ) );
		$this->assertTrue( $permissions->user_can_edit_ticket( $ticket, $admin_id ) );
		$this->assertTrue( $permissions->user_can_delete_ticket( $ticket, $admin_id ) );

		wp_set_current_user( $admin_id );
		$this->assertFalse( $permissions->user_can_edit_tickets_of( $post_id, $subscriber_id ) );
		$this->assertFalse( $permissions->user_can_edit_ticket( $ticket, $subscriber_id ) );
		$this->assertFalse( $permissions->user_can_delete_ticket( $ticket, $subscriber_id ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_accept_edit_event_tickets_without_edit_post(): void {
		$ticket  = $this->ticket_on_a_post();
		$this->log_in_as( 'subscriber' );
		wp_get_current_user()->add_cap( 'edit_event_tickets' );

		$this->assertTrue( current_user_can( 'edit_event_tickets' ) ); // phpcs:ignore WordPress.WP.Capabilities.Unknown
		$this->assertFalse( tribe( Ticket_Permissions::class )->user_can_edit_ticket( $ticket ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_accept_edit_others_posts_without_edit_post(): void {
		$author_id = static::factory()->user->create( [ 'role' => 'author' ] );
		$ticket    = $this->ticket_on_a_post( $author_id );
		add_role( 'others_drafts_editor', 'Others drafts editor', [ 'read' => true, 'edit_posts' => true, 'edit_others_posts' => true ] );
		$this->log_in_as( 'others_drafts_editor' );

		$this->assertTrue( current_user_can( 'edit_others_posts' ) );
		$this->assertFalse( current_user_can( 'edit_post', (int) $ticket->get_event_id() ), 'The post is published; the role cannot edit published posts.' );
		$this->assertFalse( tribe( Ticket_Permissions::class )->user_can_edit_ticket( $ticket ) );
	}

	/**
	 * @test
	 */
	public function it_should_let_a_filter_refuse_each_answer(): void {
		$ticket      = $this->ticket_on_a_post();
		$permissions = tribe( Ticket_Permissions::class );
		$admin_id    = $this->log_in_as( 'administrator' );
		$post_id     = (int) $ticket->get_event_id();
		$seen        = [];
		$refuse      = static function ( string $filter ) use ( &$seen ) {
			return static function ( bool $can, $subject, int $user_id ) use ( $filter, &$seen ): bool {
				$seen[ $filter ] = [ $can, $subject, $user_id ];

				return false;
			};
		};

		add_filter( 'tec_tickets_user_can_delete_ticket', $refuse( 'delete' ), 10, 3 );
		$this->assertTrue( $permissions->user_can_edit_ticket( $ticket ) );
		$this->assertFalse( $permissions->user_can_delete_ticket( $ticket ) );
		$this->assertSame( [ true, $ticket, $admin_id ], $seen['delete'] );

		add_filter( 'tec_tickets_user_can_edit_ticket', $refuse( 'edit' ), 10, 3 );
		$this->assertTrue( $permissions->user_can_edit_tickets_of( $post_id ) );
		$this->assertFalse( $permissions->user_can_edit_ticket( $ticket ) );
		$this->assertSame( [ true, $ticket, $admin_id ], $seen['edit'] );

		add_filter( 'tec_tickets_user_can_edit_tickets_of', $refuse( 'post' ), 10, 3 );
		$this->assertFalse( $permissions->user_can_edit_tickets_of( $post_id ) );
		$this->assertSame( [ true, $post_id, $admin_id ], $seen['post'] );
	}

	/**
	 * @test
	 */
	public function it_should_refuse_the_delete_when_the_edit_filter_refuses(): void {
		$ticket = $this->ticket_on_a_post();
		$this->log_in_as( 'administrator' );
		$asked = false;
		add_filter( 'tec_tickets_user_can_edit_ticket', '__return_false' );
		add_filter(
			'tec_tickets_user_can_delete_ticket',
			static function ( bool $can ) use ( &$asked ): bool {
				$asked = true;

				return $can;
			}
		);

		$this->assertFalse( tribe( Ticket_Permissions::class )->user_can_delete_ticket( $ticket ) );
		$this->assertFalse( $asked, 'The delete filter is not asked once editing is refused.' );
	}

	/**
	 * @test
	 */
	public function it_should_let_the_edit_filter_change_a_providers_answer(): void {
		$ticket   = $this->ticket_on_a_post();
		$stricter = new class() extends Ticket_Permissions {
			protected function can_edit_ticket( Ticket_Object $ticket, int $user_id ): bool {
				return false;
			}
		};
		$this->log_in_as( 'administrator' );
		$seen = null;
		add_filter(
			'tec_tickets_user_can_edit_ticket',
			static function ( bool $can ) use ( &$seen ): bool {
				$seen = $can;

				return true;
			}
		);

		$this->assertTrue( $stricter->user_can_edit_ticket( $ticket ) );
		$this->assertFalse( $seen, 'The filter sees the provider\'s refusal.' );
	}
}
