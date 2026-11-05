<?php

namespace TEC\Tickets\Recurring_Tickets\Editor;

use Codeception\TestCase\WPTestCase;
use TEC\Events_Pro\Custom_Tables\V1\Editors\Classic\Recurrence;
use TEC\Tickets\Event;
use TEC\Tickets\Recurring_Tickets\Admin\Tickets_By_Date;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Recurring_Tickets\Template_Guard;
use TEC\Tickets\Recurring_Tickets\Ticket_ID;
use TEC\Tickets\Tests\Recurring_Tickets\Recurrence_Tier_Check;
use TEC\Tickets\Tests\Recurring_Tickets\Row_Orders;
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
	use Row_Orders;

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

		$this->assertSame( 1, preg_match( '#<div id="tec-tickets-by-date" class="tec-tickets-by-date-root" data-tickets-by-date="([^"]+)"></div>#', $html, $matches ) );
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
		$this->assertStringContainsString( 'Recurring Event Tickets', $html );
		$this->assertStringContainsString( 'These tickets apply to all dates in the recurring event', $html );
		$this->assertStringContainsString( 'Add a Ticket', $html );
		$this->assertStringNotContainsString( 'display: none', $this->header( $html ) );
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

		$this->assertStringContainsString( 'display: none', $this->header( $this->new_ticket_buttons( $event ) ) );
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
	 * The panel lists a recurring event's tickets for all its dates: one date's numbers read as if nothing sold.
	 *
	 * @test
	 */
	public function it_should_count_each_tickets_numbers_over_every_date_as_the_attendees_page_does(): void {
		$this->skip_without_recurrence_tier();

		$event  = $this->create_recurring_event();
		$shared = $this->create_tc_ticket( $event, 10, [ 'tribe-ticket' => [ 'mode' => 'global', 'event_capacity' => 10, 'capacity' => 10 ] ] );
		$own    = $this->create_tc_ticket( $event, 20, [ 'tribe-ticket' => [ 'mode' => 'own', 'capacity' => 5 ] ] );
		$single = $this->create_tc_ticket( static::factory()->post->create(), 5 );
		$date   = $this->get_dates( $event )[0];
		$rows   = array_filter( tribe( Rows::class )->get_by_template( $shared ), static fn( $row ) => (int) $row->occurrence_id === (int) $date->occurrence_id );
		$this->create_row_order( [ Ticket_ID::from_row_id( (int) reset( $rows )->id ) => 2 ] );
		$this->forget_rows_read();

		$row = function ( int $ticket_id, int $post_id ): array {
			$ticket = tribe( Module::class )->get_ticket( $post_id, $ticket_id );

			ob_start();
			do_action( 'tec_tickets_editor_list_row_columns_' . $ticket->type(), $ticket, $post_id );

			return [
				apply_filters( 'tec_tickets_editor_list_row_capacity', $ticket->capacity(), $ticket, $post_id ),
				apply_filters( 'tec_tickets_editor_list_row_available', $ticket->available(), $ticket, $post_id ),
				trim( wp_strip_all_tags( (string) ob_get_clean() ) ),
			];
		};

		// Pools of 10 on each of the 3 dates, 2 sold; 5 of its own on each.
		$this->assertSame( [ 3 * 10, 3 * 10 - 2, 'Sold: 2' ], $row( $shared, $event ) );
		$this->assertSame( [ 3 * 5, 3 * 5, 'Sold: 0' ], $row( $own, $event ) );

		$this->assertSame( 3 * 10 + 3 * 5, apply_filters( 'tec_tickets_editor_total_capacity', 10 + 5, $event ), 'The total under them counts every date too.' );

		$single_ticket = tribe( Module::class )->get_ticket( get_post_meta( $single, '_tec_tickets_commerce_event', true ), $single );
		$this->assertSame( $single_ticket->capacity(), apply_filters( 'tec_tickets_editor_list_row_capacity', $single_ticket->capacity(), $single_ticket, 0 ), 'Other tickets keep their own numbers.' );
		$this->assertSame( 5, apply_filters( 'tec_tickets_editor_total_capacity', 5, (int) $single_ticket->get_event_id() ), 'Other posts keep their total.' );
	}

	/**
	 * @test
	 */
	public function it_should_title_the_list_of_recurring_event_tickets(): void {
		$this->skip_without_recurrence_tier();

		$table_data = apply_filters( 'tec_tickets_editor_list_table_data_' . Template_Guard::TICKET_TYPE, [ 'ticket_type' => Template_Guard::TICKET_TYPE ] );

		$this->assertSame( tribe_get_ticket_label_plural( 'recurring_tickets_list_title' ), $table_data['table_title'] ?? null );
	}

	/**
	 * @test
	 */
	public function it_should_leave_saying_what_a_recurring_event_offers_to_the_head_of_its_tickets(): void {
		$this->skip_without_recurrence_tier();

		ob_start();
		tribe( Warnings::class )->render_hidden_recurring_warning_for_ticket_meta_box( $this->create_recurring_event() );
		$html = (string) ob_get_clean();

		$this->assertStringNotContainsString( 'Tickets you add here are offered on every date', $html );
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
	public function it_should_show_a_shared_recurring_event_tickets_capacity_in_parentheses(): void {
		$event = $this->create_recurring_event();
		$this->create_tc_ticket( $event, 10, [ 'tribe-ticket' => [ 'mode' => 'global', 'event_capacity' => 100, 'capacity' => 100 ] ] );
		set_current_screen( 'tribe_events' );

		try {
			ob_start();
			tribe( 'tickets.metabox' )->render( $event );
			$html = (string) ob_get_clean();
		} finally {
			set_current_screen( 'front' );
		}

		// A pool of 100 on each of the 3 dates.
		$this->assertStringContainsString( '(300)', $html );
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

		// Decoded: WordPress versions differ on how kses encodes the link's ampersand.
		$this->assertStringContainsString( 'This event is part of the <a href="' . get_edit_post_link( $series, 'raw' ) . '"', html_entity_decode( $html ) );
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
	 * @return string The opening tag of the head of the recurring event tickets.
	 */
	private function header( string $html ): string {
		preg_match( '/<div[^>]*id="tec-tickets-recurring-header"[^>]*>/s', $html, $matches );

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
