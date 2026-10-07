<?php

namespace TEC\Tickets\Recurring_Tickets\Editor;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Recurring_Tickets\Template_Guard;
use TEC\Tickets\Tests\Recurring_Tickets\Ticket_Rows;
use Tribe\Tickets\Editor\Warnings;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Admin__Notices as Notices;

/**
 * The classic editor creates recurring event tickets on a recurring event.
 */
class Classic_Test extends WPTestCase {
	use Ticket_Rows;
	use Ticket_Maker;

	/**
	 * @before
	 */
	public function edit_as_an_editor(): void {
		// An administrator's first admin screen redirects to TEC's first-time setup, and exits.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'editor' ] ) );
		set_current_screen( 'edit-post' );
	}

	/**
	 * @after
	 */
	public function leave_the_admin(): void {
		set_current_screen( 'front' );
		unset( $_GET['post'] );
	}

	/**
	 * @test
	 */
	public function it_should_offer_a_recurring_event_ticket_on_a_recurring_event(): void {
		$html = $this->new_ticket_buttons( $this->create_recurring_event() );

		$this->assertStringContainsString( 'id="recurring_ticket_form_toggle"', $html );
		$this->assertStringContainsString( 'data-ticket-type="recurring"', $html );
		$this->assertStringContainsString( 'New recurring event ticket', $html );
		$this->assertStringNotContainsString( 'display: none', $this->button( $html ) );
	}

	/**
	 * @test
	 */
	public function it_should_render_the_button_hidden_on_a_single_event_for_when_it_recurs(): void {
		$event = tribe_events()->set_args(
			[
				'title'      => 'Single Event',
				'status'     => 'publish',
				'start_date' => '+1 week 10:00:00',
				'end_date'   => '+1 week 12:00:00',
			]
		)->create()->ID;

		$this->assertStringContainsString( 'display: none', $this->button( $this->new_ticket_buttons( $event ) ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_offer_it_on_a_post_that_is_not_an_event(): void {
		$this->assertStringNotContainsString( 'recurring_ticket_form_toggle', $this->new_ticket_buttons( static::factory()->post->create() ) );
	}

	/**
	 * @test
	 */
	public function it_should_head_a_recurring_event_tickets_form_with_its_type_and_the_number_of_dates(): void {
		$event = $this->create_recurring_event( 4 );
		$views = tribe( 'tickets.admin.views' );
		$views->set_values(
			[
				'ticket_type' => Template_Guard::TICKET_TYPE,
				'post_id'     => $event,
			],
			false
		);

		ob_start();
		do_action( 'tribe_template_before_include:tickets/admin-views/editor/panel/fields/dates', '', [ 'editor', 'panel', 'fields', 'dates' ], $views );
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'Recurring event ticket', $html );
		$this->assertStringContainsString( 'Sold for each date. 4 dates right now.', $html );
	}

	/**
	 * @test
	 */
	public function it_should_title_the_list_of_recurring_event_tickets(): void {
		$table_data = apply_filters( 'tec_tickets_editor_list_table_data_' . Template_Guard::TICKET_TYPE, [ 'ticket_type' => Template_Guard::TICKET_TYPE ] );

		$this->assertSame( 'Recurring event tickets', $table_data['table_title'] ?? null );
	}

	/**
	 * @test
	 */
	public function it_should_say_what_a_recurring_event_offers_in_place_of_standard_tickets(): void {
		ob_start();
		tribe( Warnings::class )->render_hidden_recurring_warning_for_ticket_meta_box( $this->create_recurring_event() );
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'Recurring events sell recurring event tickets', $html );
		$this->assertStringNotContainsString( 'Standard tickets are not yet supported', $html );
	}

	/**
	 * @test
	 */
	public function it_should_not_warn_about_recurring_tickets_when_every_ticket_is_a_recurring_event_ticket(): void {
		$event = $this->create_recurring_event();
		$this->create_tc_ticket( $event, 10 );

		$this->assertNull( $this->legacy_notice( $event ) );
	}

	/**
	 * @test
	 */
	public function it_should_still_warn_about_a_standard_ticket_on_a_recurring_event(): void {
		$event    = $this->create_recurring_event();
		$standard = $this->create_tc_ticket( $event, 10 );
		// A standard ticket made before the event recurred.
		update_post_meta( $standard, '_type', 'default' );

		$this->assertNotNull( $this->legacy_notice( $event ) );
	}

	/**
	 * @param int $post_id The post being edited.
	 *
	 * @return string The new ticket buttons of the tickets panel.
	 */
	private function new_ticket_buttons( int $post_id ): string {
		ob_start();
		do_action( 'tribe_events_tickets_new_ticket_buttons', $post_id );

		return (string) ob_get_clean();
	}

	/**
	 * @param string $html The new ticket buttons.
	 *
	 * @return string The opening tag of the recurring event ticket button.
	 */
	private function button( string $html ): string {
		preg_match( '/<button[^>]*id="recurring_ticket_form_toggle"[^>]*>/s', $html, $matches );

		return $matches[0] ?? '';
	}

	/**
	 * Builds the admin notices of an event's edit screen.
	 *
	 * @param int $post_id The event.
	 *
	 * @return mixed The notice about tickets on recurring events, or null.
	 */
	private function legacy_notice( int $post_id ) {
		$_GET['post']                       = $post_id;
		$GLOBALS['wp_filter']['admin_init'] = new \WP_Hook();
		tribe( 'tickets.admin.notices' )->hook();
		// The same callback the Recurrence tier hooks, before the notice's.
		add_action( 'admin_init', tribe()->callback( Classic::class, 'hide_legacy_notice' ), 9 );
		do_action( 'admin_init' );

		return Notices::instance()->get( 'tribe_notice_classic_editor_ecp_recurring_tickets-' . $post_id );
	}
}
