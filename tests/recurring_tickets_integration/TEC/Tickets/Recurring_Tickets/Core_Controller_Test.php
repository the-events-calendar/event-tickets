<?php

namespace TEC\Tickets\Recurring_Tickets;

use TEC\Common\StellarWP\DB\DB;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Events_Pro\Custom_Tables\V1\Events\Provisional\ID_Generator;
use TEC\Events_Pro\Custom_Tables\V1\Models\Provisional_Post;
use TEC\Tickets\Recurring_Tickets\Tables\Tickets;

/**
 * The Core tier is always on: already sold table tickets must keep working whatever else is switched off.
 */
class Core_Controller_Test extends Controller_Test_Case {
	protected string $controller_class = Core_Controller::class;

	/**
	 * @after
	 */
	public function empty_table(): void {
		( new Tickets() )->empty_table();
	}

	/**
	 * @test
	 */
	public function it_should_be_active_by_default(): void {
		$this->assertTrue( $this->make_controller()->is_active() );
	}

	/**
	 * @test
	 */
	public function it_should_create_the_table_when_it_is_missing(): void {
		DB::query( DB::prepare( 'DROP TABLE IF EXISTS %i', Tickets::table_name() ) );

		$this->make_controller()->register();

		$this->assertSame( Tickets::table_name(), DB::get_var( DB::prepare( 'SHOW TABLES LIKE %s', Tickets::table_name() ) ) );
	}

	/**
	 * @test
	 */
	public function it_should_keep_the_table_and_its_rows_when_unregistered(): void {
		$controller = $this->make_controller();
		$controller->register();
		Tickets::insert(
			[
				'type'       => 'recurring',
				'post_id'    => 1,
				'name'       => 'General Admission',
				'price'      => 1050,
				'sales'      => 0,
				'menu_order' => 0,
				'created_at' => '2026-10-01 00:00:00',
			]
		);

		$controller->unregister();

		$this->assertSame( 1, Tickets::get_total_items() );
	}

	/**
	 * @test
	 */
	public function it_should_tell_ecp_a_table_ticket_id_is_not_a_date(): void {
		$this->make_controller()->register();
		$provisional = tribe( Provisional_Post::class );

		$this->assertFalse( $provisional->is_provisional_post_id( Ticket_ID::from_row_id( 5 ) ) );
		$this->assertFalse( $provisional->is_provisional_post_id( (string) Ticket_ID::base() ) );
		$this->assertTrue( $provisional->is_provisional_post_id( tribe( ID_Generator::class )->current() + 1 ), 'A date is still a date.' );
	}
}
