<?php

namespace TEC\Tickets\Commerce\Gateways\Square\Syncs;

use TEC\Common\Tests\Provider\Controller_Test_Case;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Traits\With_Square_Sync_Enabled;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Controller as Syncs_Controller;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects\Item;
use TEC\Tickets\Commerce\Gateways\Square\Settings;
use TEC\Tickets\Commerce\Settings as Commerce_Settings;
use TEC\Tickets\Commerce\Meta as Commerce_Meta;
use Tribe__Settings_Manager as Settings_Manager;
use Closure;
use Generator;
use WP_Post;

class Listeners_Test extends Controller_Test_Case {
	use With_Uopz;
	use With_Square_Sync_Enabled;
	use Ticket_Maker;
	use Order_Maker;

	protected string $controller_class = Listeners::class;

	/**
	 * @test
	 * @dataProvider schedule_sync_data_provider
	 */
	public function it_should_schedule_sync( Closure $fixture ): void {
		$controller = $this->make_controller();
		$controller->register();

		[ $parent_id, $should_schedule ] = $fixture();

		if ( $should_schedule ) {
			$this->assertTrue(
				as_has_scheduled_action( Items_Sync::HOOK_SYNC_EVENT_ACTION, [ $parent_id ], Syncs_Controller::AS_SYNC_ACTION_GROUP ),
				'Expected sync to be scheduled.'
			);
		} else {
			$this->assertFalse(
				as_has_scheduled_action( Items_Sync::HOOK_SYNC_EVENT_ACTION, [ $parent_id ?? 0 ], Syncs_Controller::AS_SYNC_ACTION_GROUP ),
				'Expected sync to NOT be scheduled.'
			);
		}
	}

	public function schedule_sync_data_provider(): Generator {
		yield 'ticket with remote object ID schedules sync' => [
			function (): array {
				$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
				$ticket_id = $this->create_on_sale_tc_ticket( $post_id, 10 );

				Commerce_Meta::set( $ticket_id, Item::SQUARE_ID_META, 'square_123' );

				do_action( 'tec_tickets_ticket_upserted', $ticket_id, $post_id );

				return [ $post_id, true ];
			},
		];

		yield 'ticket without remote object ID and sync completed schedules sync' => [
			function (): array {
				$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
				$ticket_id = $this->create_on_sale_tc_ticket( $post_id, 10 );

				$ticket_able_post_types = (array) tribe_get_option( 'ticket-enabled-post-types', [] );
				foreach ( $ticket_able_post_types as $ptype ) {
					Commerce_Settings::set( Syncs_Controller::OPTION_SYNC_ACTIONS_COMPLETED, time(), [ $ptype ] );
				}

				do_action( 'tec_tickets_ticket_upserted', $ticket_id, $post_id );

				return [ $post_id, true ];
			},
		];

		yield 'ticket without remote object ID and sync not completed does not schedule sync' => [
			function (): array {
				$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
				$ticket_id = $this->create_on_sale_tc_ticket( $post_id, 10 );

				do_action( 'tec_tickets_ticket_upserted', $ticket_id, $post_id );

				return [ $post_id, false ];
			},
		];

		yield 'null parent ID does not schedule sync' => [
			function (): array {
				$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
				$ticket_id = $this->create_on_sale_tc_ticket( $post_id, 10 );

				do_action( 'tec_tickets_ticket_upserted', $ticket_id, null );

				return [ null, false ];
			},
		];
	}

	/**
	 * @test
	 * @dataProvider schedule_sync_on_save_data_provider
	 */
	public function it_should_schedule_sync_on_save( Closure $fixture ): void {
		$controller = $this->make_controller();
		$controller->register();

		[ $post_id, $should_schedule ] = $fixture();

		if ( $should_schedule ) {
			$this->assertTrue(
				as_has_scheduled_action( Items_Sync::HOOK_SYNC_EVENT_ACTION, [ $post_id ], Syncs_Controller::AS_SYNC_ACTION_GROUP ),
				'Expected sync to be scheduled.'
			);
		} else {
			$this->assertFalse(
				as_has_scheduled_action( Items_Sync::HOOK_SYNC_EVENT_ACTION, [ $post_id ], Syncs_Controller::AS_SYNC_ACTION_GROUP ),
				'Expected sync to NOT be scheduled.'
			);
		}
	}

	public function schedule_sync_on_save_data_provider(): Generator {
		yield 'post with on-sale ticket and sync completed schedules sync' => [
			function (): array {
				$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );
				$this->create_on_sale_tc_ticket( $post_id, 10 );

				// Mark initial sync as completed so is_object_syncable returns true.
				$ticket_able_post_types = (array) tribe_get_option( 'ticket-enabled-post-types', [] );
				foreach ( $ticket_able_post_types as $ptype ) {
					Commerce_Settings::set( Syncs_Controller::OPTION_SYNC_ACTIONS_COMPLETED, time(), [ $ptype ] );
				}

				$post = get_post( $post_id );
				do_action( 'tec_tickets_ticket_able_post_upserted', $post_id, $post );

				return [ $post_id, true ];
			},
		];

		yield 'post without tickets does not schedule sync' => [
			function (): array {
				$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

				$post = get_post( $post_id );
				do_action( 'tec_tickets_ticket_able_post_upserted', $post_id, $post );

				return [ $post_id, false ];
			},
		];

		yield 'post with non-ticket-enabled post type does not schedule sync' => [
			function (): array {
				register_post_type( 'not_ticketed', [ 'public' => true ] );
				$post_id = static::factory()->post->create( [
					'post_status' => 'publish',
					'post_type'   => 'not_ticketed',
				] );

				$post = get_post( $post_id );
				do_action( 'tec_tickets_ticket_able_post_upserted', $post_id, $post );

				return [ $post_id, false ];
			},
		];

		yield 'revision does not schedule sync' => [
			function (): array {
				$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );
				$this->create_on_sale_tc_ticket( $post_id, 10 );

				$this->set_fn_return( 'wp_is_post_revision', true );

				$post = get_post( $post_id );
				do_action( 'tec_tickets_ticket_able_post_upserted', $post_id, $post );

				return [ $post_id, false ];
			},
		];
	}

	/**
	 * @test
	 * @dataProvider schedule_sync_on_delete_data_provider
	 */
	public function it_should_schedule_sync_on_delete( Closure $fixture ): void {
		$controller = $this->make_controller();
		$controller->register();

		[ $expected_hook, $expected_args ] = $fixture();

		if ( $expected_hook ) {
			$this->assertTrue(
				as_has_scheduled_action( $expected_hook, $expected_args, Syncs_Controller::AS_SYNC_ACTION_GROUP ),
				"Expected action '{$expected_hook}' to be scheduled."
			);
		} else {
			$this->assertFalse(
				as_has_scheduled_action( Items_Sync::HOOK_SYNC_DELETE_EVENT_ACTION, null, Syncs_Controller::AS_SYNC_ACTION_GROUP ),
				'Expected no delete action to be scheduled.'
			);
		}
	}

	public function schedule_sync_on_delete_data_provider(): Generator {
		yield 'non-existent post does not schedule' => [
			function (): array {
				do_action( 'wp_trash_post', PHP_INT_MAX );

				return [ null, null ];
			},
		];

		yield 'draft post does not schedule' => [
			function (): array {
				$post_id = static::factory()->post->create( [ 'post_status' => 'draft' ] );

				do_action( 'wp_trash_post', $post_id );

				return [ null, null ];
			},
		];

		yield 'post type not in ticket-enabled types does not schedule' => [
			function (): array {
				register_post_type( 'unrelated_type', [ 'public' => true ] );
				$post_id = static::factory()->post->create( [
					'post_status' => 'publish',
					'post_type'   => 'unrelated_type',
				] );

				do_action( 'wp_trash_post', $post_id );

				return [ null, null ];
			},
		];

		yield 'ticket-enabled post without remote ID does not schedule' => [
			function (): array {
				$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );
				$this->create_on_sale_tc_ticket( $post_id, 10 );

				do_action( 'wp_trash_post', $post_id );

				return [ null, null ];
			},
		];

		yield 'ticket-enabled post with remote ID schedules deletion' => [
			function (): array {
				$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );
				$this->create_on_sale_tc_ticket( $post_id, 10 );

				Commerce_Meta::set( $post_id, Item::SQUARE_ID_META, 'square_event_123' );

				do_action( 'wp_trash_post', $post_id );

				return [ Items_Sync::HOOK_SYNC_DELETE_EVENT_ACTION, [ 0, 'square_event_123' ] ];
			},
		];

		yield 'last sync-able ticket trashed schedules deletion for parent event' => [
			function (): array {
				$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
				$ticket_id = $this->create_on_sale_tc_ticket( $post_id, 10 );

				Commerce_Meta::set( $ticket_id, Item::SQUARE_ID_META, 'square_ticket_123' );
				Commerce_Meta::set( $post_id, Item::SQUARE_ID_META, 'square_event_456' );

				do_action( 'wp_trash_post', $ticket_id );

				// The parent event should be scheduled for deletion since this was the last sync-able ticket.
				return [ Items_Sync::HOOK_SYNC_DELETE_EVENT_ACTION, [ 0, 'square_event_456' ] ];
			},
		];

		yield 'ticket trashed with multiple sync-able tickets schedules deletion for ticket only' => [
			function (): array {
				$post_id    = static::factory()->post->create( [ 'post_status' => 'publish' ] );
				$ticket_id1 = $this->create_on_sale_tc_ticket( $post_id, 10 );
				$ticket_id2 = $this->create_on_sale_tc_ticket( $post_id, 20 );

				Commerce_Meta::set( $ticket_id1, Item::SQUARE_ID_META, 'square_ticket_1' );
				Commerce_Meta::set( $ticket_id2, Item::SQUARE_ID_META, 'square_ticket_2' );

				do_action( 'wp_trash_post', $ticket_id1 );

				// Only the ticket should be deleted, not the parent event.
				return [ Items_Sync::HOOK_SYNC_DELETE_EVENT_ACTION, [ 0, 'square_ticket_1' ] ];
			},
		];
	}

	/**
	 * @test
	 * @dataProvider schedule_sync_on_date_start_data_provider
	 */
	public function it_should_schedule_sync_on_date_start( Closure $fixture ): void {
		$controller = $this->make_controller();
		$controller->register();

		[ $parent_id, $should_schedule ] = $fixture();

		if ( $should_schedule ) {
			$this->assertTrue(
				as_has_scheduled_action( Items_Sync::HOOK_SYNC_EVENT_ACTION, [ $parent_id ], Syncs_Controller::AS_SYNC_ACTION_GROUP ),
				'Expected sync to be scheduled on date start.'
			);
		} else {
			$this->assertFalse(
				as_has_scheduled_action( Items_Sync::HOOK_SYNC_EVENT_ACTION, [ $parent_id ], Syncs_Controller::AS_SYNC_ACTION_GROUP ),
				'Expected sync to NOT be scheduled on date start.'
			);
		}
	}

	public function schedule_sync_on_date_start_data_provider(): Generator {
		yield 'its_happening is true and ticket is synced schedules sync' => [
			function (): array {
				$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
				$ticket_id = $this->create_on_sale_tc_ticket( $post_id, 10 );

				Commerce_Meta::set( $ticket_id, Item::SQUARE_ID_META, 'square_123' );

				$post_parent = get_post( $post_id );

				do_action( 'tec_tickets_ticket_start_date_trigger', $ticket_id, true, time() + HOUR_IN_SECONDS, $post_parent );

				return [ $post_id, true ];
			},
		];

		yield 'its_happening is false but within about-to-go-on-sale window schedules sync' => [
			function (): array {
				$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
				$ticket_id = $this->create_on_sale_tc_ticket( $post_id, 10 );

				Commerce_Meta::set( $ticket_id, Item::SQUARE_ID_META, 'square_123' );

				$post_parent = get_post( $post_id );

				// Timestamp close enough that time() >= $timestamp - about_to_go_to_sale_seconds.
				$timestamp = time() + 5;

				do_action( 'tec_tickets_ticket_start_date_trigger', $ticket_id, false, $timestamp, $post_parent );

				return [ $post_id, true ];
			},
		];

		yield 'its_happening is false and outside about-to-go-on-sale window does not schedule' => [
			function (): array {
				$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
				$ticket_id = $this->create_on_sale_tc_ticket( $post_id, 10 );

				Commerce_Meta::set( $ticket_id, Item::SQUARE_ID_META, 'square_123' );

				$post_parent = get_post( $post_id );

				// Timestamp far in the future.
				$timestamp = time() + DAY_IN_SECONDS;

				do_action( 'tec_tickets_ticket_start_date_trigger', $ticket_id, false, $timestamp, $post_parent );

				return [ $post_id, false ];
			},
		];

		yield 'ticket not syncable does not schedule' => [
			function (): array {
				$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
				$ticket_id = $this->create_on_sale_tc_ticket( $post_id, 10 );

				$post_parent = get_post( $post_id );

				do_action( 'tec_tickets_ticket_start_date_trigger', $ticket_id, true, time(), $post_parent );

				return [ $post_id, false ];
			},
		];
	}

	/**
	 * @test
	 * @dataProvider schedule_sync_on_date_end_data_provider
	 */
	public function it_should_schedule_sync_on_date_end( Closure $fixture ): void {
		$controller = $this->make_controller();
		$controller->register();

		[ $parent_id, $should_schedule ] = $fixture();

		if ( $should_schedule ) {
			$this->assertTrue(
				as_has_scheduled_action( Items_Sync::HOOK_SYNC_EVENT_ACTION, [ $parent_id ], Syncs_Controller::AS_SYNC_ACTION_GROUP ),
				'Expected sync to be scheduled on date end.'
			);
		} else {
			$this->assertFalse(
				as_has_scheduled_action( Items_Sync::HOOK_SYNC_EVENT_ACTION, [ $parent_id ], Syncs_Controller::AS_SYNC_ACTION_GROUP ),
				'Expected sync to NOT be scheduled on date end.'
			);
		}
	}

	public function schedule_sync_on_date_end_data_provider(): Generator {
		yield 'its_happening is true and ticket has remote ID schedules sync' => [
			function (): array {
				$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
				$ticket_id = $this->create_on_sale_tc_ticket( $post_id, 10 );

				Commerce_Meta::set( $ticket_id, Item::SQUARE_ID_META, 'square_123' );

				$post_parent = get_post( $post_id );

				do_action( 'tec_tickets_ticket_end_date_trigger', $ticket_id, true, time(), $post_parent );

				return [ $post_id, true ];
			},
		];

		yield 'its_happening is false does not schedule' => [
			function (): array {
				$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
				$ticket_id = $this->create_on_sale_tc_ticket( $post_id, 10 );

				Commerce_Meta::set( $ticket_id, Item::SQUARE_ID_META, 'square_123' );

				$post_parent = get_post( $post_id );

				do_action( 'tec_tickets_ticket_end_date_trigger', $ticket_id, false, time(), $post_parent );

				return [ $post_id, false ];
			},
		];

		yield 'ticket without remote ID does not schedule' => [
			function (): array {
				$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
				$ticket_id = $this->create_on_sale_tc_ticket( $post_id, 10 );

				$post_parent = get_post( $post_id );

				do_action( 'tec_tickets_ticket_end_date_trigger', $ticket_id, true, time(), $post_parent );

				return [ $post_id, false ];
			},
		];
	}

	/**
	 * @test
	 * @dataProvider schedule_ticket_sync_on_out_of_sync_data_provider
	 */
	public function it_should_schedule_ticket_sync_on_out_of_sync( Closure $fixture ): void {
		$controller = $this->make_controller();
		$controller->register();

		[ $ticket_id, $quantity, $state ] = $fixture();

		$this->assertTrue(
			as_has_scheduled_action( Inventory_Sync::HOOK_CHECK_TICKET_INVENTORY_SYNC, [ $ticket_id, $quantity, $state ], Syncs_Controller::AS_SYNC_ACTION_GROUP ),
			'Expected inventory sync check to be scheduled.'
		);
	}

	public function schedule_ticket_sync_on_out_of_sync_data_provider(): Generator {
		yield 'out of sync ticket schedules inventory check' => [
			function (): array {
				$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
				$ticket_id = $this->create_on_sale_tc_ticket( $post_id, 10 );

				do_action( 'tec_tickets_commerce_square_ticket_out_of_sync', $ticket_id, 5, 'IN_STOCK' );

				return [ $ticket_id, 5, 'IN_STOCK' ];
			},
		];
	}

	/**
	 * @test
	 * @dataProvider schedule_ticket_sync_on_stock_changed_data_provider
	 */
	public function it_should_schedule_ticket_sync_on_stock_changed( Closure $fixture ): void {
		$controller = $this->make_controller();
		$controller->register();

		[ $parent_id, $should_schedule ] = $fixture();

		if ( $should_schedule ) {
			$this->assertTrue(
				as_has_scheduled_action( Items_Sync::HOOK_SYNC_EVENT_ACTION, [ $parent_id ], Syncs_Controller::AS_SYNC_ACTION_GROUP ),
				'Expected sync to be scheduled on stock change.'
			);
		} else {
			$this->assertFalse(
				as_has_scheduled_action( Items_Sync::HOOK_SYNC_EVENT_ACTION, null, Syncs_Controller::AS_SYNC_ACTION_GROUP ),
				'Expected sync to NOT be scheduled on stock change.'
			);
		}
	}

	public function schedule_ticket_sync_on_stock_changed_data_provider(): Generator {
		yield 'valid ticket with remote ID schedules sync' => [
			function (): array {
				$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
				$ticket_id = $this->create_on_sale_tc_ticket( $post_id, 10 );

				Commerce_Meta::set( $ticket_id, Item::SQUARE_ID_META, 'square_123' );

				do_action( 'tec_tickets_ticket_stock_changed', $ticket_id );

				return [ $post_id, true ];
			},
		];

		yield 'invalid ticket ID does not schedule sync' => [
			function (): array {
				do_action( 'tec_tickets_ticket_stock_changed', PHP_INT_MAX );

				return [ null, false ];
			},
		];
	}

	/**
	 * @test
	 * @dataProvider reset_sync_status_data_provider
	 */
	public function it_should_reset_sync_status_on_settings_change( Closure $fixture ): void {
		$controller = $this->make_controller();
		$controller->register();

		$did_action_before = did_action( Listeners::HOOK_SYNC_RESET_SYNCED_POST_TYPE );

		[ $expected_reset_action_count ] = $fixture();

		remove_all_actions( 'tec_shutdown' );
		do_action( 'tec_shutdown' );

		$did_action_after = did_action( Listeners::HOOK_SYNC_RESET_SYNCED_POST_TYPE );

		$this->assertSame(
			$expected_reset_action_count,
			$did_action_after - $did_action_before,
			"Expected reset action to fire {$expected_reset_action_count} time(s)."
		);
	}

	public function reset_sync_status_data_provider(): Generator {
		yield 'sync disabled triggers global reset' => [
			function (): array {
				$old_options = Settings_Manager::get_options();

				$new_options = $old_options;
				unset( $new_options[ Settings::OPTION_INVENTORY_SYNC ] );

				do_action( 'tec_common_settings_manager_pre_set_options', $new_options, $old_options );

				return [ 1 ];
			},
		];

		yield 'same post types does not trigger reset' => [
			function (): array {
				$old_options = Settings_Manager::get_options();
				$new_options = $old_options;

				$new_options[ Settings::OPTION_INVENTORY_SYNC ] = true;

				do_action( 'tec_common_settings_manager_pre_set_options', $new_options, $old_options );

				return [ 0 ];
			},
		];

		yield 'removed post type triggers per-type reset' => [
			function (): array {
				$ticket_able_post_types = (array) tribe_get_option( 'ticket-enabled-post-types', [] );

				$old_options = Settings_Manager::get_options();
				$old_options['ticket-enabled-post-types'] = $ticket_able_post_types;

				$new_options = $old_options;
				$new_options[ Settings::OPTION_INVENTORY_SYNC ] = true;

				// Remove a post type.
				array_pop( $ticket_able_post_types );
				$new_options['ticket-enabled-post-types'] = $ticket_able_post_types;

				do_action( 'tec_common_settings_manager_pre_set_options', $new_options, $old_options );

				return [ 1 ];
			},
		];
	}

	/**
	 * @test
	 */
	public function it_should_unsync_on_merchant_disconnected(): void {
		$controller = $this->make_controller();
		$controller->register();

		$did_action_before = did_action( Listeners::HOOK_SYNC_RESET_SYNCED_POST_TYPE );

		do_action( 'tec_tickets_commerce_square_merchant_disconnected' );

		remove_all_actions( 'tec_shutdown' );
		do_action( 'tec_shutdown' );

		$did_action_after = did_action( Listeners::HOOK_SYNC_RESET_SYNCED_POST_TYPE );

		$this->assertGreaterThan(
			$did_action_before,
			$did_action_after,
			'Expected reset action to fire after merchant disconnect.'
		);
	}
}
