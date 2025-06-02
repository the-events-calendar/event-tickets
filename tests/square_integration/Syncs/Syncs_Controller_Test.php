<?php

namespace TEC\Tickets\Commerce\Gateways\Square\Syncs;

use TEC\Common\Tests\Provider\Controller_Test_Case;
use Tribe\Tests\Traits\With_Uopz;
use TEC\Tickets\Commerce\Gateways\Square\Merchant;
use Tribe\Tickets\Test\Traits\With_Square_Sync_Enabled;
use Exception;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use TEC\Tickets\Commerce\Settings as Commerce_Settings;
use Tribe__Settings_Manager as Settings_Manager;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects\NotSyncableItemException;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects\Item;
use TEC\Tickets\Commerce\Meta as Commerce_Meta;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;

class Syncs_Controller_Test extends Controller_Test_Case {
	use With_Uopz;
	use With_Square_Sync_Enabled;
	use Ticket_Maker;
	use Order_Maker;

	protected string $controller_class = Controller::class;

	/**
	 * @test
	 */
	public function it_should_be_active_when_merchant_is_connected(): void {
		$controller = $this->make_controller();
		$this->assertTrue( $controller->is_active() );
		$this->set_class_fn_return( Merchant::class, 'is_connected', false );
		$this->assertFalse( $controller->is_active() );
	}

	/**
	 * @test
	 */
	public function it_should_schedule_batch_sync(): void {
		$controller = $this->make_controller();
		$this->assertFalse( as_has_scheduled_action( Regulator::HOOK_INIT_SYNC_ACTION, [], Controller::AS_SYNC_ACTION_GROUP ) );
		$controller->schedule_batch_sync();
		$this->assertTrue( as_has_scheduled_action( Regulator::HOOK_INIT_SYNC_ACTION, [], Controller::AS_SYNC_ACTION_GROUP ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_schedule_batch_sync_when_sync_is_completed_or_in_progress(): void {
		$controller = $this->make_controller();
		$this->assertFalse( as_has_scheduled_action( Regulator::HOOK_INIT_SYNC_ACTION, [], Controller::AS_SYNC_ACTION_GROUP ) );

		$this->set_class_fn_return( Controller::class, 'is_sync_completed', true );
		$this->set_class_fn_return( Controller::class, 'is_sync_in_progress', false );
		$controller->schedule_batch_sync();
		$this->assertFalse( as_has_scheduled_action( Regulator::HOOK_INIT_SYNC_ACTION, [], Controller::AS_SYNC_ACTION_GROUP ) );

		$this->set_class_fn_return( Controller::class, 'is_sync_completed', false );
		$this->set_class_fn_return( Controller::class, 'is_sync_in_progress', true );
		$controller->schedule_batch_sync();
		$this->assertFalse( as_has_scheduled_action( Regulator::HOOK_INIT_SYNC_ACTION, [], Controller::AS_SYNC_ACTION_GROUP ) );

		$this->set_class_fn_return( Controller::class, 'is_sync_completed', true );
		$this->set_class_fn_return( Controller::class, 'is_sync_in_progress', true );
		$controller->schedule_batch_sync();
		$this->assertFalse( as_has_scheduled_action( Regulator::HOOK_INIT_SYNC_ACTION, [], Controller::AS_SYNC_ACTION_GROUP ) );
	}

	/**
	 * @test
	 */
	public function it_should_throw_exception_to_mark_action_failed(): void {
		remove_all_actions( 'tribe_log' );
		remove_all_actions( 'action_scheduler_begin_execute' );
		$this->make_controller()->register();

		$data = [ 'msg' => 'this is a test' ];

		// No exception is thrown, because we are not in AS context.
		do_action( 'tribe_log', 'error', 'test', $data );

		// Now we are in AS context.
		do_action( 'action_scheduler_begin_execute' );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'test with error data: ' . wp_json_encode( $data, JSON_PRETTY_PRINT ) );
		do_action( 'tribe_log', 'error', 'test', $data );
	}

	/**
	 * @test
	 */
	public function it_should_get_sync_able_tickets_of_event(): void {
		$post = self::factory()->post->create();
		$ticket_id_1 = $this->create_on_sale_tc_ticket( $post, 10 );
		$ticket_id_2 = $this->create_about_to_be_on_sale_tc_ticket( $post, 20 );
		$ticket_id_3 = $this->create_pre_sale_tc_ticket( $post, 30 );
		$ticket_id_4 = $this->create_after_sales_tc_ticket( $post, 40 );

		$tickets = Controller::get_sync_able_tickets_of_event( $post );

		$ticket_ids = array_map( fn( $ticket ) => $ticket->ID, $tickets );

		$this->assertCount( 3, $tickets );
		$this->assertContains( $ticket_id_1, $ticket_ids );
		$this->assertContains( $ticket_id_2, $ticket_ids );
		$this->assertNotContains( $ticket_id_3, $ticket_ids );
		$this->assertContains( $ticket_id_4, $ticket_ids );
	}

	/**
	 * @test
	 */
	public function it_should_get_ticket_able_post_types_to_sync(): void {
		$ptypes = Controller::ticket_able_post_types_to_sync();

		$this->assertSame( (array) tribe_get_option( 'ticket-enabled-post-types', [] ), $ptypes );

		$this->assertNotEmpty( $ptypes );

		$ptype = $ptypes[ array_rand( $ptypes ) ];

		Commerce_Settings::set( Controller::OPTION_SYNC_ACTIONS_COMPLETED, time(), [ $ptype ] );

		$new_ptypes = Controller::ticket_able_post_types_to_sync();

		$this->assertNotContains( $ptype, $new_ptypes );

		$this->assertCount( count( $ptypes ) - 1, $new_ptypes );
	}

	/**
	 * @test
	 */
	public function it_should_return_when_sync_is_completed(): void {
		$ptypes = (array) tribe_get_option( 'ticket-enabled-post-types', [] );

		foreach ( $ptypes as $ptype ) {
			$this->assertFalse( Controller::is_sync_completed() );
			Commerce_Settings::set( Controller::OPTION_SYNC_ACTIONS_COMPLETED, time(), [ $ptype ] );
		}

		$this->assertTrue( Controller::is_sync_completed() );
	}

	/**
	 * @test
	 */
	public function it_should_return_when_is_sync_in_progress(): void {
		$ptypes = (array) tribe_get_option( 'ticket-enabled-post-types', [] );

		$random_ptype = $ptypes[ array_rand( $ptypes ) ];

		$this->assertFalse( Controller::is_sync_in_progress() );
		Commerce_Settings::set( Controller::OPTION_SYNC_ACTIONS_IN_PROGRESS, time(), [ $random_ptype ] );
		$this->assertTrue( Controller::is_sync_in_progress() );
	}

	/**
	 * @test
	 */
	public function it_should_reset_sync_status(): void {
		remove_all_actions( 'tec_shutdown' );

		$settings = Settings_Manager::get_options();

		Controller::reset_sync_status( $settings );

		do_action( 'tec_shutdown' );
		remove_all_actions( 'tec_shutdown' );

		$this->assertEquals( $settings, Settings_Manager::get_options() );

		$this->assertEquals( 1, did_action( Listeners::HOOK_SYNC_RESET_SYNCED_POST_TYPE ) );
		$this->assertEquals( 0, did_action( 'tec_tickets_commerce_square_sync_post_reset_status' ) );

		$ticket_able_post_types = (array) tribe_get_option( 'ticket-enabled-post-types', [] );

		foreach ( $ticket_able_post_types as $ptype ) {
			Commerce_Settings::set( Controller::OPTION_SYNC_ACTIONS_COMPLETED, time(), [ $ptype ] );
			Commerce_Settings::set( Controller::OPTION_SYNC_ACTIONS_IN_PROGRESS, time(), [ $ptype ] );
		}

		$settings = Settings_Manager::get_options();

		// Reset only a single post type.
		Controller::reset_sync_status( $settings, $ptype );

		do_action( 'tec_shutdown' );
		remove_all_actions( 'tec_shutdown' );

		$this->assertEquals( 2, did_action( Listeners::HOOK_SYNC_RESET_SYNCED_POST_TYPE ) );
		$this->assertEquals( 1, did_action( 'tec_tickets_commerce_square_sync_post_reset_status' ) );

		$this->assertCount( count( $settings ) - 2, Settings_Manager::get_options() );

		// Reset all post types.
		Controller::reset_sync_status( $settings );

		do_action( 'tec_shutdown' );
		remove_all_actions( 'tec_shutdown' );

		$this->assertEquals( 3, did_action( Listeners::HOOK_SYNC_RESET_SYNCED_POST_TYPE ) );
		$this->assertEquals( 2, did_action( 'tec_tickets_commerce_square_sync_post_reset_status' ) );

		$this->assertCount( count( $settings ) - ( 2 * count( $ticket_able_post_types ) ), Settings_Manager::get_options() );
	}

	/**
	 * @test
	 */
	public function it_should_return_when_ticket_is_in_sync_with_square_data(): void {
		$ticket = $this->with_capacity( 10 )->create_on_sale_tc_ticket( self::factory()->post->create(), 10 );

		Commerce_Meta::set( $ticket, Item::SQUARE_ID_META, '123' );

		$ticket_object = tribe( Ticket::class )->load_ticket_object( $ticket );

		$this->assertTrue( Controller::is_ticket_in_sync_with_square_data( $ticket_object, 10, 'IN_STOCK' ) );
		$this->assertFalse( Controller::is_ticket_in_sync_with_square_data( $ticket_object, 11, 'IN_STOCK' ) );

		$this->create_order_through_square( [ $ticket => 4 ] );
		$ticket_object = tribe( Ticket::class )->load_ticket_object( $ticket );

		$this->assertFalse( Controller::is_ticket_in_sync_with_square_data( $ticket_object, 10, 'IN_STOCK' ) );
		$this->assertTrue( Controller::is_ticket_in_sync_with_square_data( $ticket_object, 6, 'IN_STOCK' ) );

		$this->create_order_through_square( [ $ticket => 6 ] );
		$ticket_object = tribe( Ticket::class )->load_ticket_object( $ticket );

		$this->assertFalse( Controller::is_ticket_in_sync_with_square_data( $ticket_object, 6, 'IN_STOCK' ) );
		$this->assertFalse( Controller::is_ticket_in_sync_with_square_data( $ticket_object, 10, 'IN_STOCK' ) );
		$this->assertTrue( Controller::is_ticket_in_sync_with_square_data( $ticket_object, 0, 'IN_STOCK' ) );
	}

	/**
	 * @test
	 */
	public function it_should_throw_exception_when_ticket_is_not_syncable(): void {
		$ticket = $this->create_on_sale_tc_ticket( self::factory()->post->create(), 10 );

		$ticket_object = tribe( Ticket::class )->load_ticket_object( $ticket );

		$this->expectException( NotSyncableItemException::class );
		$this->expectExceptionMessage( 'Ticket is not sync-able.' );
		Controller::is_ticket_in_sync_with_square_data( $ticket_object, 10, 'IN_STOCK' );
	}

	/**
	 * @test
	 */
	public function it_should_return_when_no_limit_ticket_is_in_sync_with_square_data(): void {
		$ticket = $this->with_capacity( -1 )->create_on_sale_tc_ticket( self::factory()->post->create(), 10 );

		Commerce_Meta::set( $ticket, Item::SQUARE_ID_META, '123' );

		$ticket_object = tribe( Ticket::class )->load_ticket_object( $ticket );

		$this->assertTrue( Controller::is_ticket_in_sync_with_square_data( $ticket_object, 900000100, 'IN_STOCK' ) );
		$this->assertFalse( Controller::is_ticket_in_sync_with_square_data( $ticket_object, 11, 'IN_STOCK' ) );

		$this->create_order_through_square( [ $ticket => 4 ] );
		$ticket_object = tribe( Ticket::class )->load_ticket_object( $ticket );

		$this->assertTrue( Controller::is_ticket_in_sync_with_square_data( $ticket_object, 900000096, 'IN_STOCK' ) );
		$this->assertTrue( Controller::is_ticket_in_sync_with_square_data( $ticket_object, 900000100, 'IN_STOCK' ) );
		$this->assertFalse( Controller::is_ticket_in_sync_with_square_data( $ticket_object, 96, 'IN_STOCK' ) );
	}
}
