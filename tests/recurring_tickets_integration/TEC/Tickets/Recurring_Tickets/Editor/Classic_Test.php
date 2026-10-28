<?php

namespace TEC\Tickets\Recurring_Tickets\Editor;

use Codeception\TestCase\WPTestCase;
use TEC\Events_Pro\Custom_Tables\V1\Editors\Classic\Recurrence;
use TEC\Tickets\Event;
use TEC\Tickets\Recurring_Tickets\Admin\Tickets_By_Date;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;
use TEC\Tickets\Recurring_Tickets\Template_Guard;
use TEC\Tickets\Recurring_Tickets\Ticket_ID;
use TEC\Tickets\Tests\Recurring_Tickets\Recurrence_Tier_Check;
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
	use Recurrence_Tier_Check;

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
	public function it_should_print_tickets_by_date_under_a_recurring_events_tickets(): void {
		$this->skip_without_recurrence_tier();
		$event    = $this->create_recurring_event();
		$template = $this->create_tc_ticket( $event, 10 );
		// As the ticket editor saves a ticket on a recurring event.
		update_post_meta( $template, '_type', Template_Guard::TICKET_TYPE );

		ob_start();
		do_action( 'tribe_events_tickets_after_new_ticket_panel', $event );
		$html = (string) ob_get_clean();

		$this->assertSame( 1, preg_match( '#<div class="tec-tickets-by-date-root" data-tickets-by-date="([^"]+)"></div>#', $html, $matches ) );
		$this->assertSame( tribe( Tickets_By_Date::class )->for_event( $event ), json_decode( html_entity_decode( $matches[1], ENT_QUOTES ), true ) );
	}

	/**
	 * @test
	 */
	public function it_should_print_no_tickets_by_date_without_recurring_event_tickets(): void {
		$this->skip_without_recurrence_tier();
		$single = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		$this->create_tc_ticket( $single, 10 );

		foreach ( [ $single, $this->create_recurring_event() ] as $post_id ) {
			ob_start();
			do_action( 'tribe_events_tickets_after_new_ticket_panel', $post_id );

			$this->assertStringNotContainsString( 'tec-tickets-by-date-root', (string) ob_get_clean() );
		}
	}

	/**
	 * @test
	 */
	public function it_should_offer_a_recurring_event_ticket_on_a_recurring_event(): void {
		$this->skip_without_recurrence_tier();

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
		$this->skip_without_recurrence_tier();

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
	public function it_should_not_offer_it_on_a_seated_event(): void {
		$event = $this->create_recurring_event();
		update_post_meta( $event, '_tec_slr_layout', 'some-layout' );

		$this->assertStringNotContainsString( 'recurring_ticket_form_toggle', $this->new_ticket_buttons( $event ) );
		$this->assertFalse( tribe( Classic::class )->offers_recurring_tickets( $event ) );
		$this->assertTrue( tribe( Classic::class )->offers_recurring_tickets( $this->create_recurring_event() ) );
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
		$this->skip_without_recurrence_tier();

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
		$this->skip_without_recurrence_tier();

		$table_data = apply_filters( 'tec_tickets_editor_list_table_data_' . Template_Guard::TICKET_TYPE, [ 'ticket_type' => Template_Guard::TICKET_TYPE ] );

		$this->assertSame( 'Recurring event tickets', $table_data['table_title'] ?? null );
	}

	/**
	 * @test
	 */
	public function it_should_say_what_a_recurring_event_offers_in_place_of_standard_tickets(): void {
		$this->skip_without_recurrence_tier();

		ob_start();
		tribe( Warnings::class )->render_hidden_recurring_warning_for_ticket_meta_box( $this->create_recurring_event() );
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'Tickets you add here are offered on every date of this event. Each date has its own capacity.', $html );
		$this->assertStringContainsString( 'You can&#039;t add standard tickets or RSVPs to a recurring event.', $html );
		$this->assertStringNotContainsString( 'Standard tickets are not yet supported', $html );
	}

	/**
	 * @test
	 */
	public function it_should_list_the_events_recurring_event_tickets_on_a_dates_screen(): void {
		$this->skip_without_recurrence_tier();

		$event    = $this->create_recurring_event();
		$template = $this->create_tc_ticket( $event, 10 );
		// As the ticket editor saves a ticket on a recurring event.
		update_post_meta( $template, '_type', Template_Guard::TICKET_TYPE );
		$date = (int) $this->get_dates( $event )[1]->provisional_id;
		$row  = Ticket_ID::from_row_id( (int) tribe( Rows::class )->get_by_occurrence( (int) $this->get_dates( $event )[1]->occurrence_id )[0]->id );
		set_current_screen( 'tribe_events' );

		try {
			// As the date's edit screen renders the panel.
			ob_start();
			tribe( 'tickets.metabox' )->render( $date );
			$rendered = (string) ob_get_clean();

			// As the panel reloads when a ticket is edited.
			$_POST['post_id'] = $date;
			$reloaded         = implode( '', (array) tribe( 'tickets.metabox' )->get_panels( Event::filter_event_id( $date, 'tickets-metabox-render' ) ) );
		} finally {
			unset( $_POST['post_id'] );
			set_current_screen( 'front' );
		}

		foreach ( [ $rendered, $reloaded ] as $html ) {
			$this->assertStringContainsString( 'order_' . $template . '"', $html, 'The ticket a save can update.' );
			$this->assertStringNotContainsString( 'order_' . $row . '"', $html );
		}
	}

	/**
	 * @test
	 */
	public function it_should_say_what_to_do_with_an_event_that_has_standard_tickets_in_place_of_its_recurrence_rules(): void {
		$this->skip_without_recurrence_tier();

		ob_start();
		tribe( Recurrence::class )->add_recurrence_not_supported_with_tickets_message( '' );
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'This event has tickets or RSVPs, so it can&#039;t be made a recurring event. To sell tickets for every date, create a new recurring event and add recurring event tickets there.', $html );
	}

	/**
	 * @test
	 */
	public function it_should_say_where_to_sell_series_passes_for_a_recurring_event_in_a_series(): void {
		$this->skip_without_recurrence_tier();

		$event  = $this->create_recurring_event();
		$series = tec_series()->where( 'event_post_id', $event )->first();

		ob_start();
		tribe( Warnings::class )->render_hidden_recurring_warning_for_ticket_meta_box( $event );
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'This event is part of the <a href="' . esc_url( get_edit_post_link( $series ) ) . '"', $html );
		$this->assertStringContainsString( 'To sell a pass that covers every event in the Series, add Series Passes on the Series edit screen.', $html );
		$this->assertStringNotContainsString( 'Series admin', $html );
	}

	/**
	 * @test
	 */
	public function it_should_not_warn_about_recurring_tickets_when_every_ticket_is_a_recurring_event_ticket(): void {
		$this->skip_without_recurrence_tier();

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
