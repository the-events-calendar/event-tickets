<?php

namespace TEC\Tickets\Relative_Sale_Dates;

use Closure;
use DateTimeImmutable;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Generator;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\Classic_Ticket_Form;
use Tribe\Tickets\Test\Traits\Relative_Sale_Dates_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;

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

	protected $controller_class = Controller::class;

	/**
	 * @before
	 */
	public function log_in_as_administrator(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
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
		$this->assertSame( Ticket_Save::DATA_KEY, $hidden->getAttribute( 'name' ) );
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
		$this->assertSame( $this->get_ticket_start( $ruled )[0], $dates->getAttribute( 'data-sale-start' ) );
		$this->assertSame( $this->get_ticket_end( $ruled )[0], $dates->getAttribute( 'data-sale-end' ) );
		// Each row's template context is merged into the next one, so the rule must not carry over.
		$this->assertFalse( $this->get_available_dates( $list, $plain )->hasAttribute( 'data-relative-sale-dates' ) );
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
		$expected  = $this->get_date_input_attributes( $this->render_ticket_form( $event_id, $ticket_id ) );

		$this->make_controller()->register();
		$actual = $this->get_date_input_attributes( $this->render_ticket_form( $event_id, $ticket_id ) );

		$this->assertSame( $expected, $actual );
	}

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

		yield 'RSVP on an event' => [
			function (): array {
				$event_id = $this->create_event( '2027-06-24 19:00:00' );

				return [ $event_id, $this->create_rsvp_ticket( $event_id ), null ];
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
	}

	/**
	 * @test
	 * @dataProvider unchanged_form_provider
	 */
	public function should_leave_the_ticket_form_unchanged( Closure $fixture ): void {
		[ $post_id, $ticket_id, $ticket_type ] = $fixture();
		// The sale price fields render in every classic form of an event already; this case is about the sales window.
		add_filter(
			'tribe_template_include_html:tickets/admin-views/commerce/metabox/sale-price',
			$this->test_services->callback( Sale_Price_Editor::class, 'render_sale_price_fields' ),
			10,
			4
		);
		$expected = $this->render_ticket_panel( $post_id, $ticket_id, $ticket_type );

		$this->make_controller()->register();

		$this->assertSame( $expected, $this->render_ticket_panel( $post_id, $ticket_id, $ticket_type ) );
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
		$rule_fields = array_keys( array_filter( $fields, static fn( array $field ): bool => Ticket_Save::DATA_KEY === $field[0] ) );
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
	 *
	 * @return array<string,array<string,string>> The attributes of each date and time input, keyed by input id.
	 */
	private function get_date_input_attributes( DOMXPath $form ): array {
		$attributes = [];

		foreach ( self::DATE_INPUT_IDS as $id ) {
			foreach ( $this->get_element( $form, $id )->attributes as $attribute ) {
				$attributes[ $id ][ $attribute->name ] = $attribute->value;
			}
		}

		return $attributes;
	}
}
