<?php

namespace TEC\Tickets\Relative_Sale_Dates;

use Closure;
use DateTimeImmutable;
use DateTimeZone;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Generator;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes;
use TEC\Tickets\RSVP\V2\Constants as RSVP_V2_Constants;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\Classic_Ticket_Form;
use Tribe\Tickets\Test\Traits\Relative_Sale_Dates_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use Tribe__Tickets__Admin__Views as Admin_Views;

class Editor_Test extends Controller_Test_Case {
	use Classic_Ticket_Form;
	use Relative_Sale_Dates_Maker;
	use Ticket_Maker;
	use RSVP_Ticket_Maker;
	use With_Tickets_Commerce;

	/**
	 * The ids of the four date and time inputs the classic ticket form has always had.
	 *
	 * @var string[]
	 */
	private const DATE_INPUT_IDS = [ 'ticket_start_date', 'ticket_start_time', 'ticket_end_date', 'ticket_end_time' ];

	/**
	 * The event start the sale price tests use, in UTC.
	 *
	 * @var string
	 */
	private const EVENT_START = '2027-06-24 19:00:00';

	/**
	 * The id prefix the preset sales window tests render with, unlike the ticket form's `ticket_sales`.
	 *
	 * @var string
	 */
	private const PRESET_PREFIX = 'tec_tickets_preset_sales';

	/**
	 * The ids of the sale price inputs the classic ticket form has always had.
	 *
	 * @var string[]
	 */
	private const SALE_PRICE_INPUT_IDS = [ 'ticket_add_sale_price', 'ticket_sale_price', 'ticket_sale_start_date', 'ticket_sale_end_date' ];

	protected $controller_class = Controller::class;

	/**
	 * The classic ticket form is the one on the event edit screen in wp-admin.
	 *
	 * @before
	 */
	public function edit_the_event_in_wp_admin(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		set_current_screen( 'tribe_events' );
	}

	/**
	 * @after
	 */
	public function leave_wp_admin(): void {
		set_current_screen( 'front' );
		$_POST = [];
	}

	/**
	 * @test
	 */
	public function should_prefill_the_sales_window_options_from_the_stored_rule(): void {
		$this->make_controller()->register();
		$event_id  = $this->create_event( '2027-06-24 19:00:00' );
		$ticket_id = $this->create_tc_ticket( $event_id );
		$rule      = [
			'start' => [
				'mode'   => 'relative',
				'value'  => 3,
				'unit'   => DAY_IN_SECONDS,
				'anchor' => 'end',
			],
			'end'   => $this->relative( 1, HOUR_IN_SECONDS ),
		];
		tribe( Rule_Store::class )->save( $ticket_id, $rule );

		$form = $this->render_ticket_form( $event_id, $ticket_id );

		$this->assertSame( 'relative', $this->get_selected_value( $form, 'ticket_sales_start_mode' ) );
		$this->assertSame( '3', $this->get_element( $form, 'ticket_sales_start_value' )->getAttribute( 'value' ) );
		$this->assertSame( DAY_IN_SECONDS, absint( $this->get_selected_value( $form, 'ticket_sales_start_unit' ) ) );
		$this->assertSame( 'end', $this->get_selected_value( $form, 'ticket_sales_start_anchor' ) );
		$this->assertSame( 'relative', $this->get_selected_value( $form, 'ticket_sales_end_mode' ) );
		$this->assertSame( '1', $this->get_element( $form, 'ticket_sales_end_value' )->getAttribute( 'value' ) );
		$this->assertSame( HOUR_IN_SECONDS, absint( $this->get_selected_value( $form, 'ticket_sales_end_unit' ) ) );
		$this->assertSame( 'start', $this->get_selected_value( $form, 'ticket_sales_end_anchor' ) );
		$this->assertSame( $rule, json_decode( $this->get_element( $form, 'ticket_relative_sale_dates' )->getAttribute( 'value' ), true ) );
	}

	/**
	 * @test
	 */
	public function should_name_each_unit_in_the_plural_form_of_the_number(): void {
		$this->make_controller()->register();
		$event_id  = $this->create_event( '2027-06-24 19:00:00' );
		$ticket_id = $this->create_tc_ticket( $event_id );
		tribe( Rule_Store::class )->save( $ticket_id, [ 'start' => $this->relative( 1, DAY_IN_SECONDS ), 'end' => $this->relative( 3, HOUR_IN_SECONDS ) ] );

		$form = $this->render_ticket_form( $event_id, $ticket_id );

		$this->assertSame( [ 'minute', 'hour', 'day', 'week' ], $this->get_option_labels( $form, 'ticket_sales_start_unit' ) );
		$this->assertSame( [ 'minutes', 'hours', 'days', 'weeks' ], $this->get_option_labels( $form, 'ticket_sales_end_unit' ) );
	}

	/**
	 * The script names the units again as the number changes, with this msgid and context.
	 *
	 * @test
	 */
	public function should_name_each_unit_with_the_translation_the_script_uses(): void {
		$this->make_controller()->register();
		$event_id  = $this->create_event( '2027-06-24 19:00:00' );
		$ticket_id = $this->create_tc_ticket( $event_id );
		tribe( Rule_Store::class )->save( $ticket_id, [ 'start' => $this->relative( 1, WEEK_IN_SECONDS ), 'end' => [ 'mode' => 'default' ] ] );
		add_filter(
			'ngettext_with_context_event-tickets',
			static fn( $translation, $single, $plural, $number, $context ) => 'week' === $single && 'Unit of a relative ticket sale date.' === $context ? 'semana' : $translation,
			10,
			5
		);

		$form = $this->render_ticket_form( $event_id, $ticket_id );

		$this->assertContains( 'semana', $this->get_option_labels( $form, 'ticket_sales_start_unit' ) );
	}

	/**
	 * @test
	 */
	public function should_leave_the_sales_window_options_out_of_the_submitted_fields(): void {
		$this->make_controller()->register();
		$event_id = $this->create_event( '2027-06-24 19:00:00' );

		$form = $this->render_ticket_form( $event_id );

		foreach ( [ 'start', 'end' ] as $end ) {
			foreach ( [ 'mode', 'value', 'unit', 'anchor' ] as $field ) {
				$this->assertFalse( $this->get_element( $form, "ticket_sales_{$end}_{$field}" )->hasAttribute( 'name' ), "ticket_sales_{$end}_{$field}" );
			}
		}
		$hidden = $this->get_element( $form, 'ticket_relative_sale_dates' );
		$this->assertSame( 'hidden', $hidden->getAttribute( 'type' ) );
		$this->assertSame( 'relative_sale_dates', $hidden->getAttribute( 'name' ) );
	}

	/**
	 * @test
	 */
	public function should_open_a_new_ticket_on_now_and_when_the_event_starts(): void {
		$this->make_controller()->register();
		$event_id = $this->create_event( '2027-06-24 19:00:00' );

		$form = $this->render_ticket_form( $event_id );

		$this->assertSame( 'default', $this->get_selected_value( $form, 'ticket_sales_start_mode' ) );
		$this->assertSame( 'default', $this->get_selected_value( $form, 'ticket_sales_end_mode' ) );
		$this->assertSame( '', $this->get_element( $form, 'ticket_relative_sale_dates' )->getAttribute( 'value' ) );
	}

	/**
	 * @test
	 */
	public function should_offer_the_relative_defaults_of_two_weeks_and_one_hour_before_the_event_starts(): void {
		$this->make_controller()->register();
		$event_id = $this->create_event( '2027-06-24 19:00:00' );

		$form = $this->render_ticket_form( $event_id );

		$this->assertSame( '2', $this->get_element( $form, 'ticket_sales_start_value' )->getAttribute( 'value' ) );
		$this->assertSame( WEEK_IN_SECONDS, absint( $this->get_selected_value( $form, 'ticket_sales_start_unit' ) ) );
		$this->assertSame( 'start', $this->get_selected_value( $form, 'ticket_sales_start_anchor' ) );
		$this->assertSame( '1', $this->get_element( $form, 'ticket_sales_end_value' )->getAttribute( 'value' ) );
		$this->assertSame( HOUR_IN_SECONDS, absint( $this->get_selected_value( $form, 'ticket_sales_end_unit' ) ) );
		$this->assertSame( 'start', $this->get_selected_value( $form, 'ticket_sales_end_anchor' ) );
	}

	/**
	 * @test
	 */
	public function should_open_a_ticket_without_a_rule_on_its_specific_dates(): void {
		$this->make_controller()->register();
		$event_id  = $this->create_event( '2027-06-24 19:00:00' );
		$ticket_id = $this->create_tc_ticket( $event_id );

		$form = $this->render_ticket_form( $event_id, $ticket_id );

		$this->assertSame( 'specific', $this->get_selected_value( $form, 'ticket_sales_start_mode' ) );
		$this->assertSame( 'specific', $this->get_selected_value( $form, 'ticket_sales_end_mode' ) );
	}

	/**
	 * A ticket created on a page and moved to an event, or created by code, can have no end date. Saving it without one
	 * gave it the event start, which the default end still does.
	 *
	 * @test
	 */
	public function should_open_a_saved_ticket_without_an_end_date_on_when_the_event_starts(): void {
		$this->make_controller()->register();
		$event_start = new DateTimeImmutable( '2027-06-24 19:00:00' );
		$event_id    = $this->create_event( $event_start->format( 'Y-m-d H:i:s' ) );
		$ticket_id   = $this->create_tc_ticket( $event_id );
		delete_post_meta( $ticket_id, '_ticket_end_date' );
		delete_post_meta( $ticket_id, '_ticket_end_time' );
		clean_post_cache( $ticket_id );

		$form = $this->render_ticket_form( $event_id, $ticket_id );

		$this->assertSame( 'specific', $this->get_selected_value( $form, 'ticket_sales_start_mode' ) );
		$this->assertSame( 'default', $this->get_selected_value( $form, 'ticket_sales_end_mode' ) );

		$fields      = $this->serialize_form( $form );
		$rule        = [ 'start' => [ 'mode' => 'specific' ], 'end' => [ 'mode' => 'default' ] ];
		$rule_fields = array_keys( array_filter( $fields, static fn( array $field ): bool => 'relative_sale_dates' === $field[0] ) );
		// What the script writes from the selects the form opened on.
		$fields[ reset( $rule_fields ) ][1] = wp_json_encode( $rule );

		$response = $this->send_classic_ticket_form( $event_id, $this->to_query_string( $fields ) );

		$this->assertTrue( $response['success'] );
		$this->assertSame( [ $event_start->format( 'Y-m-d' ), $event_start->format( 'H:i:s' ) ], $this->get_ticket_end( $ticket_id ) );
	}

	/**
	 * @test
	 */
	public function should_show_the_date_inputs_only_for_a_specific_date_and_the_helper_text_only_for_a_relative_one(): void {
		$this->make_controller()->register();
		$event_id = $this->create_event( '2027-06-24 19:00:00' );

		$form = $this->render_ticket_form( $event_id );

		foreach ( [ 'start', 'end' ] as $end ) {
			$mode = $this->get_element( $form, "ticket_sales_{$end}_mode" );
			$this->assertContains( 'tribe-dependency', explode( ' ', $mode->getAttribute( 'class' ) ) );

			$specific = $this->get_dependent( $this->get_element( $form, "ticket_{$end}_date" ) );
			$this->assertSame( "#ticket_sales_{$end}_mode", $specific->getAttribute( 'data-depends' ) );
			$this->assertSame( 'specific', $specific->getAttribute( 'data-condition' ) );

			$relative = $this->get_dependent( $this->get_element( $form, "ticket_sales_{$end}_value" ) );
			$this->assertSame( "#ticket_sales_{$end}_mode", $relative->getAttribute( 'data-depends' ) );
			$this->assertSame( 'relative', $relative->getAttribute( 'data-condition' ) );
			$this->assertSame( $relative, $this->get_dependent( $this->get_element( $form, "ticket_sales_{$end}_helper" ) ) );
		}
	}

	/**
	 * @test
	 */
	public function should_describe_each_relative_field_with_its_helper_text(): void {
		$this->make_controller()->register();
		$event_id = $this->create_event( '2027-06-24 19:00:00' );

		$form = $this->render_ticket_form( $event_id );

		foreach ( [ 'start', 'end' ] as $end ) {
			foreach ( [ 'value', 'unit', 'anchor' ] as $field ) {
				$this->assertSame( "ticket_sales_{$end}_helper", $this->get_element( $form, "ticket_sales_{$end}_{$field}" )->getAttribute( 'aria-describedby' ) );
			}
		}
	}

	/**
	 * @test
	 */
	public function should_hold_an_alert_for_an_invalid_sales_window(): void {
		$this->make_controller()->register();

		$error = $this->get_element( $this->render_ticket_form( $this->create_event( '2027-06-24 19:00:00' ) ), 'ticket_sales_window_error' );

		$this->assertSame( 'alert', $error->getAttribute( 'role' ) );
		$this->assertSame( '', trim( $error->textContent ) );
	}

	/**
	 * @test
	 */
	public function should_hand_the_rule_and_the_stored_dates_to_the_tickets_list(): void {
		$this->make_controller()->register();
		$event_id = $this->create_event( '2027-06-24 19:00:00' );
		$rule     = [ 'start' => $this->relative( 2, WEEK_IN_SECONDS ), 'end' => [ 'mode' => 'default' ] ];
		$ruled    = $this->create_tc_ticket( $event_id, 1, [ 'relative_sale_dates' => wp_json_encode( $rule ) ] );
		$plain    = $this->create_tc_ticket( $event_id );

		$list = $this->render_tickets_list( $event_id );

		$dates = $this->get_available_dates( $list, $ruled );
		$this->assertSame( $rule, json_decode( $dates->getAttribute( 'data-relative-sale-dates' ), true ) );
		$this->assertSame( implode( ' ', $this->get_ticket_start( $ruled ) ), $dates->getAttribute( 'data-sale-start' ) );
		$this->assertSame( implode( ' ', $this->get_ticket_end( $ruled ) ), $dates->getAttribute( 'data-sale-end' ) );
		// Each row's template context is merged into the next one, so the rule must not carry over.
		$this->assertFalse( $this->get_available_dates( $list, $plain )->hasAttribute( 'data-relative-sale-dates' ) );
	}

	/**
	 * @test
	 */
	public function should_show_the_time_of_each_sales_date_in_the_tickets_list(): void {
		$this->make_controller()->register();
		$event_start = new DateTimeImmutable( '2027-06-24 19:00:00' );
		$event_id    = $this->create_event( $event_start->format( 'Y-m-d H:i:s' ) );
		$rule        = [ 'start' => $this->relative( 2, WEEK_IN_SECONDS ), 'end' => $this->relative( 1, HOUR_IN_SECONDS ) ];
		$ticket_id   = $this->create_tc_ticket( $event_id, 1, [ 'relative_sale_dates' => wp_json_encode( $rule ) ] );

		$dates = $this->get_available_dates( $this->render_tickets_list( $event_id ), $ticket_id );

		$expected = $event_start->modify( '-2 weeks' )->format( 'F j, Y g:i a' ) . ' - ' . $event_start->modify( '-1 hour' )->format( 'F j, Y g:i a' );
		$this->assertSame( $expected, trim( $dates->textContent ) );
	}

	/**
	 * 2:30 am does not exist in New York on the day the clocks spring forward; the classic script shows it as stored.
	 *
	 * @test
	 */
	public function should_show_a_sales_time_in_the_site_timezone_gap_as_stored(): void {
		update_option( 'timezone_string', 'America/New_York' );
		$this->make_controller()->register();
		$event_id   = $this->create_event( '2027-06-24 19:00:00', 'America/New_York' );
		$sale_start = new DateTimeImmutable( '2027-03-14 02:30:00' );
		$ticket_id  = $this->create_tc_ticket(
			$event_id,
			1,
			[
				'ticket_start_date' => $sale_start->format( 'Y-m-d' ),
				'ticket_start_time' => $sale_start->format( 'H:i:s' ),
			]
		);

		$dates = $this->get_available_dates( $this->render_tickets_list( $event_id ), $ticket_id );

		$this->assertStringStartsWith( $sale_start->format( 'F j, Y g:i a' ) . ' - ', trim( $dates->textContent ) );
	}

	/**
	 * @test
	 */
	public function should_show_the_sales_dates_in_the_scripts_fallback_formats_when_the_formats_are_blank(): void {
		$this->make_controller()->register();
		add_filter( 'tribe_date_format', '__return_empty_string' );
		add_filter( 'tribe_time_format', '__return_empty_string' );
		$event_id  = $this->create_event( '2027-06-24 19:00:00' );
		$ticket_id = $this->create_tc_ticket( $event_id );

		$dates = $this->get_available_dates( $this->render_tickets_list( $event_id ), $ticket_id );

		$start    = new DateTimeImmutable( implode( ' ', $this->get_ticket_start( $ticket_id ) ) );
		$end      = new DateTimeImmutable( implode( ' ', $this->get_ticket_end( $ticket_id ) ) );
		$expected = $start->format( 'F j, Y g:i a' ) . ' - ' . $end->format( 'F j, Y g:i a' );
		$this->assertSame( $expected, trim( $dates->textContent ) );
	}

	/**
	 * Sales dates are stored in the event timezone, here 12 hours ahead of the site's: read in the site's, a sale that
	 * started or ended 2 hours ago would still be 10 hours away.
	 *
	 * @test
	 */
	public function should_tell_an_on_sale_and_an_expired_ticket_apart_in_the_event_timezone(): void {
		update_option( 'timezone_string', 'America/Sao_Paulo' );
		$this->make_controller()->register();
		$event_now = new DateTimeImmutable( 'now', new DateTimeZone( 'Asia/Tokyo' ) );
		$event_id  = $this->create_event( $event_now->modify( '+60 days' )->format( 'Y-m-d H:i:s' ), 'Asia/Tokyo' );
		$on_sale   = $this->create_tc_ticket( $event_id, 1, $this->get_sales_window( $event_now->modify( '-2 hours' ), $event_now->modify( '+30 days' ) ) );
		$expired   = $this->create_tc_ticket( $event_id, 1, $this->get_sales_window( $event_now->modify( '-10 days' ), $event_now->modify( '-2 hours' ) ) );

		$list = $this->render_tickets_list( $event_id );

		$on_sale_classes = explode( ' ', $this->get_available_dates( $list, $on_sale )->getAttribute( 'class' ) );
		$this->assertContains( 'dashicons-clock', $on_sale_classes );
		$this->assertNotContains( 'tribe-tickets__tickets-editor-ticket-available-dates-icon-expired', $on_sale_classes );
		$this->assertContains( 'tribe-tickets__tickets-editor-ticket-available-dates-icon-expired', explode( ' ', $this->get_available_dates( $list, $expired )->getAttribute( 'class' ) ) );
	}

	/**
	 * Tickets Commerce caches a ticket with the ID it was loaded with, and an attendee's `product_id` meta is a string.
	 *
	 * @test
	 */
	public function should_list_a_ruled_ticket_first_loaded_with_a_numeric_string_id(): void {
		$this->make_controller()->register();
		$event_id  = $this->create_event( '2027-06-24 19:00:00' );
		$ticket_id = $this->create_ruled_ticket( $event_id );
		wp_cache_delete( $ticket_id, 'tec_tickets' );
		tribe( Ticket::class )->get_ticket( "{$ticket_id}" );

		$dates = $this->get_available_dates( $this->render_tickets_list( $event_id ), $ticket_id );

		$this->assertSame( tribe( Rule_Store::class )->get( $ticket_id ), json_decode( $dates->getAttribute( 'data-relative-sale-dates' ), true ) );
	}

	/**
	 * @test
	 */
	public function should_hand_on_a_tickets_list_context_an_earlier_callback_left_without_an_array(): void {
		$this->make_controller()->register();
		$hook = 'tribe_template_context:tickets/admin-views/editor/list-row/available-dates';
		add_filter( $hook, '__return_null', 5 );

		$this->assertNull( apply_filters( $hook, [] ) );
	}

	/**
	 * @test
	 */
	public function should_render_the_form_of_a_ticket_first_loaded_with_a_numeric_string_id(): void {
		$this->make_controller()->register();
		$event_id  = $this->create_event( '2027-06-24 19:00:00' );
		$ticket_id = $this->create_tc_ticket( $event_id );
		wp_cache_delete( $ticket_id, 'tec_tickets' );
		tribe( Ticket::class )->get_ticket( "{$ticket_id}" );

		$form = $this->render_ticket_form( $event_id, $ticket_id );

		$this->assertSame( 'specific', $this->get_selected_value( $form, 'ticket_sales_start_mode' ) );
		$this->assertSame( 'specific', $this->get_selected_value( $form, 'ticket_sales_end_mode' ) );
	}

	/**
	 * Third parties may compare the row markup byte for byte.
	 *
	 * @test
	 */
	public function should_keep_the_sale_dates_markup_of_a_ticket_without_a_rule(): void {
		$this->make_controller()->register();
		$event_id = $this->create_event( '2027-06-24 19:00:00' );
		$this->create_tc_ticket( $event_id );

		$list = tribe( 'tickets.metabox' )->get_panels( $event_id )['list'];

		$this->assertRegExp( '/<div  class="tribe-tickets__tickets-editor-ticket-available-dates [^"]*" >\n/', $list );
	}

	/**
	 * @test
	 */
	public function should_keep_what_other_code_renders_around_the_date_fields(): void {
		$this->make_controller()->register();
		$event_id = $this->create_event( '2027-06-24 19:00:00' );
		add_action(
			'tribe_template_after_include:tickets/admin-views/editor/panel/fields/dates',
			static function (): void {
				echo '<span id="after-the-date-fields"></span>';
			}
		);

		$form = $this->render_ticket_form( $event_id, $this->create_tc_ticket( $event_id ) );

		// The ticket type header is rendered before the date fields.
		$this->get_element( $form, 'ticket_type_options' );
		$this->get_element( $form, 'after-the-date-fields' );
	}

	/**
	 * @test
	 */
	public function should_keep_the_attributes_of_the_date_inputs(): void {
		$event_id  = $this->create_event( '2027-06-24 19:00:00' );
		$ticket_id = $this->create_tc_ticket( $event_id );
		$expected  = $this->get_input_attributes( $this->render_ticket_form( $event_id, $ticket_id ), self::DATE_INPUT_IDS );

		$this->make_controller()->register();
		$actual = $this->get_input_attributes( $this->render_ticket_form( $event_id, $ticket_id ), self::DATE_INPUT_IDS );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * @return Generator<string,array{0: Closure(): array{0: int, 1: int|null, 2: string|null}}>
	 */
	public function unchanged_form_provider(): Generator {
		yield 'ticket on a page' => [
			function (): array {
				$post_id = static::factory()->post->create( [ 'post_type' => 'page' ] );
				$this->enable_tickets_on( 'page' );

				return [ $post_id, $this->create_tc_ticket( $post_id ), null ];
			},
		];

		yield 'new ticket on a page' => [
			function (): array {
				$this->enable_tickets_on( 'page' );

				return [ static::factory()->post->create( [ 'post_type' => 'page' ] ), null, null ];
			},
		];

		yield 'new RSVP on an event' => [
			function (): array {
				return [ $this->create_event( '2027-06-24 19:00:00' ), null, 'rsvp' ];
			},
		];

		yield 'new ticket on an event without an active provider' => [
			function (): array {
				// The event still defaults to the Tickets Commerce provider, though no provider is active.
				add_filter( 'tribe_tickets_get_modules', '__return_empty_array', 20 );

				return [ $this->create_event( '2027-06-24 19:00:00' ), null, null ];
			},
		];

		yield 'new Series Pass' => [
			function (): array {
				return [ $this->create_event( '2027-06-24 19:00:00' ), null, Series_Passes::TICKET_TYPE ];
			},
		];

		yield 'RSVP V2 ticket on an event' => [
			function (): array {
				$event_id = $this->create_event( '2027-06-24 19:00:00' );

				return [ $event_id, $this->create_tc_ticket( $event_id, 0, [ 'ticket_type' => RSVP_V2_Constants::TC_RSVP_TYPE ] ), null ];
			},
		];

		yield 'ticket in a front-end form, such as Community Events\'' => [
			function (): array {
				$event_id = $this->create_event( '2027-06-24 19:00:00' );
				// What `tickets.js` sends outside wp-admin.
				$_POST['is_admin'] = 'false';

				return [ $event_id, $this->create_tc_ticket( $event_id ), null ];
			},
		];

		yield 'ticket with a sale price on a page' => [
			function (): array {
				$post_id = static::factory()->post->create( [ 'post_type' => 'page' ] );
				$this->enable_tickets_on( 'page' );

				return [ $post_id, $this->create_sale_price_ticket_up_to_the_event( $post_id ), null ];
			},
		];

		yield 'ticket with a sale price in a front-end form, such as Community Events\'' => [
			function (): array {
				$event_id = $this->create_event( self::EVENT_START );
				// What `tickets.js` sends outside wp-admin.
				$_POST['is_admin'] = 'false';

				return [ $event_id, $this->create_sale_price_ticket_up_to_the_event( $event_id ), null ];
			},
		];
	}

	/**
	 * @test
	 * @dataProvider unchanged_form_provider
	 */
	public function should_leave_the_ticket_form_unchanged( Closure $fixture ): void {
		[ $post_id, $ticket_id, $ticket_type ] = $fixture();
		$expected                              = $this->render_ticket_panel( $post_id, $ticket_id, $ticket_type );

		$this->make_controller()->register();

		$this->assertSame( $expected, $this->render_ticket_panel( $post_id, $ticket_id, $ticket_type ) );
	}

	/**
	 * An RSVP is not a Tickets Commerce ticket, so its panel offers neither window's options.
	 *
	 * @test
	 */
	public function should_leave_the_date_fields_of_an_rsvp_on_an_event_unchanged(): void {
		$event_id = $this->create_event( self::EVENT_START );
		$rsvp_id  = $this->create_rsvp_ticket( $event_id );
		$hook     = 'tribe_template_include_html:tickets/admin-views/editor/panel/fields/dates';
		$callback = $this->test_services->callback( Editor::class, 'render_sales_window_fields' );
		$this->make_controller()->register();
		remove_filter( $hook, $callback );
		$expected = $this->render_ticket_panel( $event_id, $rsvp_id );

		add_filter( $hook, $callback, 10, 4 );

		$this->assertSame( $expected, $this->render_ticket_panel( $event_id, $rsvp_id ) );
		$this->assertSame( 0, $this->render_ticket_form( $event_id, $rsvp_id )->query( "//*[@id='ticket_sale_start_mode']" )->length );
	}

	/**
	 * @test
	 */
	public function should_store_the_rule_the_classic_form_submits(): void {
		$this->make_controller()->register();
		$event_start = new DateTimeImmutable( '2027-06-24 19:00:00' );
		$event_id    = $this->create_event( $event_start->format( 'Y-m-d H:i:s' ) );
		$ticket_id   = $this->create_tc_ticket( $event_id );
		$rule        = [ 'start' => $this->relative( 2, WEEK_IN_SECONDS ), 'end' => [ 'mode' => 'default' ] ];
		$fields      = $this->serialize_form( $this->render_ticket_form( $event_id, $ticket_id ) );

		// The script writes the rule into the form's own field before `tickets.js` serializes the form.
		$rule_fields = array_keys( array_filter( $fields, static fn( array $field ): bool => 'relative_sale_dates' === $field[0] ) );
		$this->assertCount( 1, $rule_fields );
		$fields[ reset( $rule_fields ) ][1] = wp_json_encode( $rule );

		$response = $this->send_classic_ticket_form( $event_id, $this->to_query_string( $fields ) );

		$this->assertTrue( $response['success'] );
		$this->assertSame( $rule, tribe( Rule_Store::class )->get( $ticket_id ) );
		$expected_start = $event_start->modify( '-2 weeks' );
		$this->assertSame( [ $expected_start->format( 'Y-m-d' ), $expected_start->format( 'H:i:s' ) ], $this->get_ticket_start( $ticket_id ) );
	}

	/**
	 * @test
	 */
	public function should_keep_the_stored_rule_when_the_classic_form_is_saved_unchanged(): void {
		$this->make_controller()->register();
		$event_id  = $this->create_event( '2027-06-24 19:00:00' );
		$ticket_id = $this->create_tc_ticket( $event_id );
		$rule      = [ 'start' => $this->relative( 3, DAY_IN_SECONDS ), 'end' => $this->relative( 1, HOUR_IN_SECONDS ) ];
		tribe( Rule_Store::class )->save( $ticket_id, $rule );

		$fields   = $this->serialize_form( $this->render_ticket_form( $event_id, $ticket_id ) );
		$response = $this->send_classic_ticket_form( $event_id, $this->to_query_string( $fields ) );

		$this->assertTrue( $response['success'] );
		$this->assertSame( $rule, tribe( Rule_Store::class )->get( $ticket_id ) );
	}

	/**
	 * A front-end form has no sales window options, so the dates it sends are the ones the person set.
	 *
	 * @test
	 */
	public function should_drop_the_stored_rule_when_a_front_end_form_saves_the_ticket(): void {
		$this->make_controller()->register();
		$event_id  = $this->create_event( '2027-06-24 19:00:00' );
		$ticket_id = $this->create_tc_ticket( $event_id );
		tribe( Rule_Store::class )->save( $ticket_id, [ 'start' => $this->relative( 2, WEEK_IN_SECONDS ), 'end' => [ 'mode' => 'default' ] ] );
		$_POST['is_admin'] = 'false';
		$fields            = $this->serialize_form( $this->render_ticket_form( $event_id, $ticket_id ) );
		$fields[]          = [ 'ticket_start_date', '2027-05-01' ];
		$fields[]          = [ 'ticket_start_time', '10:00:00' ];

		$response = $this->send_classic_ticket_form( $event_id, $this->to_query_string( $fields ), false );

		$this->assertTrue( $response['success'] );
		$this->assertSame( [], tribe( Rule_Store::class )->get( $ticket_id ) );
		$this->assertSame( [ '2027-05-01', '10:00:00' ], $this->get_ticket_start( $ticket_id ) );
	}

	/**
	 * @test
	 */
	public function should_prefill_the_sale_price_options_from_the_stored_rule(): void {
		$this->make_controller()->register();
		$event_id  = $this->create_event( self::EVENT_START );
		$ticket_id = $this->create_sale_price_ticket_up_to_the_event( $event_id );
		$rule      = [ 'start' => $this->sale_price_relative( 10, DAY_IN_SECONDS ), 'end' => $this->sale_price_relative( 1, WEEK_IN_SECONDS ) ];
		$this->store_sale_price_rule( $ticket_id, $rule );

		$form = $this->render_ticket_form( $event_id, $ticket_id );

		$this->assertSame( 'relative', $this->get_selected_value( $form, 'ticket_sale_start_mode' ) );
		$this->assertSame( '10', $this->get_element( $form, 'ticket_sale_start_value' )->getAttribute( 'value' ) );
		$this->assertSame( DAY_IN_SECONDS, absint( $this->get_selected_value( $form, 'ticket_sale_start_unit' ) ) );
		$this->assertSame( 'relative', $this->get_selected_value( $form, 'ticket_sale_end_mode' ) );
		$this->assertSame( '1', $this->get_element( $form, 'ticket_sale_end_value' )->getAttribute( 'value' ) );
		$this->assertSame( WEEK_IN_SECONDS, absint( $this->get_selected_value( $form, 'ticket_sale_end_unit' ) ) );
		$this->assertSame( $rule, json_decode( $this->get_element( $form, 'ticket_sale_price_relative' )->getAttribute( 'value' ), true ) );
		$this->assertSame( '1', $this->get_element( $form, 'ticket_sale_price_relative' )->getAttribute( 'data-stored-rule' ) );
	}

	/**
	 * @test
	 */
	public function should_offer_the_relative_defaults_next_to_a_stored_sale_price_boundary_that_is_not_relative(): void {
		$this->make_controller()->register();
		$event_id  = $this->create_event( self::EVENT_START );
		$ticket_id = $this->create_sale_price_ticket_up_to_the_event( $event_id );
		$this->store_sale_price_rule( $ticket_id, [ 'start' => [ 'mode' => 'now' ], 'end' => [ 'mode' => 'specific' ] ] );

		$form = $this->render_ticket_form( $event_id, $ticket_id );

		$this->assertSame( 'now', $this->get_selected_value( $form, 'ticket_sale_start_mode' ) );
		$this->assertSame( '2', $this->get_element( $form, 'ticket_sale_start_value' )->getAttribute( 'value' ) );
		$this->assertSame( WEEK_IN_SECONDS, absint( $this->get_selected_value( $form, 'ticket_sale_start_unit' ) ) );
		$this->assertSame( 'specific', $this->get_selected_value( $form, 'ticket_sale_end_mode' ) );
		$this->assertSame( '1', $this->get_element( $form, 'ticket_sale_end_value' )->getAttribute( 'value' ) );
		$this->assertSame( WEEK_IN_SECONDS, absint( $this->get_selected_value( $form, 'ticket_sale_end_unit' ) ) );
	}

	/**
	 * @test
	 */
	public function should_open_the_sale_price_of_a_new_ticket_on_now_and_one_week_before_the_event_starts(): void {
		$this->make_controller()->register();

		$form = $this->render_ticket_form( $this->create_event( self::EVENT_START ) );

		$this->assertSame( 'now', $this->get_selected_value( $form, 'ticket_sale_start_mode' ) );
		$this->assertSame( '2', $this->get_element( $form, 'ticket_sale_start_value' )->getAttribute( 'value' ) );
		$this->assertSame( WEEK_IN_SECONDS, absint( $this->get_selected_value( $form, 'ticket_sale_start_unit' ) ) );
		$this->assertSame( 'relative', $this->get_selected_value( $form, 'ticket_sale_end_mode' ) );
		$this->assertSame( '1', $this->get_element( $form, 'ticket_sale_end_value' )->getAttribute( 'value' ) );
		$this->assertSame( WEEK_IN_SECONDS, absint( $this->get_selected_value( $form, 'ticket_sale_end_unit' ) ) );
		$this->assertSame( '', $this->get_element( $form, 'ticket_sale_price_relative' )->getAttribute( 'value' ) );
		$this->assertSame( '0', $this->get_element( $form, 'ticket_sale_price_relative' )->getAttribute( 'data-stored-rule' ) );
	}

	/**
	 * @test
	 */
	public function should_open_the_sale_price_of_a_saved_ticket_without_one_on_the_defaults(): void {
		$this->make_controller()->register();
		$event_id = $this->create_event( self::EVENT_START );

		$form = $this->render_ticket_form( $event_id, $this->create_tc_ticket( $event_id, 20 ) );

		$this->assertSame( 'now', $this->get_selected_value( $form, 'ticket_sale_start_mode' ) );
		$this->assertSame( 'relative', $this->get_selected_value( $form, 'ticket_sale_end_mode' ) );
	}

	/**
	 * @test
	 */
	public function should_open_a_sale_price_without_a_rule_on_its_specific_dates(): void {
		$this->make_controller()->register();
		$event_id = $this->create_event( self::EVENT_START );

		$form = $this->render_ticket_form( $event_id, $this->create_sale_price_ticket_up_to_the_event( $event_id ) );

		$this->assertSame( 'specific', $this->get_selected_value( $form, 'ticket_sale_start_mode' ) );
		$this->assertSame( 'specific', $this->get_selected_value( $form, 'ticket_sale_end_mode' ) );
		$this->assertSame( '', $this->get_element( $form, 'ticket_sale_price_relative' )->getAttribute( 'value' ) );
	}

	/**
	 * @test
	 */
	public function should_leave_the_sale_price_options_out_of_the_submitted_fields(): void {
		$this->make_controller()->register();

		$form = $this->render_ticket_form( $this->create_event( self::EVENT_START ) );

		foreach ( [ 'start', 'end' ] as $end ) {
			foreach ( [ 'mode', 'value', 'unit' ] as $field ) {
				$this->assertFalse( $this->get_element( $form, "ticket_sale_{$end}_{$field}" )->hasAttribute( 'name' ), "ticket_sale_{$end}_{$field}" );
			}
		}
		$hidden = $this->get_element( $form, 'ticket_sale_price_relative' );
		$this->assertSame( 'hidden', $hidden->getAttribute( 'type' ) );
		$this->assertSame( Window_Kind::sale_price()->get_rule_keys()['data'], $hidden->getAttribute( 'name' ) );
		// An unchecked sale price disables its dependents, so the rule is not sent without it.
		$this->assertSame( '#ticket_add_sale_price', $this->get_dependent( $hidden )->getAttribute( 'data-depends' ) );
	}

	/**
	 * @test
	 */
	public function should_offer_one_to_thirty_days_or_weeks_named_for_the_number(): void {
		$this->make_controller()->register();
		$event_id  = $this->create_event( self::EVENT_START );
		$ticket_id = $this->create_sale_price_ticket_up_to_the_event( $event_id );
		$this->store_sale_price_rule( $ticket_id, [ 'start' => $this->sale_price_relative( 3, WEEK_IN_SECONDS ), 'end' => $this->sale_price_relative( 1, DAY_IN_SECONDS ) ] );
		$kind = Window_Kind::sale_price();

		$form = $this->render_ticket_form( $event_id, $ticket_id );

		$this->assertSame( [ 'days', 'weeks' ], $this->get_option_labels( $form, 'ticket_sale_start_unit' ) );
		$this->assertSame( [ 'day', 'week' ], $this->get_option_labels( $form, 'ticket_sale_end_unit' ) );
		foreach ( [ 'start', 'end' ] as $end ) {
			$value = $this->get_element( $form, "ticket_sale_{$end}_value" );
			$this->assertSame(
				[ Boundary::MIN_VALUE, $kind->get_max_value() ],
				[ absint( $value->getAttribute( 'min' ) ), absint( $value->getAttribute( 'max' ) ) ]
			);
		}
	}

	/**
	 * The classic script names the units again as the number changes, with this msgid and context.
	 *
	 * @test
	 */
	public function should_name_each_sale_price_unit_with_the_translation_the_script_uses(): void {
		$this->make_controller()->register();
		$event_id  = $this->create_event( self::EVENT_START );
		$ticket_id = $this->create_sale_price_ticket_up_to_the_event( $event_id );
		$this->store_sale_price_rule( $ticket_id, [ 'start' => $this->sale_price_relative( 3, WEEK_IN_SECONDS ), 'end' => $this->sale_price_relative( 1, WEEK_IN_SECONDS ) ] );
		add_filter(
			'ngettext_with_context_event-tickets',
			static fn( $translation, $single, $plural, $number, $context ) => 'week' === $single && 'Unit of a relative ticket sale date.' === $context ? 'semana' : $translation,
			10,
			5
		);

		$form = $this->render_ticket_form( $event_id, $ticket_id );

		$this->assertContains( 'semana', $this->get_option_labels( $form, 'ticket_sale_end_unit' ) );
	}

	/**
	 * @test
	 */
	public function should_show_the_sale_price_dates_only_for_a_specific_date_and_the_relative_inputs_only_for_a_relative_one(): void {
		$this->make_controller()->register();

		$form = $this->render_ticket_form( $this->create_event( self::EVENT_START ) );

		foreach ( [ 'start', 'end' ] as $end ) {
			$mode = $this->get_element( $form, "ticket_sale_{$end}_mode" );
			$this->assertContains( 'tribe-dependency', explode( ' ', $mode->getAttribute( 'class' ) ) );

			$specific = $this->get_dependent( $this->get_element( $form, "ticket_sale_{$end}_date" ) );
			$this->assertSame( "#ticket_sale_{$end}_mode", $specific->getAttribute( 'data-depends' ) );
			$this->assertSame( 'specific', $specific->getAttribute( 'data-condition' ) );

			$relative = $this->get_dependent( $this->get_element( $form, "ticket_sale_{$end}_value" ) );
			$this->assertSame( "#ticket_sale_{$end}_mode", $relative->getAttribute( 'data-depends' ) );
			$this->assertSame( 'relative', $relative->getAttribute( 'data-condition' ) );
			$this->assertSame( $relative, $this->get_dependent( $this->get_element( $form, "ticket_sale_{$end}_unit" ) ) );
		}
	}

	/**
	 * @test
	 */
	public function should_give_every_sale_price_input_its_own_accessible_name(): void {
		$this->make_controller()->register();

		$form = $this->render_ticket_form( $this->create_event( self::EVENT_START ) );

		foreach ( [ 'ticket_sale_start_mode', 'ticket_sale_start_date', 'ticket_sale_end_mode', 'ticket_sale_end_date' ] as $id ) {
			$this->assertSame( 1, $form->query( "//label[@for='{$id}']" )->length, $id );
		}
		foreach ( [ 'ticket_sale_start_value', 'ticket_sale_start_unit', 'ticket_sale_end_value', 'ticket_sale_end_unit' ] as $id ) {
			$this->assertNotSame( '', $this->get_element( $form, $id )->getAttribute( 'aria-label' ), $id );
		}
		$this->assertSame( 0, $form->query( "//*[contains(@class, 'ticket_sale_price_wrapper')]//label[@for='ticket_start_date']" )->length );
	}

	/**
	 * @test
	 */
	public function should_hold_a_live_sale_length_under_the_sale_end_for_either_mode(): void {
		$this->make_controller()->register();

		$form = $this->render_ticket_form( $this->create_event( self::EVENT_START ) );

		$helper = $this->get_element( $form, 'ticket_sale_price_length' );
		$this->assertSame( 'polite', $helper->getAttribute( 'aria-live' ) );
		$this->assertSame( '', trim( $helper->textContent ) );
		// Only the sale price checkbox shows and hides it, whichever mode the end is in.
		$this->assertSame( '#ticket_add_sale_price', $this->get_dependent( $helper )->getAttribute( 'data-depends' ) );
		$this->assertSame( $helper->parentNode, $this->get_element( $form, 'ticket_sale_end_mode' )->parentNode );
	}

	/**
	 * @test
	 */
	public function should_hold_an_alert_for_an_invalid_sale_price_window(): void {
		$this->make_controller()->register();

		$form = $this->render_ticket_form( $this->create_event( self::EVENT_START ) );

		$error = $this->get_element( $form, 'ticket_sale_price_error' );
		$this->assertSame( 'alert', $error->getAttribute( 'role' ) );
		$this->assertSame( '', trim( $error->textContent ) );
		$this->assertSame( '#ticket_add_sale_price', $this->get_dependent( $error )->getAttribute( 'data-depends' ) );
	}

	/**
	 * @test
	 */
	public function should_keep_the_attributes_of_the_existing_sale_price_inputs(): void {
		$event_id  = $this->create_event( self::EVENT_START );
		$ticket_id = $this->create_sale_price_ticket_up_to_the_event( $event_id );
		$expected  = $this->get_input_attributes( $this->render_ticket_form( $event_id, $ticket_id ), self::SALE_PRICE_INPUT_IDS );

		$this->make_controller()->register();
		$actual = $this->get_input_attributes( $this->render_ticket_form( $event_id, $ticket_id ), self::SALE_PRICE_INPUT_IDS );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * @test
	 */
	public function should_keep_what_other_code_renders_around_the_sale_price_fields(): void {
		$this->make_controller()->register();
		$event_id = $this->create_event( self::EVENT_START );
		foreach ( [ 'before', 'after' ] as $position ) {
			add_action(
				"tribe_template_{$position}_include:tickets/admin-views/commerce/metabox/sale-price",
				static function () use ( $position ): void {
					echo '<span id="' . esc_attr( $position ) . '-the-sale-price-fields"></span>';
				}
			);
		}

		$form = $this->render_ticket_form( $event_id, $this->create_sale_price_ticket_up_to_the_event( $event_id ) );

		$this->get_element( $form, 'ticket_sale_start_mode' );
		$this->get_element( $form, 'before-the-sale-price-fields' );
		$this->get_element( $form, 'after-the-sale-price-fields' );
	}

	/**
	 * A front-end form shows the sale price dates as they are, so a save from it keeps them instead of the rule.
	 *
	 * @test
	 */
	public function should_drop_the_stored_sale_price_rule_when_a_front_end_form_saves_the_ticket(): void {
		$this->make_controller()->register();
		$event_id  = $this->create_event( self::EVENT_START );
		$ticket_id = $this->create_sale_price_ticket_up_to_the_event( $event_id );
		$this->store_sale_price_rule( $ticket_id, [ 'start' => $this->sale_price_relative( 2, WEEK_IN_SECONDS ), 'end' => $this->sale_price_relative( 1, WEEK_IN_SECONDS ) ] );
		$_POST['is_admin'] = 'false';
		$fields            = $this->serialize_form( $this->render_ticket_form( $event_id, $ticket_id ) );
		$fields[]          = [ 'ticket_sale_start_date', '2027-01-04' ];
		$fields[]          = [ 'ticket_sale_end_date', '2027-01-11' ];

		$response = $this->send_classic_ticket_form( $event_id, $this->to_query_string( $fields ), false );

		$this->assertTrue( $response['success'], wp_json_encode( $response['data'] ?? null ) );
		$this->assertArrayNotHasKey( Window_Kind::sale_price()->get_store_key(), tribe( Rule_Store::class )->get( $ticket_id ) );
		$this->assertSame( [ '2027-01-04', '2027-01-11' ], $this->get_sale_price_dates( $ticket_id ) );
	}

	/**
	 * @test
	 */
	public function should_store_the_sale_price_rule_the_classic_form_submits(): void {
		$this->make_controller()->register();
		$event_start = new DateTimeImmutable( self::EVENT_START );
		$event_id    = $this->create_event( self::EVENT_START );
		$ticket_id   = $this->create_sale_price_ticket_up_to_the_event( $event_id );
		$rule        = [ 'start' => $this->sale_price_relative( 2, WEEK_IN_SECONDS ), 'end' => $this->sale_price_relative( 1, WEEK_IN_SECONDS ) ];
		$fields      = $this->serialize_form( $this->render_ticket_form( $event_id, $ticket_id ) );

		// The script writes the rule into the form's own field before `tickets.js` serializes the form.
		$data_key    = Window_Kind::sale_price()->get_rule_keys()['data'];
		$rule_fields = array_keys( array_filter( $fields, static fn( array $field ): bool => $data_key === $field[0] ) );
		$this->assertCount( 1, $rule_fields );
		$fields[ reset( $rule_fields ) ][1] = wp_json_encode( $rule );

		$response = $this->send_classic_ticket_form( $event_id, $this->to_query_string( $fields ) );

		$this->assertTrue( $response['success'], wp_json_encode( $response['data'] ?? null ) );
		$this->assertSame( $rule, $this->get_stored_sale_price_rule( $ticket_id ) );
		$this->assertSame(
			[ $event_start->modify( '-2 weeks' )->format( 'Y-m-d' ), $event_start->modify( '-1 week' )->format( 'Y-m-d' ) ],
			$this->get_sale_price_dates( $ticket_id )
		);
	}

	/**
	 * @test
	 */
	public function should_keep_the_stored_sale_price_rule_when_the_classic_form_is_saved_unchanged(): void {
		$this->make_controller()->register();
		$event_id  = $this->create_event( self::EVENT_START );
		$ticket_id = $this->create_sale_price_ticket_up_to_the_event( $event_id );
		$rule      = [ 'start' => [ 'mode' => 'now' ], 'end' => $this->sale_price_relative( 3, DAY_IN_SECONDS ) ];
		$this->store_sale_price_rule( $ticket_id, $rule );

		$fields   = $this->serialize_form( $this->render_ticket_form( $event_id, $ticket_id ) );
		$response = $this->send_classic_ticket_form( $event_id, $this->to_query_string( $fields ) );

		$this->assertTrue( $response['success'], wp_json_encode( $response['data'] ?? null ) );
		$this->assertSame( $rule, $this->get_stored_sale_price_rule( $ticket_id ) );
	}

	/**
	 * @test
	 */
	public function should_render_the_preset_sales_window_from_its_rule(): void {
		$rule = [
			'start' => [
				'mode'   => Rule::MODE_RELATIVE,
				'value'  => 3,
				'unit'   => DAY_IN_SECONDS,
				'anchor' => Rule::ANCHOR_END,
			],
			'end'   => [ 'mode' => Rule::MODE_DEFAULT ],
		];

		$form = $this->render_preset_sales_window( $rule );

		$this->assertSame( Rule::MODE_RELATIVE, $this->get_selected_value( $form, self::PRESET_PREFIX . '_start_mode' ) );
		$this->assertSame( '3', $this->get_element( $form, self::PRESET_PREFIX . '_start_value' )->getAttribute( 'value' ) );
		$this->assertSame( DAY_IN_SECONDS, absint( $this->get_selected_value( $form, self::PRESET_PREFIX . '_start_unit' ) ) );
		$this->assertSame( Rule::ANCHOR_END, $this->get_selected_value( $form, self::PRESET_PREFIX . '_start_anchor' ) );
		$this->assertSame( Rule::MODE_DEFAULT, $this->get_selected_value( $form, self::PRESET_PREFIX . '_end_mode' ) );
		// A boundary on its default mode offers the ticket form's relative values once the admin switches to relative.
		$this->assertSame( Editor::DEFAULT_RELATIVE_END['value'], absint( $this->get_element( $form, self::PRESET_PREFIX . '_end_value' )->getAttribute( 'value' ) ) );
		$this->assertSame( self::PRESET_PREFIX . '_start_mode', $form->query( "//label[@for='" . self::PRESET_PREFIX . "_start_mode']" )->item( 0 )->getAttribute( 'for' ) );
		$this->assertSame(
			Window_Kind::sales()->get_error( 'endsBeforeStart' )->get_error_message(),
			$this->get_element( $form, self::PRESET_PREFIX . '_window_error' )->getAttribute( 'data-ends-before-start' )
		);
	}

	/**
	 * @test
	 */
	public function should_offer_no_specific_date_helper_text_or_rule_field_in_the_preset_sales_window(): void {
		$form = $this->render_preset_sales_window( [] );

		foreach ( [ 'start', 'end' ] as $end ) {
			$this->assertSame( [ Rule::MODE_DEFAULT, Rule::MODE_RELATIVE ], $this->get_option_values( $form, self::PRESET_PREFIX . "_{$end}_mode" ) );
			$this->assertFalse( $this->get_element( $form, self::PRESET_PREFIX . "_{$end}_value" )->hasAttribute( 'aria-describedby' ) );
		}

		$this->assertSame( 0, $form->query( "//*[contains(@class, 'tec-tickets-relative-sale-dates__specific')]" )->length );
		$this->assertSame( 0, $form->query( "//*[contains(@class, 'tec-tickets-relative-sale-dates__helper')]" )->length );
		$this->assertSame( 0, $form->query( '//input' )->length - $form->query( "//input[@type='number']" )->length, 'Only the relative numbers are inputs.' );
	}

	/**
	 * The preset form has no Event Tickets script to name a unit after the number typed.
	 *
	 * @test
	 */
	public function should_give_the_preset_sales_window_the_name_of_each_unit_for_each_number(): void {
		$form = $this->render_preset_sales_window( [] );

		$names = json_decode( $form->query( '//*[@data-unit-names]' )->item( 0 )->getAttribute( 'data-unit-names' ), true );

		$this->assertSame( array_map( 'strval', Window_Kind::sales()->get_units() ), array_map( 'strval', array_keys( $names ) ) );
		foreach ( $names as $unit_names ) {
			$this->assertCount( Window_Kind::sales()->get_max_value(), $unit_names );
		}
		$this->assertSame( _nx( 'day', 'days', 1, 'Unit of a relative ticket sale date.', 'event-tickets' ), $names[ DAY_IN_SECONDS ][0] );
		$this->assertSame( _nx( 'day', 'days', 2, 'Unit of a relative ticket sale date.', 'event-tickets' ), $names[ DAY_IN_SECONDS ][1] );
	}

	/**
	 * @return Generator<string,array{0: array{start?: array{mode: string, value?: int, unit?: int, anchor?: string}|null, end?: array{mode: string, value?: int, unit?: int, anchor?: string}|null}}>
	 */
	public function preset_boundary_without_an_option_provider(): Generator {
		yield 'missing' => [ [] ];
		yield 'null' => [ [ 'start' => null ] ];
		yield 'a specific date' => [ [ 'start' => [ 'mode' => Rule::MODE_SPECIFIC ] ] ];
		yield 'out of range' => [ [ 'start' => array_merge( Editor::DEFAULT_RELATIVE_START, [ 'value' => Boundary::MAX_VALUE + 1 ] ) ] ];
	}

	/**
	 * @test
	 * @dataProvider preset_boundary_without_an_option_provider
	 *
	 * @param array{start?: array{mode: string, value?: int, unit?: int, anchor?: string}|null, end?: array{mode: string, value?: int, unit?: int, anchor?: string}|null} $rule The rule, whose start the preset form has no option for.
	 */
	public function should_render_the_default_for_a_preset_boundary_it_has_no_option_for( array $rule ): void {
		$form = $this->render_preset_sales_window( $rule );

		$this->assertSame( Rule::MODE_DEFAULT, $this->get_selected_value( $form, self::PRESET_PREFIX . '_start_mode' ) );
		$this->assertSame( Editor::DEFAULT_RELATIVE_START['value'], absint( $this->get_element( $form, self::PRESET_PREFIX . '_start_value' )->getAttribute( 'value' ) ) );
	}

	/**
	 * The admin views template carries each render's context into the next one, and the event screen renders the
	 * *Save as preset* modal next to the ticket form.
	 *
	 * @test
	 */
	public function should_keep_the_ticket_form_sales_window_after_a_preset_one(): void {
		$this->make_controller()->register();
		$event_id = $this->create_event( self::EVENT_START );
		$this->render_preset_sales_window( [] );

		$form = $this->render_ticket_form( $event_id );

		$this->assertContains( Rule::MODE_SPECIFIC, $this->get_option_values( $form, 'ticket_sales_start_mode' ) );
		$this->assertTrue( $this->get_element( $form, 'ticket_sales_start_value' )->hasAttribute( 'aria-describedby' ) );
		$this->get_element( $form, 'ticket_relative_sale_dates' );
		$this->get_element( $form, 'ticket_sales_window_error' );
	}

	/**
	 * Only Tickets Commerce tickets have the sale price fields the sale price window replaces.
	 *
	 * @test
	 */
	public function should_offer_the_sales_window_alone_to_a_ticket_of_an_accepted_provider(): void {
		$this->accept_paypal_tickets();
		$this->make_controller()->register();
		$event_id  = $this->create_event( self::EVENT_START );
		$ticket_id = $this->create_ticket( 'tickets.commerce.paypal', $event_id, 1 );

		$form = $this->render_ticket_form( $event_id, $ticket_id );

		// A saved ticket without a rule opens on its own dates, as a Tickets Commerce one does.
		$this->assertSame( 'specific', $this->get_selected_value( $form, 'ticket_sales_start_mode' ) );
		$this->assertSame( 0, $form->query( "//*[@id='ticket_sale_start_mode']" )->length );
	}

	/**
	 * @test
	 */
	public function should_leave_the_date_fields_of_a_provider_the_sales_window_does_not_apply_to(): void {
		$this->accept_paypal_tickets();
		remove_all_filters( 'tec_tickets_relative_sale_dates_ticket_providers' );
		$this->make_controller()->register();
		$event_id  = $this->create_event( self::EVENT_START );
		$ticket_id = $this->create_ticket( 'tickets.commerce.paypal', $event_id, 1 );

		$this->assertSame( 0, $this->render_ticket_form( $event_id, $ticket_id )->query( "//*[@id='ticket_sales_start_mode']" )->length );
	}

	/**
	 * @param array{start?: array{mode: string, value?: int, unit?: int, anchor?: string}|null, end?: array{mode: string, value?: int, unit?: int, anchor?: string}|null} $rule The preset's rule.
	 *
	 * @return DOMXPath The preset sales window, ready to query.
	 */
	private function render_preset_sales_window( array $rule ): DOMXPath {
		$document = new DOMDocument();
		// The window is an HTML fragment, which libxml warns about.
		libxml_use_internal_errors( true );
		$document->loadHTML(
			'<?xml encoding="UTF-8">' . tribe( Admin_Views::class )->template(
				'relative-sale-dates/sales-window',
				tribe( Editor::class )->get_preset_sales_window_context( $rule, self::PRESET_PREFIX ),
				false
			)
		);
		libxml_clear_errors();

		return new DOMXPath( $document );
	}

	/**
	 * @param DOMXPath $form The form.
	 * @param string   $id   The select id.
	 *
	 * @return string[] The values of the select's options.
	 */
	private function get_option_values( DOMXPath $form, string $id ): array {
		$values = [];

		foreach ( $form->query( "//select[@id='{$id}']/option" ) as $option ) {
			$values[] = $option->getAttribute( 'value' );
		}

		$this->assertNotEmpty( $values, "No options in {$id}." );

		return $values;
	}

	/**
	 * @param int $post_id The ticketed post ID.
	 *
	 * @return DOMXPath The classic tickets list, ready to query.
	 */
	private function render_tickets_list( int $post_id ): DOMXPath {
		$document = new DOMDocument();
		// The list is an HTML fragment, which libxml warns about.
		libxml_use_internal_errors( true );
		$document->loadHTML( '<?xml encoding="UTF-8">' . tribe( 'tickets.metabox' )->get_panels( $post_id )['list'] );
		libxml_clear_errors();

		return new DOMXPath( $document );
	}

	/**
	 * @param DateTimeImmutable $start The sales start, in the event timezone.
	 * @param DateTimeImmutable $end   The sales end, in the event timezone.
	 *
	 * @return array{ticket_start_date: string, ticket_start_time: string, ticket_end_date: string, ticket_end_time: string} The ticket's sales dates.
	 */
	private function get_sales_window( DateTimeImmutable $start, DateTimeImmutable $end ): array {
		return [
			'ticket_start_date' => $start->format( 'Y-m-d' ),
			'ticket_start_time' => $start->format( 'H:i:s' ),
			'ticket_end_date'   => $end->format( 'Y-m-d' ),
			'ticket_end_time'   => $end->format( 'H:i:s' ),
		];
	}

	/**
	 * @param DOMXPath $list      The tickets list.
	 * @param int      $ticket_id The ticket post ID.
	 *
	 * @return DOMElement The element that shows the ticket's sale dates.
	 */
	private function get_available_dates( DOMXPath $list, int $ticket_id ): DOMElement {
		$dates = $list->query( "//*[@data-ticket-type-id='{$ticket_id}']//*[contains(@class, 'tribe-tickets__tickets-editor-ticket-available-dates')]" )->item( 0 );
		$this->assertInstanceOf( DOMElement::class, $dates, "No sale dates for the ticket {$ticket_id}." );

		return $dates;
	}

	/**
	 * @param DOMXPath $form The ticket form.
	 * @param string[] $ids  The ids of the inputs.
	 *
	 * @return array<string,array<string,string>> The attributes of each input, keyed by input id.
	 */
	private function get_input_attributes( DOMXPath $form, array $ids ): array {
		$attributes = [];

		foreach ( $ids as $id ) {
			foreach ( $this->get_element( $form, $id )->attributes as $attribute ) {
				$attributes[ $id ][ $attribute->name ] = $attribute->value;
			}
		}

		return $attributes;
	}

	/**
	 * Creates a Tickets Commerce ticket priced 20 with a sale price of 10 running up to the event day.
	 *
	 * @param int $post_id The ticketed post ID.
	 *
	 * @return int The ticket post ID.
	 */
	private function create_sale_price_ticket_up_to_the_event( int $post_id ): int {
		$event_start = new DateTimeImmutable( self::EVENT_START );

		return $this->create_sale_price_ticket(
			$post_id,
			[
				'ticket_sale_start_date' => $event_start->modify( '-1 month' )->format( 'Y-m-d' ),
				'ticket_sale_end_date'   => $event_start->format( 'Y-m-d' ),
			]
		);
	}

	/**
	 * @param int                                                                                          $ticket_id The ticket post ID.
	 * @param array{start: array{mode: string, value?: int, unit?: int}, end: array{mode: string, value?: int, unit?: int}} $rule      The sale price rule.
	 */
	private function store_sale_price_rule( int $ticket_id, array $rule ): void {
		tribe( Rule_Store::class )->save( $ticket_id, [ Window_Kind::sale_price()->get_store_key() => $rule ] );
	}

	/**
	 * @param int $ticket_id The ticket post ID.
	 *
	 * @return array{start: array{mode: string, value?: int, unit?: int}, end: array{mode: string, value?: int, unit?: int}}|null The stored sale price rule, or `null` when there is none.
	 */
	private function get_stored_sale_price_rule( int $ticket_id ): ?array {
		return tribe( Rule_Store::class )->get( $ticket_id )[ Window_Kind::sale_price()->get_store_key() ] ?? null;
	}
}
