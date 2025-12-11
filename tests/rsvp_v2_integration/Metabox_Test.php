<?php

namespace TECTicketsRSVPV2Tests;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\RSVP\V2\Metabox;
use TEC\Tickets\RSVP\V2\Ticket;

class Metabox_Test extends WPTestCase {
	/**
	 * @var Metabox
	 */
	protected $metabox;

	protected function setUp(): void {
		parent::setUp();
		$this->metabox = tribe( Metabox::class );

		// Enable tickets for posts.
		tribe_update_option( 'ticket-enabled-post-types', [ 'post' ] );
	}

	public function test_metabox_id_constant_is_defined(): void {
		$this->assertSame( 'tec_tickets_rsvp_v2_metabox', Metabox::METABOX_ID );
	}

	public function test_nonce_constants_are_defined(): void {
		$this->assertSame( 'tec_tickets_rsvp_v2_save', Metabox::NONCE_ACTION );
		$this->assertSame( 'tec_tickets_rsvp_v2_nonce', Metabox::NONCE_FIELD );
	}

	public function test_should_render_metabox_with_enable_checkbox(): void {
		$post_id = static::factory()->post->create();
		$post    = get_post( $post_id );

		ob_start();
		$this->metabox->render_metabox( $post );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'tec_tickets_rsvp_v2_enabled', $output );
		$this->assertStringContainsString( 'Enable RSVP', $output );
	}

	public function test_should_render_metabox_with_name_field(): void {
		$post_id = static::factory()->post->create();
		$post    = get_post( $post_id );

		ob_start();
		$this->metabox->render_metabox( $post );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'tec_tickets_rsvp_v2_name', $output );
		$this->assertStringContainsString( 'RSVP Name', $output );
	}

	public function test_should_render_metabox_with_description_field(): void {
		$post_id = static::factory()->post->create();
		$post    = get_post( $post_id );

		ob_start();
		$this->metabox->render_metabox( $post );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'tec_tickets_rsvp_v2_description', $output );
		$this->assertStringContainsString( 'Description', $output );
	}

	public function test_should_render_metabox_with_capacity_field(): void {
		$post_id = static::factory()->post->create();
		$post    = get_post( $post_id );

		ob_start();
		$this->metabox->render_metabox( $post );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'tec_tickets_rsvp_v2_capacity', $output );
		$this->assertStringContainsString( 'Capacity', $output );
	}

	public function test_should_render_metabox_with_show_not_going_field(): void {
		$post_id = static::factory()->post->create();
		$post    = get_post( $post_id );

		ob_start();
		$this->metabox->render_metabox( $post );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'tec_tickets_rsvp_v2_show_not_going', $output );
		$this->assertStringContainsString( 'Not Going', $output );
	}

	public function test_should_include_nonce_field(): void {
		$post_id = static::factory()->post->create();
		$post    = get_post( $post_id );

		ob_start();
		$this->metabox->render_metabox( $post );
		$output = ob_get_clean();

		$this->assertStringContainsString( Metabox::NONCE_FIELD, $output );
	}

	public function test_should_fire_form_start_action(): void {
		$post_id = static::factory()->post->create();
		$post    = get_post( $post_id );

		$action_fired = false;
		add_action( 'tec_event_tickets_rsvp_form__start', function () use ( &$action_fired ) {
			$action_fired = true;
		} );

		ob_start();
		$this->metabox->render_metabox( $post );
		ob_get_clean();

		$this->assertTrue( $action_fired );
	}

	public function test_should_fire_bottom_action(): void {
		$post_id = static::factory()->post->create();
		$post    = get_post( $post_id );

		$action_fired = false;
		add_action( 'tec_event_tickets_rsvp_bottom', function () use ( &$action_fired ) {
			$action_fired = true;
		} );

		ob_start();
		$this->metabox->render_metabox( $post );
		ob_get_clean();

		$this->assertTrue( $action_fired );
	}

	public function test_should_create_ticket_when_enabled(): void {
		$post_id = static::factory()->post->create();

		// Set current user who can edit the post FIRST.
		$admin_id = static::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin_id );

		// Simulate form submission.
		$_POST[ Metabox::NONCE_FIELD ]            = wp_create_nonce( Metabox::NONCE_ACTION );
		$_POST['tec_tickets_rsvp_v2_enabled']     = '1';
		$_POST['tec_tickets_rsvp_v2_ticket_id']   = '0';
		$_POST['tec_tickets_rsvp_v2_name']        = 'Test RSVP';
		$_POST['tec_tickets_rsvp_v2_description'] = 'Test Description';
		$_POST['tec_tickets_rsvp_v2_capacity']    = '50';

		$this->metabox->save_metabox( $post_id );

		// Verify ticket was created.
		$ticket     = tribe( Ticket::class );
		$ticket_ids = $ticket->get_tickets_for_post( $post_id );

		$this->assertNotEmpty( $ticket_ids );
		$this->assertCount( 1, $ticket_ids );
	}

	public function test_should_not_save_without_valid_nonce(): void {
		$post_id = static::factory()->post->create();

		// Simulate form submission without nonce.
		$_POST['tec_tickets_rsvp_v2_enabled']   = '1';
		$_POST['tec_tickets_rsvp_v2_ticket_id'] = '0';
		$_POST['tec_tickets_rsvp_v2_name']      = 'Test RSVP';

		$this->metabox->save_metabox( $post_id );

		// Verify no ticket was created.
		$ticket     = tribe( Ticket::class );
		$ticket_ids = $ticket->get_tickets_for_post( $post_id );

		$this->assertEmpty( $ticket_ids );
	}

	public function test_should_delete_ticket_when_disabled(): void {
		$post_id = static::factory()->post->create();
		$ticket  = tribe( Ticket::class );

		// Create a ticket first.
		$ticket_id = $ticket->create( $post_id, [
			'name'     => 'Test RSVP',
			'capacity' => 50,
		] );

		// Set current user who can edit the post FIRST.
		$admin_id = static::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin_id );

		// Simulate form submission with disabled checkbox.
		$_POST[ Metabox::NONCE_FIELD ]          = wp_create_nonce( Metabox::NONCE_ACTION );
		$_POST['tec_tickets_rsvp_v2_ticket_id'] = $ticket_id;
		// Not setting 'tec_tickets_rsvp_v2_enabled' simulates unchecking the checkbox.

		$this->metabox->save_metabox( $post_id );

		// Verify ticket was deleted (trashed).
		$ticket_post = get_post( $ticket_id );
		$this->assertSame( 'trash', $ticket_post->post_status );
	}

	public function test_supported_post_types_filter(): void {
		// Start with default post types.
		$default_types = tribe_get_option( 'ticket-enabled-post-types', [] );

		add_filter( 'tec_tickets_rsvp_v2_supported_post_types', function ( $types ) {
			$types[] = 'page';
			return $types;
		} );

		$types = $this->metabox->get_supported_post_types();

		$this->assertContains( 'page', $types );
	}
}
