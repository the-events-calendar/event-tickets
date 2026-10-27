<?php

namespace TEC\Tickets\Relative_Sale_Dates;

use DateTimeImmutable;
use DOMDocument;
use DOMElement;
use DOMXPath;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Commerce\Utils\Value;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\Relative_Sale_Dates_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;

class Price_Column_Test extends Controller_Test_Case {
	use Relative_Sale_Dates_Maker;
	use Ticket_Maker;
	use With_Tickets_Commerce;

	/**
	 * The event start, in UTC: its sale price window, a month up to the event, has not started yet.
	 *
	 * @var string
	 */
	private const EVENT_START = '2027-06-24 19:00:00';

	/**
	 * The template context filter of the price column.
	 *
	 * @var string
	 */
	private const HOOK = 'tribe_template_context:tickets/admin-views/editor/column-body-price';

	protected $controller_class = Controller::class;

	/**
	 * The tickets list is on the event edit screen in wp-admin.
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
	}

	/**
	 * @test
	 */
	public function should_hand_the_price_column_the_sale_price_rule_and_its_stored_dates(): void {
		$this->make_controller()->register();
		$event_id  = $this->create_event( self::EVENT_START );
		$ticket_id = $this->create_sale_price_ticket_up_to_the_event( $event_id );
		$rule      = [ 'start' => [ 'mode' => Rule::MODE_NOW ], 'end' => $this->sale_price_relative( 1, WEEK_IN_SECONDS ) ];
		$this->store_sale_price_rule( $ticket_id, $rule );

		$cell = $this->get_price_cell( $this->render_tickets_list( $event_id ), $ticket_id );

		[ $start, $end ] = $this->get_sale_price_dates( $ticket_id );
		$this->assertSame( $rule, json_decode( $cell->getAttribute( 'data-sale-price-rule' ), true ) );
		$this->assertSame( $start, $cell->getAttribute( 'data-sale-price-start' ) );
		$this->assertSame( $end, $cell->getAttribute( 'data-sale-price-end' ) );
	}

	/**
	 * @test
	 */
	public function should_hand_the_regular_price_a_ticket_off_sale_shows_and_its_sale_price(): void {
		$this->make_controller()->register();
		$event_id  = $this->create_event( self::EVENT_START );
		$ticket_id = $this->create_sale_price_ticket_up_to_the_event( $event_id );
		$this->store_sale_price_rule( $ticket_id, [ 'start' => [ 'mode' => Rule::MODE_NOW ], 'end' => $this->sale_price_relative( 1, WEEK_IN_SECONDS ) ] );

		$cell = $this->get_price_cell( $this->render_tickets_list( $event_id ), $ticket_id );

		$this->assertSame( $this->get_inner_html( $cell ), $this->normalize_html( $cell->getAttribute( 'data-price-regular' ) ) );
		$this->assert_shows_sale_price( $ticket_id, $cell->getAttribute( 'data-price-on-sale' ) );
	}

	/**
	 * @test
	 */
	public function should_hand_the_sale_price_a_ticket_on_sale_shows_and_its_regular_price(): void {
		$this->make_controller()->register();
		$event_id = $this->create_event( self::EVENT_START );
		// Without dates, the sale price applies from the day it is saved.
		$ticket_id = $this->create_sale_price_ticket( $event_id );
		$this->store_sale_price_rule( $ticket_id, [ 'start' => [ 'mode' => Rule::MODE_NOW ], 'end' => $this->sale_price_relative( 1, WEEK_IN_SECONDS ) ] );

		$cell = $this->get_price_cell( $this->render_tickets_list( $event_id ), $ticket_id );

		$this->assertSame( $this->get_inner_html( $cell ), $this->normalize_html( $cell->getAttribute( 'data-price-on-sale' ) ) );
		$this->assertStringContainsString(
			$this->get_display_price( tribe( Ticket::class )->get_regular_price( $ticket_id ) ),
			$cell->getAttribute( 'data-price-regular' )
		);
		$this->assertStringNotContainsString( '<del', $cell->getAttribute( 'data-price-regular' ) );
	}

	/**
	 * @test
	 */
	public function should_leave_the_price_column_of_a_sale_price_without_a_rule_alone(): void {
		$this->make_controller()->register();
		$event_id  = $this->create_event( self::EVENT_START );
		$ticket_id = $this->create_sale_price_ticket_up_to_the_event( $event_id );

		$cell = $this->get_price_cell( $this->render_tickets_list( $event_id ), $ticket_id );

		$this->assertFalse( $cell->hasAttribute( 'data-sale-price-rule' ) );
		$this->assertFalse( $cell->hasAttribute( 'data-price-on-sale' ) );
	}

	/**
	 * A ticket keeps its sale price rule when the admin unchecks the sale price, so the rule alone is not enough.
	 *
	 * @test
	 */
	public function should_leave_the_price_column_of_a_ticket_without_a_sale_price_alone(): void {
		$this->make_controller()->register();
		$event_id  = $this->create_event( self::EVENT_START );
		$ticket_id = $this->create_tc_ticket( $event_id, 20 );
		$this->store_sale_price_rule( $ticket_id, [ 'start' => [ 'mode' => Rule::MODE_NOW ], 'end' => $this->sale_price_relative( 1, WEEK_IN_SECONDS ) ] );

		$cell = $this->get_price_cell( $this->render_tickets_list( $event_id ), $ticket_id );

		$this->assertFalse( $cell->hasAttribute( 'data-sale-price-rule' ) );
	}

	/**
	 * Each row's template context is merged into the values the next row inherits.
	 *
	 * @test
	 */
	public function should_not_carry_a_rule_over_to_the_next_row(): void {
		$this->make_controller()->register();
		$event_id = $this->create_event( self::EVENT_START );
		$ruled    = $this->create_sale_price_ticket_up_to_the_event( $event_id );
		$this->store_sale_price_rule( $ruled, [ 'start' => [ 'mode' => Rule::MODE_NOW ], 'end' => $this->sale_price_relative( 1, WEEK_IN_SECONDS ) ] );
		$plain = $this->create_sale_price_ticket_up_to_the_event( $event_id );

		$list = $this->render_tickets_list( $event_id );

		$this->assertTrue( $this->get_price_cell( $list, $ruled )->hasAttribute( 'data-sale-price-rule' ) );
		$this->assertFalse( $this->get_price_cell( $list, $plain )->hasAttribute( 'data-sale-price-rule' ) );
	}

	/**
	 * @test
	 */
	public function should_hand_on_a_price_column_context_an_earlier_callback_left_without_an_array(): void {
		$this->make_controller()->register();
		add_filter( self::HOOK, '__return_null', 5 );

		$this->assertNull( apply_filters( self::HOOK, [] ) );
	}

	/**
	 * @param int    $ticket_id The ticket post ID.
	 * @param string $html      The price HTML.
	 */
	private function assert_shows_sale_price( int $ticket_id, string $html ): void {
		$this->assertStringContainsString( $this->get_display_price( tribe( Ticket::class )->get_sale_price( $ticket_id ) ), $html );
		$this->assertStringContainsString( '<del', $html );
	}

	/**
	 * @param string $price A price.
	 *
	 * @return string The price as a price HTML shows it, once the attribute holding the HTML is read: entities decoded.
	 */
	private function get_display_price( string $price ): string {
		return html_entity_decode( Value::create( $price )->get_currency_display(), ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Creates a Tickets Commerce ticket with a sale price running the month up to the event day.
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
	 * @param int                                                                                                           $ticket_id The ticket post ID.
	 * @param array{start: array{mode: string, value?: int, unit?: int}, end: array{mode: string, value?: int, unit?: int}} $rule      The sale price rule.
	 */
	private function store_sale_price_rule( int $ticket_id, array $rule ): void {
		tribe( Rule_Store::class )->save( $ticket_id, [ Window_Kind::sale_price()->get_store_key() => $rule ] );
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
	 * @return DOMElement The price cell of the ticket's row.
	 */
	private function get_price_cell( DOMXPath $list, int $ticket_id ): DOMElement {
		$cell = $list->query( "//*[@data-ticket-type-id='{$ticket_id}']//td[contains(@class, 'ticket_price')]" )->item( 0 );
		$this->assertInstanceOf( DOMElement::class, $cell, "No price cell for the ticket {$ticket_id}." );

		return $cell;
	}

	/**
	 * @param DOMElement $element The element.
	 *
	 * @return string The element's content, serialized as `normalize_html()` serializes markup.
	 */
	private function get_inner_html( DOMElement $element ): string {
		$html = '';

		foreach ( $element->childNodes as $child ) {
			$html .= $element->ownerDocument->saveHTML( $child );
		}

		return $this->normalize_html( $html );
	}

	/**
	 * @param string $html An HTML fragment.
	 *
	 * @return string The fragment as libxml serializes it, without the whitespace between and around its tags.
	 */
	private function normalize_html( string $html ): string {
		$document = new DOMDocument();
		libxml_use_internal_errors( true );
		$document->loadHTML( '<?xml encoding="UTF-8"><div>' . $html . '</div>' );
		libxml_clear_errors();

		$wrapper    = $document->getElementsByTagName( 'div' )->item( 0 );
		$serialized = '';

		foreach ( $wrapper->childNodes as $child ) {
			$serialized .= $document->saveHTML( $child );
		}

		return trim( preg_replace( '/\s*(<[^>]+>)\s*/', '$1', $serialized ) );
	}
}
