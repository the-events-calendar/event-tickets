<?php

namespace TEC\Tickets\Relative_Sale_Dates;

use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Admin\Panels_Data\Ticket_Panel_Data;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\Relative_Sale_Dates_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;

class Classic_Panel_Data_Test extends Controller_Test_Case {
	use Relative_Sale_Dates_Maker;
	use Ticket_Maker;
	use With_Tickets_Commerce;

	protected $controller_class = Classic_Panel_Data::class;

	/**
	 * @before
	 */
	public function register_controller(): void {
		$this->make_controller()->register();
	}

	/**
	 * @test
	 */
	public function should_add_the_stored_rule_to_the_classic_ticket_panel_data(): void {
		$event_id  = $this->create_event( '2027-06-24 19:00:00' );
		$ticket_id = $this->create_tc_ticket( $event_id );
		$rule      = [ 'start' => $this->relative( 2, Rule::UNIT_WEEKS ), 'end' => [ 'mode' => 'default' ] ];
		tribe( Rule_Store::class )->save( $ticket_id, $rule );

		$data = ( new Ticket_Panel_Data( $event_id, $ticket_id ) )->to_array();

		$this->assertSame( $rule, $data['relative_sale_dates'] );
	}

	/**
	 * @test
	 */
	public function should_add_null_to_the_classic_ticket_panel_data_of_a_ticket_without_a_rule(): void {
		$event_id  = $this->create_event( '2027-06-24 19:00:00' );
		$ticket_id = $this->create_tc_ticket( $event_id );

		$data = ( new Ticket_Panel_Data( $event_id, $ticket_id ) )->to_array();

		$this->assertArrayHasKey( 'relative_sale_dates', $data );
		$this->assertNull( $data['relative_sale_dates'] );
	}

	/**
	 * @test
	 */
	public function should_add_null_to_the_classic_ticket_panel_data_of_a_new_ticket(): void {
		$data = ( new Ticket_Panel_Data( $this->create_event( '2027-06-24 19:00:00' ) ) )->to_array();

		$this->assertArrayHasKey( 'relative_sale_dates', $data );
		$this->assertNull( $data['relative_sale_dates'] );
	}
}
