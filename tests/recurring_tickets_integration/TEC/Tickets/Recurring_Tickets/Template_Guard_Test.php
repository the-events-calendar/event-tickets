<?php

namespace TEC\Tickets\Recurring_Tickets;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;
use TEC\Tickets\RSVP\V2\Controller as RSVP_V2_Controller;
use TEC\Tickets\Recurring_Tickets\Tables\Tickets;
use TEC\Tickets\Tests\Recurring_Tickets\Ticket_Rows;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

/**
 * A template is never listed for customers and never sold, with or without ECP.
 */
class Template_Guard_Test extends WPTestCase {
	use Ticket_Rows;
	use Ticket_Maker;

	/**
	 * @after
	 */
	public function clean_up(): void {
		set_current_screen( 'front' );
		( new Tickets() )->empty_table();
	}

	/**
	 * @test
	 */
	public function it_should_drop_templates_from_front_end_lists(): void {
		[ $event, $template, $default ] = $this->create_event();
		$date                           = $this->get_dates( $event )[0];
		$this->insert_ticket_row( [ 'parent_id' => $template, 'post_id' => $event, 'occurrence_id' => $date->occurrence_id, 'name' => 'Row' ] );

		$this->assertSame( [ $default ], array_column( tribe( Module::class )->get_tickets( $event ), 'ID' ) );
		$this->assertSame( [ 'Row', 'Default' ], array_column( tribe( Module::class )->get_tickets( $date->provisional_id ), 'name' ) );
	}

	/**
	 * @test
	 */
	public function it_should_keep_templates_in_the_admin_for_who_can_edit_the_event(): void {
		[ $event, $template, $default ] = $this->create_event();
		set_current_screen( 'edit-post' );
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->assertSame( [ $template, $default ], array_column( tribe( Module::class )->get_tickets( $event ), 'ID' ) );
	}

	/**
	 * @test
	 */
	public function it_should_drop_templates_from_an_admin_request_of_a_visitor(): void {
		// A front-end AJAX request runs in the admin: is_admin() alone does not mean an editor is asking.
		[ $event, $template, $default ] = $this->create_event();
		set_current_screen( 'edit-post' );
		wp_set_current_user( 0 );

		$this->assertSame( [ $default ], array_column( tribe( Module::class )->get_tickets( $event ), 'ID' ) );
	}

	/**
	 * @test
	 */
	public function it_should_keep_a_template_added_by_code_out_of_the_cart_items(): void {
		[ $event, $template, $default ] = $this->create_event();
		$cart = tribe( Cart::class );
		$cart->add_ticket( $template, 1 );
		$cart->add_ticket( $default, 1 );

		try {
			$items = $cart->get_repository()->get_items_in_cart( true );
		} finally {
			$cart->clear_cart();
		}

		$this->assertSame( [ $default ], array_values( array_map( 'intval', array_column( $items, 'ticket_id' ) ) ) );
	}

	/**
	 * @test
	 */
	public function it_should_refuse_a_template_in_the_cart(): void {
		[ $event, $template, $default ] = $this->create_event();

		$this->assertSame( [ $default ], $this->add_to_cart( $event, [ $template, $default ] ) );
	}

	/**
	 * @test
	 */
	public function it_should_refuse_a_template_in_the_cart_when_ecp_provides_no_dates(): void {
		[ $event, $template, $default ] = $this->create_event();
		// As when ECP is deactivated: its custom tables never finish activating.
		global $wp_actions;
		$fired = $wp_actions['tec_events_pro_custom_tables_v1_fully_activated'] ?? null;
		unset( $wp_actions['tec_events_pro_custom_tables_v1_fully_activated'] );

		try {
			$added = $this->add_to_cart( $event, [ $template, $default ] );
			$names = array_column( tribe( Module::class )->get_tickets( $event ), 'ID' );
		} finally {
			if ( null !== $fired ) {
				$wp_actions['tec_events_pro_custom_tables_v1_fully_activated'] = $fired;
			}
		}

		$this->assertSame( [ $default ], $added );
		$this->assertSame( [ $default ], $names );
	}

	/**
	 * @test
	 */
	public function it_should_refuse_a_template_and_a_row_at_the_free_rsvp_order_endpoint(): void {
		[ $event, $template ] = $this->create_event();
		$row                  = $this->insert_ticket_row( [ 'parent_id' => $template, 'post_id' => $event, 'occurrence_id' => $this->get_dates( $event )[0]->occurrence_id, 'capacity' => 10, 'stock' => 10 ] );
		tribe( RSVP_V2_Controller::class )->register();
		do_action( 'rest_api_init' );
		$orders = static fn() => ( new \WP_Query( [ 'post_type' => 'tec_tc_order', 'post_status' => 'any', 'fields' => 'ids', 'posts_per_page' => -1 ] ) )->post_count;
		$before = $orders();

		foreach ( [ $template, $row ] as $ticket_id ) {
			$request = new \WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/v2/order' );
			$request->set_param( 'ticket_id', $ticket_id );
			$request->set_param( 'step', 'success' );
			$request->set_param( 'tribe_tickets', [ $ticket_id => [ 'quantity' => 1, 'attendees' => [ [ 'email' => 'visitor@example.test', 'full_name' => 'Visitor', 'order_status' => 'yes', 'optout' => false ] ] ] ] );

			rest_get_server()->dispatch( $request );
		}

		$this->assertSame( $before, $orders(), 'Neither a template nor a row may be ordered for free.' );
		$this->assertSame( 10, (int) tribe( Rows::class )->find( Ticket_ID::to_row_id( $row ) )->stock );
	}

	/**
	 * Runs the tickets of an add-to-cart request through the cart's checks.
	 *
	 * @param int   $event      The event.
	 * @param int[] $ticket_ids The tickets asked for, one of each.
	 *
	 * @return int[] The tickets the cart accepted.
	 */
	private function add_to_cart( int $event, array $ticket_ids ): array {
		$data = tribe( Cart::class )->prepare_data(
			[
				'tribe_tickets_ar_data' => [
					'tribe_tickets_post_id' => $event,
					'tribe_tickets_tickets' => array_map( static fn( int $id ) => [ 'ticket_id' => $id, 'quantity' => 1 ], $ticket_ids ),
				],
			]
		);

		return array_values( array_map( 'intval', array_column( $data['tickets'] ?? [], 'ticket_id' ) ) );
	}

	/**
	 * @return int[] The event, its template and its default ticket.
	 */
	private function create_event(): array {
		$event    = $this->create_recurring_event();
		$template = $this->create_tc_ticket( $event, 10, [ 'ticket_name' => 'Template' ] );
		update_post_meta( $template, '_type', Template_Guard::TICKET_TYPE );
		$default = $this->create_tc_ticket( $event, 5, [ 'ticket_name' => 'Default' ] );

		// Tickets are listed by menu order alone: give each its own, so tickets created in the same second keep an order.
		wp_update_post( [ 'ID' => $default, 'menu_order' => 1 ] );

		return [ $event, $template, $default ];
	}
}
