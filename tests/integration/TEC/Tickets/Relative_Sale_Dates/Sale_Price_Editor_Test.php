<?php

namespace TEC\Tickets\Relative_Sale_Dates;

use Closure;
use DateTimeImmutable;
use DOMXPath;
use Generator;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\Classic_Ticket_Form;
use Tribe\Tickets\Test\Traits\Relative_Sale_Dates_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;

class Sale_Price_Editor_Test extends Controller_Test_Case {
	use Classic_Ticket_Form;
	use Relative_Sale_Dates_Maker;
	use Ticket_Maker;
	use With_Tickets_Commerce;

	/**
	 * The event start the tests use, in UTC.
	 *
	 * @var string
	 */
	private const EVENT_START = '2027-06-24 19:00:00';

	/**
	 * The ids of the sale price inputs the classic ticket form has always had.
	 *
	 * @var string[]
	 */
	private const KEPT_INPUT_IDS = [ 'ticket_add_sale_price', 'ticket_sale_price', 'ticket_sale_start_date', 'ticket_sale_end_date' ];

	protected $controller_class = Sale_Price_Editor::class;

	/**
	 * @before
	 */
	public function log_in_as_administrator(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	/**
	 * @test
	 */
	public function should_prefill_the_sale_price_options_from_the_stored_rule(): void {
		$this->make_controller()->register();
		$event_id  = $this->create_event( self::EVENT_START );
		$ticket_id = $this->create_sale_price_ticket( $event_id );
		$rule      = [ 'start' => $this->sale_price_relative( 10, Rule::UNIT_DAYS ), 'end' => $this->sale_price_relative( 1, Rule::UNIT_WEEKS ) ];
		tribe( Rule_Store::class )->save( $ticket_id, [ Sale_Price_Rule::KEY => $rule ] );

		$form = $this->render_ticket_form( $event_id, $ticket_id );

		$this->assertSame( 'relative', $this->get_selected_value( $form, 'ticket_sale_start_mode' ) );
		$this->assertSame( '10', $this->get_element( $form, 'ticket_sale_start_value' )->getAttribute( 'value' ) );
		$this->assertSame( Rule::UNIT_DAYS, absint( $this->get_selected_value( $form, 'ticket_sale_start_unit' ) ) );
		$this->assertSame( 'relative', $this->get_selected_value( $form, 'ticket_sale_end_mode' ) );
		$this->assertSame( '1', $this->get_element( $form, 'ticket_sale_end_value' )->getAttribute( 'value' ) );
		$this->assertSame( Rule::UNIT_WEEKS, absint( $this->get_selected_value( $form, 'ticket_sale_end_unit' ) ) );
		$this->assertSame( $rule, json_decode( $this->get_element( $form, 'ticket_sale_price_relative' )->getAttribute( 'value' ), true ) );
	}

	/**
	 * @test
	 */
	public function should_offer_the_relative_defaults_next_to_a_stored_boundary_that_is_not_relative(): void {
		$this->make_controller()->register();
		$event_id  = $this->create_event( self::EVENT_START );
		$ticket_id = $this->create_sale_price_ticket( $event_id );
		tribe( Rule_Store::class )->save( $ticket_id, [ Sale_Price_Rule::KEY => [ 'start' => [ 'mode' => 'now' ], 'end' => [ 'mode' => 'specific' ] ] ] );

		$form = $this->render_ticket_form( $event_id, $ticket_id );

		$this->assertSame( 'now', $this->get_selected_value( $form, 'ticket_sale_start_mode' ) );
		$this->assertSame( '2', $this->get_element( $form, 'ticket_sale_start_value' )->getAttribute( 'value' ) );
		$this->assertSame( Rule::UNIT_WEEKS, absint( $this->get_selected_value( $form, 'ticket_sale_start_unit' ) ) );
		$this->assertSame( 'specific', $this->get_selected_value( $form, 'ticket_sale_end_mode' ) );
		$this->assertSame( '1', $this->get_element( $form, 'ticket_sale_end_value' )->getAttribute( 'value' ) );
		$this->assertSame( Rule::UNIT_WEEKS, absint( $this->get_selected_value( $form, 'ticket_sale_end_unit' ) ) );
	}

	/**
	 * @test
	 */
	public function should_open_a_new_ticket_on_now_and_one_week_before_the_event_starts(): void {
		$this->make_controller()->register();

		$form = $this->render_ticket_form( $this->create_event( self::EVENT_START ) );

		$this->assertSame( 'now', $this->get_selected_value( $form, 'ticket_sale_start_mode' ) );
		$this->assertSame( '2', $this->get_element( $form, 'ticket_sale_start_value' )->getAttribute( 'value' ) );
		$this->assertSame( Rule::UNIT_WEEKS, absint( $this->get_selected_value( $form, 'ticket_sale_start_unit' ) ) );
		$this->assertSame( 'relative', $this->get_selected_value( $form, 'ticket_sale_end_mode' ) );
		$this->assertSame( '1', $this->get_element( $form, 'ticket_sale_end_value' )->getAttribute( 'value' ) );
		$this->assertSame( Rule::UNIT_WEEKS, absint( $this->get_selected_value( $form, 'ticket_sale_end_unit' ) ) );
		$this->assertSame( '', $this->get_element( $form, 'ticket_sale_price_relative' )->getAttribute( 'value' ) );
	}

	/**
	 * @test
	 */
	public function should_open_a_saved_ticket_without_a_sale_price_on_the_defaults(): void {
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

		$form = $this->render_ticket_form( $event_id, $this->create_sale_price_ticket( $event_id ) );

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
		$this->assertSame( Sale_Price_Save::DATA_KEY, $hidden->getAttribute( 'name' ) );
		// An unchecked sale price disables its dependents, so the rule is not sent without it.
		$this->assertSame( '#ticket_add_sale_price', $this->get_dependent( $hidden )->getAttribute( 'data-depends' ) );
	}

	/**
	 * @test
	 */
	public function should_offer_one_to_thirty_days_or_weeks_named_for_the_number(): void {
		$this->make_controller()->register();
		$event_id  = $this->create_event( self::EVENT_START );
		$ticket_id = $this->create_sale_price_ticket( $event_id );
		tribe( Rule_Store::class )->save(
			$ticket_id,
			[ Sale_Price_Rule::KEY => [ 'start' => $this->sale_price_relative( 3, Rule::UNIT_WEEKS ), 'end' => $this->sale_price_relative( 1, Rule::UNIT_DAYS ) ] ]
		);

		$form = $this->render_ticket_form( $event_id, $ticket_id );

		$this->assertSame( [ 'days', 'weeks' ], $this->get_option_labels( $form, 'ticket_sale_start_unit' ) );
		$this->assertSame( [ 'day', 'week' ], $this->get_option_labels( $form, 'ticket_sale_end_unit' ) );
		foreach ( [ 'start', 'end' ] as $end ) {
			$value = $this->get_element( $form, "ticket_sale_{$end}_value" );
			$this->assertSame(
				[ Sale_Price_Boundary::MIN_VALUE, Sale_Price_Boundary::MAX_VALUE ],
				[ absint( $value->getAttribute( 'min' ) ), absint( $value->getAttribute( 'max' ) ) ]
			);
		}
	}

	/**
	 * @test
	 */
	public function should_show_the_date_inputs_only_for_a_specific_date_and_the_relative_inputs_only_for_a_relative_one(): void {
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
	public function should_keep_the_attributes_of_the_existing_sale_price_inputs(): void {
		$event_id  = $this->create_event( self::EVENT_START );
		$ticket_id = $this->create_sale_price_ticket( $event_id );
		$expected  = $this->get_kept_input_attributes( $this->render_ticket_form( $event_id, $ticket_id ) );

		$this->make_controller()->register();
		$actual = $this->get_kept_input_attributes( $this->render_ticket_form( $event_id, $ticket_id ) );

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

		$form = $this->render_ticket_form( $event_id, $this->create_sale_price_ticket( $event_id ) );

		$this->get_element( $form, 'ticket_sale_start_mode' );
		$this->get_element( $form, 'before-the-sale-price-fields' );
		$this->get_element( $form, 'after-the-sale-price-fields' );
	}

	public function unchanged_form_provider(): Generator {
		yield 'ticket on a page' => [
			function (): array {
				$post_id = static::factory()->post->create( [ 'post_type' => 'page' ] );
				$this->enable_tickets_on( 'page' );

				return [ $post_id, $this->create_sale_price_ticket( $post_id ), null ];
			},
		];

		yield 'new ticket on a page' => [
			function (): array {
				$this->enable_tickets_on( 'page' );

				return [ static::factory()->post->create( [ 'post_type' => 'page' ] ), null, null ];
			},
		];

		yield 'new Series Pass' => [
			function (): array {
				return [ $this->create_event( self::EVENT_START ), null, Series_Passes::TICKET_TYPE ];
			},
		];
	}

	/**
	 * @test
	 * @dataProvider unchanged_form_provider
	 */
	public function should_leave_the_sale_price_fields_unchanged( Closure $fixture ): void {
		[ $post_id, $ticket_id, $ticket_type ] = $fixture();
		$expected                              = $this->render_ticket_panel( $post_id, $ticket_id, $ticket_type );

		$this->make_controller()->register();

		$this->assertSame( $expected, $this->render_ticket_panel( $post_id, $ticket_id, $ticket_type ) );
	}

	/**
	 * @test
	 */
	public function should_store_the_sale_price_rule_the_classic_form_submits(): void {
		$this->make_controller()->register();
		$event_start = new DateTimeImmutable( self::EVENT_START );
		$event_id    = $this->create_event( self::EVENT_START );
		$ticket_id   = $this->create_sale_price_ticket( $event_id );
		$rule        = [ 'start' => $this->sale_price_relative( 2, Rule::UNIT_WEEKS ), 'end' => $this->sale_price_relative( 1, Rule::UNIT_WEEKS ) ];
		$fields      = $this->serialize_form( $this->render_ticket_form( $event_id, $ticket_id ) );

		// The script writes the rule into the form's own field before `tickets.js` serializes the form.
		$rule_fields = array_keys( array_filter( $fields, static fn( array $field ): bool => Sale_Price_Save::DATA_KEY === $field[0] ) );
		$this->assertCount( 1, $rule_fields );
		$fields[ reset( $rule_fields ) ][1] = wp_json_encode( $rule );

		$response = $this->send_classic_ticket_form( $event_id, $this->to_query_string( $fields ) );

		$this->assertTrue( $response['success'], wp_json_encode( $response['data'] ?? null ) );
		$this->assertSame( $rule, tribe( Rule_Store::class )->get( $ticket_id )[ Sale_Price_Rule::KEY ] ?? null );
		$this->assertSame(
			[ $event_start->modify( '-2 weeks' )->format( 'Y-m-d' ), $event_start->modify( '-1 week' )->format( 'Y-m-d' ) ],
			[
				get_post_meta( $ticket_id, Ticket::$sale_price_start_date_key, true ),
				get_post_meta( $ticket_id, Ticket::$sale_price_end_date_key, true ),
			]
		);
	}

	/**
	 * @test
	 */
	public function should_keep_the_stored_sale_price_rule_when_the_classic_form_is_saved_unchanged(): void {
		$this->make_controller()->register();
		$event_id  = $this->create_event( self::EVENT_START );
		$ticket_id = $this->create_sale_price_ticket( $event_id );
		$rule      = [ 'start' => [ 'mode' => 'now' ], 'end' => $this->sale_price_relative( 3, Rule::UNIT_DAYS ) ];
		tribe( Rule_Store::class )->save( $ticket_id, [ Sale_Price_Rule::KEY => $rule ] );

		$fields   = $this->serialize_form( $this->render_ticket_form( $event_id, $ticket_id ) );
		$response = $this->send_classic_ticket_form( $event_id, $this->to_query_string( $fields ) );

		$this->assertTrue( $response['success'], wp_json_encode( $response['data'] ?? null ) );
		$this->assertSame( $rule, tribe( Rule_Store::class )->get( $ticket_id )[ Sale_Price_Rule::KEY ] ?? null );
	}

	/**
	 * Creates a Tickets Commerce ticket priced 20 with a sale price of 10 running up to the event day.
	 *
	 * @param int $post_id The ticketed post ID.
	 *
	 * @return int The ticket post ID.
	 */
	private function create_sale_price_ticket( int $post_id ): int {
		$event_day = ( new DateTimeImmutable( self::EVENT_START ) )->format( 'Y-m-d' );

		return $this->create_tc_ticket(
			$post_id,
			20,
			[
				'ticket_add_sale_price'  => 'on',
				'ticket_sale_price'      => 10,
				'ticket_sale_start_date' => ( new DateTimeImmutable( self::EVENT_START ) )->modify( '-1 month' )->format( 'Y-m-d' ),
				'ticket_sale_end_date'   => $event_day,
			]
		);
	}

	/**
	 * @param int $value The number of units before the event start.
	 * @param int $unit  `Rule::UNIT_DAYS` or `Rule::UNIT_WEEKS`.
	 *
	 * @return array{mode: string, value: int, unit: int} A relative boundary of the sale price window.
	 */
	private function sale_price_relative( int $value, int $unit ): array {
		return [
			'mode'  => Rule::MODE_RELATIVE,
			'value' => $value,
			'unit'  => $unit,
		];
	}

	/**
	 * @param DOMXPath $form The ticket form.
	 *
	 * @return array<string,array<string,string>> The attributes of each kept sale price input, keyed by input id.
	 */
	private function get_kept_input_attributes( DOMXPath $form ): array {
		$attributes = [];

		foreach ( self::KEPT_INPUT_IDS as $id ) {
			foreach ( $this->get_element( $form, $id )->attributes as $attribute ) {
				$attributes[ $id ][ $attribute->name ] = $attribute->value;
			}
		}

		return $attributes;
	}
}
