<?php

namespace TEC\Tickets\Recurring_Tickets;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Commerce\Order_Modifiers\Admin\Order_Modifier_Fee_Metabox;
use TEC\Tickets\Tests\Recurring_Tickets\Ticket_Rows;
use Tribe\Tickets\Test\Commerce\OrderModifiers\Fee_Creator;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use WP_REST_Request;

/**
 * Every ticket saved on a recurring event is a recurring event ticket, from any editor.
 */
class Ticket_Type_Test extends WPTestCase {
	use Ticket_Rows;
	use Ticket_Maker;
	use Fee_Creator;

	/**
	 * @before
	 */
	public function log_in(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	/**
	 * @test
	 */
	public function it_should_make_a_ticket_saved_on_a_recurring_event_a_recurring_event_ticket(): void {
		$event = $this->create_recurring_event();

		$saved_as_default = $this->create_tc_ticket( $event, 10, [ 'ticket_type' => 'default' ] );
		$saved_without    = $this->create_tc_ticket( $event, 10 );

		$this->assertSame( Template_Guard::TICKET_TYPE, get_post_meta( $saved_as_default, '_type', true ) );
		$this->assertSame( Template_Guard::TICKET_TYPE, get_post_meta( $saved_without, '_type', true ) );
	}

	/**
	 * @test
	 */
	public function it_should_store_a_ticket_created_and_edited_in_the_block_editor_as_recurring(): void {
		$event = $this->create_recurring_event();

		$created = $this->block_editor_save( $event, null, 'General Admission' );
		$this->assertSame( 202, $created->get_status(), wp_json_encode( $created->get_data() ) );
		$ticket_ids = get_posts(
			[
				'post_type'  => Ticket::POSTTYPE,
				'fields'     => 'ids',
				'meta_key'   => Ticket::$event_relation_meta_key,
				'meta_value' => $event,
			]
		);
		$this->assertCount( 1, $ticket_ids );
		$ticket_id = (int) $ticket_ids[0];
		$this->assertSame( Template_Guard::TICKET_TYPE, get_post_meta( $ticket_id, '_type', true ) );

		// The endpoint sends no type on an edit either.
		$edited = $this->block_editor_save( $event, $ticket_id, 'General Admission, renamed' );
		$this->assertSame( 202, $edited->get_status(), wp_json_encode( $edited->get_data() ) );
		$this->assertSame( 'General Admission, renamed', get_post( $ticket_id )->post_title );
		$this->assertSame( Template_Guard::TICKET_TYPE, get_post_meta( $ticket_id, '_type', true ) );
	}

	/**
	 * @test
	 */
	public function it_should_keep_a_template_recurring_when_saved_without_a_type_on_an_event_that_no_longer_recurs(): void {
		$event    = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		$template = $this->create_tc_ticket( $event, 10 );
		update_post_meta( $template, '_type', Template_Guard::TICKET_TYPE );

		tribe( Module::class )->ticket_add(
			$event,
			[
				'ticket_id'    => $template,
				'ticket_name'  => 'Renamed',
				'ticket_price' => 10,
			]
		);

		$this->assertSame( Template_Guard::TICKET_TYPE, get_post_meta( $template, '_type', true ) );
	}

	/**
	 * @test
	 */
	public function it_should_leave_an_existing_standard_ticket_standard_when_it_is_saved_again(): void {
		$event    = $this->create_recurring_event();
		$standard = $this->create_tc_ticket( $event, 10 );
		// A standard ticket made before the event recurred.
		update_post_meta( $standard, '_type', 'default' );

		tribe( Module::class )->ticket_add(
			$event,
			[
				'ticket_id'    => $standard,
				'ticket_name'  => 'Renamed',
				'ticket_price' => 10,
				'ticket_type'  => 'default',
			]
		);

		$this->assertSame( 'default', get_post_meta( $standard, '_type', true ) );
	}

	/**
	 * @test
	 */
	public function it_should_give_a_template_its_own_capacity_instead_of_a_shared_one(): void {
		$event    = $this->create_recurring_event();
		$template = $this->create_tc_ticket(
			$event,
			10,
			[
				'tribe-ticket' => [
					'mode'           => \Tribe__Tickets__Global_Stock::GLOBAL_STOCK_MODE,
					'event_capacity' => 100,
					'capacity'       => 100,
				],
			]
		);

		$this->assertSame( \Tribe__Tickets__Global_Stock::OWN_STOCK_MODE, get_post_meta( $template, \Tribe__Tickets__Global_Stock::TICKET_STOCK_MODE, true ), 'Each date sells its own capacity.' );
	}

	/**
	 * @test
	 */
	public function it_should_leave_a_ticket_on_a_single_event_default(): void {
		$event = tribe_events()->set_args(
			[
				'title'      => 'Single Event',
				'status'     => 'publish',
				'start_date' => '+1 week 10:00:00',
				'end_date'   => '+1 week 12:00:00',
			]
		)->create()->ID;

		$this->assertSame( 'default', get_post_meta( $this->create_tc_ticket( $event, 10 ), '_type', true ) );
	}

	/**
	 * @test
	 */
	public function it_should_leave_other_ticket_types_alone(): void {
		$event = $this->create_recurring_event();

		$this->assertSame( 'series_pass', get_post_meta( $this->create_tc_ticket( $event, 10, [ 'ticket_type' => 'series_pass' ] ), '_type', true ) );
	}

	/**
	 * @test
	 */
	public function it_should_show_the_fees_section_in_a_templates_form(): void {
		$event    = $this->create_recurring_event();
		$template = $this->create_tc_ticket( $event, 10 );
		$this->create_fee_for_all();
		update_post_meta( $event, tribe( 'tickets.handler' )->key_provider_field, Module::class );

		ob_start();
		tribe( Order_Modifier_Fee_Metabox::class )->add_fee_section( $event, $template, Template_Guard::TICKET_TYPE );

		$this->assertNotSame( '', trim( (string) ob_get_clean() ) );
	}

	/**
	 * Saves a ticket the way the block editor does, through its REST endpoint.
	 *
	 * @param int      $event     The event.
	 * @param int|null $ticket_id The ticket to edit, or null to create one.
	 * @param string   $name      The ticket's name.
	 *
	 * @return \WP_REST_Response The response.
	 */
	private function block_editor_save( int $event, ?int $ticket_id, string $name ) {
		$nonce_action = $ticket_id ? 'edit_ticket_nonce' : 'add_ticket_nonce';
		$request      = new WP_REST_Request( $ticket_id ? 'PUT' : 'POST', '/tribe/tickets/v1/tickets' . ( $ticket_id ? "/{$ticket_id}" : '' ) );
		$request->set_header( 'Content-Type', 'application/x-www-form-urlencoded' );
		$request->set_body_params(
			[
				'post_id'     => $event,
				'provider'    => Module::class,
				'name'        => $name,
				'description' => '',
				'price'       => '10',
				'start_date'  => '',
				'start_time'  => '',
				'end_date'    => '',
				'end_time'    => '',
				'sku'         => '',
				'menu_order'  => 0,
				'ticket'      => [
					'mode'     => 'own',
					'capacity' => 10,
				],
				$nonce_action => wp_create_nonce( $nonce_action ),
			]
		);

		return rest_do_request( $request );
	}
}
