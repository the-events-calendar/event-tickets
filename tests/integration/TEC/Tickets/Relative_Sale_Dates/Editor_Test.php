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
use TEC\Tickets\RSVP\V2\Constants as RSVP_V2_Constants;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\Relative_Sale_Dates_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;

class Editor_Test extends Controller_Test_Case {
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
	public function should_show_the_date_inputs_only_for_a_specific_date(): void {
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
		}
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
	 * @param string $post_type The post type to sell tickets on.
	 */
	private function enable_tickets_on( string $post_type ): void {
		$ticketable   = tribe_get_option( 'ticket-enabled-post-types', [] );
		$ticketable[] = $post_type;
		tribe_update_option( 'ticket-enabled-post-types', array_values( array_unique( $ticketable ) ) );
	}

	/**
	 * @param int         $post_id     The ticketed post ID.
	 * @param int|null    $ticket_id   The ticket post ID, or `null` for a new ticket.
	 * @param string|null $ticket_type The ticket type, or `null` to read it from the ticket.
	 *
	 * @return string The HTML of the classic ticket edit panel.
	 */
	private function render_ticket_panel( int $post_id, ?int $ticket_id = null, ?string $ticket_type = null ): string {
		return tribe( 'tickets.metabox' )->get_panels( $post_id, $ticket_id, $ticket_type )['ticket'];
	}

	/**
	 * @param int      $post_id   The ticketed post ID.
	 * @param int|null $ticket_id The ticket post ID, or `null` for a new ticket.
	 *
	 * @return DOMXPath The classic ticket edit panel, ready to query.
	 */
	private function render_ticket_form( int $post_id, ?int $ticket_id = null ): DOMXPath {
		$document = new DOMDocument();
		// The panel is an HTML fragment, which libxml warns about.
		libxml_use_internal_errors( true );
		$document->loadHTML( '<?xml encoding="UTF-8">' . $this->render_ticket_panel( $post_id, $ticket_id ) );
		libxml_clear_errors();

		return new DOMXPath( $document );
	}

	/**
	 * @param DOMXPath $form The ticket form.
	 * @param string   $id   The element id.
	 *
	 * @return DOMElement The element.
	 */
	private function get_element( DOMXPath $form, string $id ): DOMElement {
		$element = $form->query( "//*[@id='{$id}']" )->item( 0 );
		$this->assertInstanceOf( DOMElement::class, $element, "No element with the id {$id}." );

		return $element;
	}

	/**
	 * @param DOMXPath $form The ticket form.
	 * @param string   $id   The select id.
	 *
	 * @return string The value of the selected option.
	 */
	private function get_selected_value( DOMXPath $form, string $id ): string {
		$option = $form->query( "//select[@id='{$id}']/option[@selected]" )->item( 0 );
		$this->assertInstanceOf( DOMElement::class, $option, "No option selected in {$id}." );

		return $option->getAttribute( 'value' );
	}

	/**
	 * @param DOMElement $element An element inside a dependent block.
	 *
	 * @return DOMElement The closest ancestor shown and hidden by `dependency.js`.
	 */
	private function get_dependent( DOMElement $element ): DOMElement {
		for ( $node = $element->parentNode; $node instanceof DOMElement; $node = $node->parentNode ) {
			if ( in_array( 'tribe-dependent', explode( ' ', $node->getAttribute( 'class' ) ), true ) ) {
				return $node;
			}
		}

		$this->fail( "{$element->getAttribute( 'id' )} is not inside a dependent block." );
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

	/**
	 * Reads the form fields the way jQuery's `serialize()` does in `tickets.js`: named, enabled fields only.
	 *
	 * @param DOMXPath $form The ticket form.
	 *
	 * @return array<int,array{0: string, 1: string}> The submitted name and value pairs, in document order.
	 */
	private function serialize_form( DOMXPath $form ): array {
		$fields = [];

		foreach ( $form->query( "//*[@id='tribe_panel_edit']//*[self::input or self::select or self::textarea][@name][not(@disabled)]" ) as $field ) {
			$type = strtolower( $field->getAttribute( 'type' ) );

			if ( in_array( $type, [ 'submit', 'button', 'reset', 'image', 'file' ], true ) ) {
				continue;
			}

			if ( in_array( $type, [ 'checkbox', 'radio' ], true ) && ! $field->hasAttribute( 'checked' ) ) {
				continue;
			}

			if ( 'select' === $field->nodeName ) {
				$selected = $form->query( './option[@selected]', $field )->item( 0 ) ?? $form->query( './option', $field )->item( 0 );
				if ( $selected instanceof DOMElement ) {
					$fields[] = [ $field->getAttribute( 'name' ), $selected->getAttribute( 'value' ) ];
				}
				continue;
			}

			$fields[] = [ $field->getAttribute( 'name' ), 'textarea' === $field->nodeName ? $field->textContent : $field->getAttribute( 'value' ) ];
		}

		return $fields;
	}

	/**
	 * @param array<int,array{0: string, 1: string}> $fields The name and value pairs; a later pair of the same name wins, as in PHP.
	 *
	 * @return string The fields, URL-encoded as `serialize()` encodes them.
	 */
	private function to_query_string( array $fields ): string {
		return implode( '&', array_map( static fn( array $field ): string => rawurlencode( $field[0] ) . '=' . rawurlencode( $field[1] ), $fields ) );
	}
}
