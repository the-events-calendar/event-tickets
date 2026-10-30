<?php

namespace TEC\Tickets\Recurring_Tickets;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Recurring_Tickets\Tables\Tickets;
use TEC\Tickets\Tests\Recurring_Tickets\Ticket_Rows;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use Tribe__Tickets__Tickets as Tickets_Base;
use WP_Post;

/**
 * The legacy loaders answer for a table ticket instead of giving up because it is not a post.
 */
class Legacy_Load_Guards_Test extends WPTestCase {
	use Ticket_Rows;

	/**
	 * @after
	 */
	public function empty_table(): void {
		( new Tickets() )->empty_table();
	}

	/**
	 * @test
	 */
	public function it_should_load_a_table_ticket(): void {
		$event = $this->create_recurring_event();
		$date  = $this->get_dates( $event )[2];
		$id    = $this->insert_ticket_row( [ 'post_id' => $event, 'occurrence_id' => $date->occurrence_id, 'name' => 'Evening' ] );

		$ticket = Tickets_Base::load_ticket_object( $id );

		$this->assertInstanceOf( Ticket_Object::class, $ticket );
		$this->assertSame( $id, $ticket->ID );
		$this->assertSame( 'Evening', $ticket->name );
		$this->assertSame( $date->provisional_id, $ticket->get_event_id() );
	}

	/**
	 * @test
	 */
	public function it_should_load_nothing_for_a_table_ticket_id_without_a_row(): void {
		$this->assertNull( Tickets_Base::load_ticket_object( Ticket_ID::from_row_id( 999 ) ) );
	}

	/**
	 * @test
	 */
	public function it_should_answer_the_date_as_the_event_of_a_table_ticket(): void {
		$event = $this->create_recurring_event();
		$date  = $this->get_dates( $event )[1];
		$id    = $this->insert_ticket_row( [ 'post_id' => $event, 'occurrence_id' => $date->occurrence_id ] );

		$found = tribe( Module::class )->get_event_for_ticket( $id );

		$this->assertInstanceOf( WP_Post::class, $found );
		$this->assertSame( $date->provisional_id, $found->ID );
		$this->assertSame( $found->ID, Tickets_Base::load_ticket_object( $id )->get_event()->ID );
	}

	/**
	 * @test
	 */
	public function it_should_answer_no_event_for_a_table_ticket_id_without_a_row(): void {
		$this->assertFalse( tribe( Module::class )->get_event_for_ticket( Ticket_ID::from_row_id( 999 ) ) );
	}
}
