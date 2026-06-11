<?php

namespace TEC\Tickets;

use TEC\Common\Tests\Provider\Controller_Test_Case;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use Tribe\Tests\Traits\With_Uopz;
use Generator;
use Closure;

class Ticket_Able_Post_Actions_Test extends Controller_Test_Case {
	use With_Uopz;
	use Ticket_Maker;
	use Order_Maker;
	use With_Tickets_Commerce;

	protected string $controller_class = Ticket_Able_Post_Actions::class;

	protected static array $back_up_actions = [];

	/**
	 * @before
	 */
	public function take_backup(): void {
		global $wp_actions;

		self::$back_up_actions = $wp_actions;
		$wp_actions = [];
	}

	/**
	 * @after
	 */
	public function restore_backup(): void {
		global $wp_actions;

		$wp_actions = self::$back_up_actions;
	}

	/**
	 * @test
	 * @dataProvider fire_ticket_able_post_change_action_data_provider
	 */
	public function it_should_fire_ticket_able_post_change_action( Closure $fixture ): void {
		$controller = $this->make_controller();
		$controller->register();

		[ $expected_actions, $unexpected_actions ] = $fixture();

		foreach ( $expected_actions as $action => $count ) {
			$this->assertSame(
				$count,
				did_action( $action ),
				"Expected action '{$action}' to fire {$count} time(s), got " . did_action( $action ) . '.'
			);
		}

		foreach ( $unexpected_actions as $action ) {
			$this->assertSame(
				0,
				did_action( $action ),
				"Expected action '{$action}' to NOT fire."
			);
		}
	}

	public function fire_ticket_able_post_change_action_data_provider(): Generator {
		yield 'creating a ticket-enabled post fires upserted and created actions' => [
			function (): array {
				static::factory()->post->create( [ 'post_status' => 'publish' ] );

				return [
					[
						'tec_tickets_ticket_able_post_upserted' => 1,
						'tec_tickets_ticket_able_post_created'  => 1,
					],
					[
						'tec_tickets_ticket_able_post_updated',
					],
				];
			},
		];

		yield 'updating a ticket-enabled post fires upserted and updated actions' => [
			function (): array {
				$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

				// Reset counts after creation.
				global $wp_actions;
				$wp_actions = [];

				wp_update_post( [
					'ID'         => $post_id,
					'post_title' => 'Updated title',
				] );

				return [
					[
						'tec_tickets_ticket_able_post_upserted' => 1,
						'tec_tickets_ticket_able_post_updated'  => 1,
					],
					[
						'tec_tickets_ticket_able_post_created',
					],
				];
			},
		];

		yield 'non-ticket-enabled post type does not fire actions' => [
			function (): array {
				register_post_type( 'not_ticketed', [ 'public' => true ] );
				static::factory()->post->create( [
					'post_status' => 'publish',
					'post_type'   => 'not_ticketed',
				] );

				return [
					[],
					[
						'tec_tickets_ticket_able_post_upserted',
						'tec_tickets_ticket_able_post_created',
						'tec_tickets_ticket_able_post_updated',
					],
				];
			},
		];

		yield 'revision does not fire actions' => [
			function (): array {
				$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

				global $wp_actions;
				$wp_actions = [];

				$this->set_fn_return( 'wp_is_post_revision', true );

				wp_update_post( [
					'ID'         => $post_id,
					'post_title' => 'Updated title',
				] );

				return [
					[],
					[
						'tec_tickets_ticket_able_post_upserted',
						'tec_tickets_ticket_able_post_created',
						'tec_tickets_ticket_able_post_updated',
					],
				];
			},
		];
	}

	/**
	 * @test
	 * @dataProvider fire_ticket_able_post_upserted_params_data_provider
	 */
	public function it_should_pass_correct_params_to_upserted_action( Closure $fixture ): void {
		$controller = $this->make_controller();
		$controller->register();

		$captured = [];
		add_action( 'tec_tickets_ticket_able_post_upserted', function ( $post_id, $post, $is_update, $has_syncable_tickets ) use ( &$captured ) {
			$captured = compact( 'post_id', 'post', 'is_update', 'has_syncable_tickets' );
		}, 10, 4 );

		[ $expected_post_id, $expected_is_update, $expected_has_syncable ] = $fixture();

		$this->assertSame( $expected_post_id, $captured['post_id'] );
		$this->assertSame( $expected_is_update, $captured['is_update'] );
		$this->assertSame( $expected_has_syncable, $captured['has_syncable_tickets'] );
	}

	public function fire_ticket_able_post_upserted_params_data_provider(): Generator {
		yield 'new post without tickets passes has_syncable_tickets as false' => [
			function (): array {
				$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

				return [ $post_id, false, false ];
			},
		];

		yield 'new post with on-sale ticket passes has_syncable_tickets as true' => [
			function (): array {
				$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

				global $wp_actions;
				$wp_actions = [];

				$this->create_on_sale_tc_ticket( $post_id, 10 );

				wp_update_post( [
					'ID'         => $post_id,
					'post_title' => 'Updated title',
				] );

				return [ $post_id, true, true ];
			},
		];
	}

	/**
	 * @test
	 * @dataProvider fire_ticket_or_ticket_able_post_deleted_data_provider
	 */
	public function it_should_fire_ticket_or_ticket_able_post_deleted( Closure $fixture ): void {
		$controller = $this->make_controller();
		$controller->register();

		[ $expected_actions, $unexpected_actions ] = $fixture();

		foreach ( $expected_actions as $action => $count ) {
			$this->assertSame(
				$count,
				did_action( $action ),
				"Expected action '{$action}' to fire {$count} time(s), got " . did_action( $action ) . '.'
			);
		}

		foreach ( $unexpected_actions as $action ) {
			$this->assertSame(
				0,
				did_action( $action ),
				"Expected action '{$action}' to NOT fire."
			);
		}
	}

	public function fire_ticket_or_ticket_able_post_deleted_data_provider(): Generator {
		yield 'trashing a ticket-enabled post fires ticket_able_post_deleted' => [
			function (): array {
				$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

				do_action( 'wp_trash_post', $post_id );

				return [
					[
						'tec_tickets_ticket_able_post_deleted' => 1,
					],
					[
						'tec_tickets_ticket_deleted',
					],
				];
			},
		];

		yield 'trashing a ticket fires ticket_deleted' => [
			function (): array {
				$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
				$ticket_id = $this->create_on_sale_tc_ticket( $post_id, 10 );

				do_action( 'wp_trash_post', $ticket_id );

				return [
					[
						'tec_tickets_ticket_deleted' => 1,
					],
					[
						'tec_tickets_ticket_able_post_deleted',
					],
				];
			},
		];

		yield 'draft post does not fire any action' => [
			function (): array {
				$post_id = static::factory()->post->create( [ 'post_status' => 'draft' ] );

				do_action( 'wp_trash_post', $post_id );

				return [
					[],
					[
						'tec_tickets_ticket_able_post_deleted',
						'tec_tickets_ticket_deleted',
					],
				];
			},
		];

		yield 'non-ticket-enabled post type does not fire any action' => [
			function (): array {
				register_post_type( 'unrelated_type', [ 'public' => true ] );
				$post_id = static::factory()->post->create( [
					'post_status' => 'publish',
					'post_type'   => 'unrelated_type',
				] );

				do_action( 'wp_trash_post', $post_id );

				return [
					[],
					[
						'tec_tickets_ticket_able_post_deleted',
						'tec_tickets_ticket_deleted',
					],
				];
			},
		];
	}

	/**
	 * @test
	 * @dataProvider fire_ticket_deleted_params_data_provider
	 */
	public function it_should_pass_correct_params_to_ticket_deleted( Closure $fixture ): void {
		$controller = $this->make_controller();
		$controller->register();

		$captured = [];
		add_action( 'tec_tickets_ticket_deleted', function ( $post_id, $is_full_deletion, $parent_id, $has_more_syncable_tickets ) use ( &$captured ) {
			$captured = compact( 'post_id', 'is_full_deletion', 'parent_id', 'has_more_syncable_tickets' );
		}, 10, 4 );

		[ $expected ] = $fixture();

		$this->assertSame( $expected['post_id'], $captured['post_id'] );
		$this->assertSame( $expected['is_full_deletion'], $captured['is_full_deletion'] );
		$this->assertSame( $expected['parent_id'], $captured['parent_id'] );
		$this->assertSame( $expected['has_more_syncable_tickets'], $captured['has_more_syncable_tickets'] );
	}

	public function fire_ticket_deleted_params_data_provider(): Generator {
		yield 'trashing single ticket passes has_more_syncable_tickets as false' => [
			function (): array {
				$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
				$ticket_id = $this->create_on_sale_tc_ticket( $post_id, 10 );

				do_action( 'wp_trash_post', $ticket_id );

				return [
					[
						'post_id'                    => $ticket_id,
						'is_full_deletion'           => false,
						'parent_id'                  => $post_id,
						'has_more_syncable_tickets'  => false,
					],
				];
			},
		];

		yield 'trashing ticket with siblings passes has_more_syncable_tickets as true' => [
			function (): array {
				$post_id    = static::factory()->post->create( [ 'post_status' => 'publish' ] );
				$ticket_id1 = $this->create_on_sale_tc_ticket( $post_id, 10 );
				$ticket_id2 = $this->create_on_sale_tc_ticket( $post_id, 20 );

				do_action( 'wp_trash_post', $ticket_id1 );

				return [
					[
						'post_id'                    => $ticket_id1,
						'is_full_deletion'           => false,
						'parent_id'                  => $post_id,
						'has_more_syncable_tickets'  => true,
					],
				];
			},
		];

		yield 'deleting ticket via before_delete_post passes is_full_deletion as true' => [
			function (): array {
				$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
				$ticket_id = $this->create_on_sale_tc_ticket( $post_id, 10 );

				do_action( 'before_delete_post', $ticket_id, get_post( $ticket_id ) );

				return [
					[
						'post_id'                    => $ticket_id,
						'is_full_deletion'           => true,
						'parent_id'                  => $post_id,
						'has_more_syncable_tickets'  => false,
					],
				];
			},
		];
	}
}
