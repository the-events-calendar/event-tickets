<?php
/**
 * Tests for the RSVP V1 Controller.
 *
 * @since TBD
 */

namespace TEC\Tickets\RSVP\V1;

use TEC\Common\Tests\Provider\Controller_Test_Case;
use Tribe__Tickets__RSVP;

/**
 * Class Controller_Test
 *
 * @since TBD
 */
class Controller_Test extends Controller_Test_Case {

	/**
	 * The controller class to test.
	 *
	 * @var string
	 */
	protected $controller_class = Controller::class;

	/**
	 * @test
	 */
	public function test_registers_rsvp_singleton(): void {
		$controller = $this->make_controller();
		$controller->register();

		$this->assertInstanceOf(
			Tribe__Tickets__RSVP::class,
			tribe( 'tickets.rsvp' )
		);
	}

	/**
	 * @test
	 */
	public function test_registers_rsvp_repositories(): void {
		$controller = $this->make_controller();
		$controller->register();

		$this->assertTrue( tribe()->has( 'tickets.ticket-repository.rsvp' ) );
		$this->assertTrue( tribe()->has( 'tickets.attendee-repository.rsvp' ) );
	}

	/**
	 * @test
	 */
	public function test_registers_init_hooks(): void {
		$controller = $this->make_controller();
		$controller->register();

		$rsvp = tribe( 'tickets.rsvp' );
		$this->assertNotFalse( has_action( 'init', [ $rsvp, 'init' ] ) );
		$this->assertEquals( 9, has_action( 'init', [ $rsvp, 'set_plugin_name' ] ) );
	}

	/**
	 * @test
	 */
	public function test_registers_enqueue_hooks_with_correct_priority(): void {
		$controller = $this->make_controller();
		$controller->register();

		$rsvp = tribe( 'tickets.rsvp' );
		$this->assertEquals( 5, has_action( 'wp_enqueue_scripts', [ $rsvp, 'register_resources' ] ) );
		$this->assertEquals( 11, has_action( 'wp_enqueue_scripts', [ $rsvp, 'enqueue_resources' ] ) );
	}

	/**
	 * @test
	 */
	public function test_registers_ajax_hooks(): void {
		$controller = $this->make_controller();
		$controller->register();

		$rsvp = tribe( 'tickets.rsvp' );
		$this->assertNotFalse( has_action( 'wp_ajax_nopriv_tribe_tickets_rsvp_handle', [ $rsvp, 'ajax_handle_rsvp' ] ) );
		$this->assertNotFalse( has_action( 'wp_ajax_tribe_tickets_rsvp_handle', [ $rsvp, 'ajax_handle_rsvp' ] ) );
	}

	/**
	 * @test
	 */
	public function test_unregister_removes_rsvp_hooks(): void {
		$controller = $this->make_controller();
		$controller->register();

		$rsvp = tribe( 'tickets.rsvp' );

		// Verify hooks are registered.
		$this->assertNotFalse( has_action( 'init', [ $rsvp, 'init' ] ) );

		// Unregister.
		$controller->unregister();

		// Verify hooks are removed.
		$this->assertFalse( has_action( 'init', [ $rsvp, 'init' ] ) );
	}

	/**
	 * @test
	 */
	public function test_can_reregister_after_unregister(): void {
		$controller = $this->make_controller();
		$controller->register();
		$controller->unregister();

		// Re-register.
		$controller->register();

		$rsvp = tribe( 'tickets.rsvp' );
		$this->assertNotFalse( has_action( 'init', [ $rsvp, 'init' ] ) );
	}

	/**
	 * @test
	 */
	public function test_registers_block_ajax_hooks(): void {
		$controller = $this->make_controller();
		$controller->register();

		$rsvp_block = tribe( 'tickets.editor.blocks.rsvp' );
		$this->assertNotFalse( has_action( 'wp_ajax_rsvp-form', [ $rsvp_block, 'rsvp_form' ] ) );
		$this->assertNotFalse( has_action( 'wp_ajax_nopriv_rsvp-form', [ $rsvp_block, 'rsvp_form' ] ) );
		$this->assertNotFalse( has_action( 'wp_ajax_rsvp-process', [ $rsvp_block, 'rsvp_process' ] ) );
		$this->assertNotFalse( has_action( 'wp_ajax_nopriv_rsvp-process', [ $rsvp_block, 'rsvp_process' ] ) );
	}

	/**
	 * @test
	 */
	public function test_registers_block_registration_hook(): void {
		$controller = $this->make_controller();
		$controller->register();

		$rsvp_block = tribe( 'tickets.editor.blocks.rsvp' );
		$this->assertNotFalse( has_action( 'tribe_editor_register_blocks', [ $rsvp_block, 'register' ] ) );
	}

	/**
	 * @test
	 */
	public function test_registers_promoter_observer_hooks(): void {
		$controller = $this->make_controller();
		$controller->register();

		// Verify RSVP Observer hooks are registered.
		$this->assertNotFalse( has_action( 'event_tickets_rsvp_attendee_created' ) );
		$this->assertNotFalse( has_action( 'updated_postmeta' ) );

		// Verify Promoter Observer hooks are registered.
		$this->assertNotFalse( has_action( 'save_post_tribe_rsvp_tickets' ) );
		$this->assertNotFalse( has_action( 'tickets_rsvp_ticket_deleted' ) );
		$this->assertNotFalse( has_action( 'event_tickets_rsvp_tickets_generated' ) );
	}

	/**
	 * @test
	 */
	public function test_registers_csv_importer_hooks(): void {
		$controller = $this->make_controller();
		$controller->register();

		$this->assertNotFalse( has_action( 'tribe_aggregator_record_activity_wakeup' ) );
	}

	/**
	 * @test
	 */
	public function test_registers_filter_hooks(): void {
		$controller = $this->make_controller();
		$controller->register();

		$rsvp = tribe( 'tickets.rsvp' );
		$this->assertNotFalse( has_filter( 'post_updated_messages', [ $rsvp, 'updated_messages' ] ) );
		$this->assertNotFalse( has_filter( 'tribe_get_cost', [ $rsvp, 'trigger_get_cost' ] ) );
		$this->assertNotFalse( has_filter( 'tribe_tickets_rsvp_form_full_name', [ $rsvp, 'rsvp_form_add_full_name' ] ) );
		$this->assertNotFalse( has_filter( 'tribe_tickets_rsvp_form_email', [ $rsvp, 'rsvp_form_add_email' ] ) );
	}

	/**
	 * @test
	 */
	public function test_unregister_removes_promoter_hooks(): void {
		global $wp_filter;

		$controller = $this->make_controller();

		// Count hooks before registration.
		$save_post_before = isset( $wp_filter['save_post_tribe_rsvp_tickets'] )
			? count( $wp_filter['save_post_tribe_rsvp_tickets']->callbacks, COUNT_RECURSIVE )
			: 0;
		$attendee_created_before = isset( $wp_filter['event_tickets_rsvp_attendee_created'] )
			? count( $wp_filter['event_tickets_rsvp_attendee_created']->callbacks, COUNT_RECURSIVE )
			: 0;

		$controller->register();

		// Verify Promoter hooks are registered (count increased).
		$save_post_after = isset( $wp_filter['save_post_tribe_rsvp_tickets'] )
			? count( $wp_filter['save_post_tribe_rsvp_tickets']->callbacks, COUNT_RECURSIVE )
			: 0;
		$attendee_created_after = isset( $wp_filter['event_tickets_rsvp_attendee_created'] )
			? count( $wp_filter['event_tickets_rsvp_attendee_created']->callbacks, COUNT_RECURSIVE )
			: 0;

		$this->assertGreaterThan( $save_post_before, $save_post_after );
		$this->assertGreaterThan( $attendee_created_before, $attendee_created_after );

		$controller->unregister();

		// Verify Promoter hooks are removed (count back to original).
		$save_post_unregistered = isset( $wp_filter['save_post_tribe_rsvp_tickets'] )
			? count( $wp_filter['save_post_tribe_rsvp_tickets']->callbacks, COUNT_RECURSIVE )
			: 0;
		$attendee_created_unregistered = isset( $wp_filter['event_tickets_rsvp_attendee_created'] )
			? count( $wp_filter['event_tickets_rsvp_attendee_created']->callbacks, COUNT_RECURSIVE )
			: 0;

		$this->assertEquals( $save_post_before, $save_post_unregistered );
		$this->assertEquals( $attendee_created_before, $attendee_created_unregistered );
	}

	/**
	 * @test
	 */
	public function test_unregister_removes_csv_importer_hooks(): void {
		global $wp_filter;

		$controller = $this->make_controller();

		// Count hooks before registration.
		$before = isset( $wp_filter['tribe_aggregator_record_activity_wakeup'] )
			? count( $wp_filter['tribe_aggregator_record_activity_wakeup']->callbacks, COUNT_RECURSIVE )
			: 0;

		$controller->register();

		// Verify CSV Importer hook is registered (count increased).
		$after = isset( $wp_filter['tribe_aggregator_record_activity_wakeup'] )
			? count( $wp_filter['tribe_aggregator_record_activity_wakeup']->callbacks, COUNT_RECURSIVE )
			: 0;

		$this->assertGreaterThan( $before, $after );

		$controller->unregister();

		// Verify CSV Importer hook is removed (count back to original).
		$unregistered = isset( $wp_filter['tribe_aggregator_record_activity_wakeup'] )
			? count( $wp_filter['tribe_aggregator_record_activity_wakeup']->callbacks, COUNT_RECURSIVE )
			: 0;

		$this->assertEquals( $before, $unregistered );
	}
}
