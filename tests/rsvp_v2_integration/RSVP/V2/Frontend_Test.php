<?php

namespace TEC\Tickets\RSVP\V2;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;
use Tribe__Tickets__Editor__Template as Tickets_Editor_Template;

/**
 * Tests for Frontend.
 *
 * These integration tests verify that Frontend filters work correctly
 * by applying the filters rather than calling methods directly.
 */
class Frontend_Test extends WPTestCase {
	use Ticket_Maker;

	/**
	 * Get the tickets editor template instance.
	 *
	 * @return Tickets_Editor_Template
	 */
	private function get_template(): Tickets_Editor_Template {
		return tribe( 'tickets.editor.template' );
	}

	public function test_should_prevent_rsvp_attendees_template_render(): void {
		$template = $this->get_template();
		$html     = $template->template( 'v2/commerce/rsvp/attendees', [], false );

		$this->assertFalse( $html, 'RSVP attendees template should render empty string' );
	}

	public function test_should_prevent_rsvp_attendee_template_render(): void {
		$template = $this->get_template();
		$html     = $template->template( 'v2/commerce/rsvp/attendees/attendee', [], false );

		$this->assertFalse( $html, 'RSVP attendee template should render empty string' );
	}

	public function test_should_prevent_rsvp_attendee_name_template_render(): void {
		$template = $this->get_template();
		$html     = $template->template( 'v2/commerce/rsvp/attendees/attendee/name', [], false );

		$this->assertFalse( $html, 'RSVP attendee name template should render empty string' );
	}

	public function test_should_prevent_rsvp_attendee_rsvp_template_render(): void {
		$template = $this->get_template();
		$html     = $template->template( 'v2/commerce/rsvp/attendees/attendee/rsvp', [], false );

		$this->assertFalse( $html, 'RSVP attendee rsvp template should render empty string' );
	}

	public function test_should_prevent_rsvp_attendees_title_template_render(): void {
		$template = $this->get_template();
		$html     = $template->template( 'v2/commerce/rsvp/attendees/title', [], false );

		$this->assertFalse( $html, 'RSVP attendees title template should render empty string' );
	}

	public function test_should_return_original_content_when_no_tc_rsvp_tickets(): void {
		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$post    = get_post( $post_id );

		// Create a regular TC ticket (not RSVP).
		$this->create_tc_ticket( $post_id, 10 );

		$template = $this->get_template();
		$args     = [
			'active_rsvps' => [],
		];

		$result = apply_filters(
			'tec_tickets_front_end_rsvp_form_template_content',
			'original content',
			$args,
			$template,
			$post,
			false
		);

		$this->assertSame( 'original content', $result, 'Should return original content when no TC-RSVP tickets' );
	}

	public function test_should_append_content_when_tc_rsvp_ticket_exists(): void {
		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$post    = get_post( $post_id );

		// Create a TC-RSVP ticket.
		$rsvp_ticket_id = $this->create_tc_rsvp_ticket( $post_id );
		$rsvp_ticket    = tribe( Module::class )->get_ticket( $post_id, $rsvp_ticket_id );

		$template = $this->get_template();
		// This would be set in the global context in the real application.
		$template->set( 'threshold', 10 );
		$args     = [
			'active_rsvps' => [ $rsvp_ticket ],
		];

		$result = apply_filters(
			'tec_tickets_front_end_rsvp_form_template_content',
			'original content',
			$args,
			$template,
			$post,
			false
		);

		$this->assertStringStartsWith( 'original content', $result, 'Should preserve original content' );
	}

	public function test_do_not_display_rsvp_v1_form_should_remove_hooks_for_rsvp_handler(): void {
		$frontend = tribe( Frontend::class );

		// Get the RSVP V1 handler.
		$rsvp_handler     = tribe( 'tickets.rsvp' );
		$ticket_form_hook = 'test_ticket_form_hook';

		// Add the hooks that should be removed.
		add_action( $ticket_form_hook, [ $rsvp_handler, 'maybe_add_front_end_tickets_form' ], 5 );
		add_filter( $ticket_form_hook, [ $rsvp_handler, 'show_tickets_unavailable_message' ], 6 );
		add_filter( 'the_content', [ $rsvp_handler, 'front_end_tickets_form_in_content' ], 11 );
		add_filter( 'the_content', [ $rsvp_handler, 'show_tickets_unavailable_message_in_content' ], 12 );

		// Call the method.
		$frontend->do_not_display_rsvp_v1_tickets_form( $rsvp_handler, $ticket_form_hook );

		// Verify hooks were removed.
		$this->assertFalse(
			has_action( $ticket_form_hook, [ $rsvp_handler, 'maybe_add_front_end_tickets_form' ] ),
			'maybe_add_front_end_tickets_form should be removed'
		);
		$this->assertFalse(
			has_filter( $ticket_form_hook, [ $rsvp_handler, 'show_tickets_unavailable_message' ] ),
			'show_tickets_unavailable_message should be removed'
		);
		$this->assertFalse(
			has_filter( 'the_content', [ $rsvp_handler, 'front_end_tickets_form_in_content' ] ),
			'front_end_tickets_form_in_content should be removed'
		);
		$this->assertFalse(
			has_filter( 'the_content', [ $rsvp_handler, 'show_tickets_unavailable_message_in_content' ] ),
			'show_tickets_unavailable_message_in_content should be removed'
		);
	}

	public function test_do_not_display_rsvp_v1_form_should_not_affect_non_rsvp_handlers(): void {
		$frontend = tribe( Frontend::class );

		// Use a non-RSVP tickets handler (e.g., TC module).
		$tc_handler       = tribe( Module::class );
		$ticket_form_hook = 'test_ticket_form_hook_2';

		// Add a hook to verify it's not removed.
		add_action( $ticket_form_hook, [ $tc_handler, 'get_tickets' ], 5 );

		// Call the method with non-RSVP handler.
		$frontend->do_not_display_rsvp_v1_tickets_form( $tc_handler, $ticket_form_hook );

		// Verify hook was NOT removed (method should return early).
		$this->assertNotFalse(
			has_action( $ticket_form_hook, [ $tc_handler, 'get_tickets' ] ),
			'Hooks should not be removed for non-RSVP handlers'
		);
	}
}
