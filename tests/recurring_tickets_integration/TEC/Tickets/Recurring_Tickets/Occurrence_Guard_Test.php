<?php

namespace TEC\Tickets\Recurring_Tickets;

use Codeception\TestCase\WPTestCase;
use TEC\Events\Custom_Tables\V1\Models\Occurrence;
use TEC\Events_Pro\Custom_Tables\V1\Events\Provisional\ID_Generator;
use TEC\Tickets\Tests\Recurring_Tickets\Ticket_Rows;

/**
 * ECP reads any ID above its own base as a date. A table ticket ID must never come back as a date's event.
 */
class Occurrence_Guard_Test extends WPTestCase {
	use Ticket_Rows;

	/**
	 * @test
	 */
	public function it_should_leave_a_table_ticket_id_unchanged_even_when_a_date_has_the_matching_id(): void {
		$event     = $this->create_recurring_event();
		$date      = $this->get_dates( $event )[0];
		$ticket_id = Ticket_ID::from_row_id( 5 );
		/*
		 * Move ECP's base so the ticket ID minus the base is a real date's occurrence ID.
		 * Changing the occurrence ID instead would raise the table's AUTO_INCREMENT, which a rollback does not undo.
		 */
		$base = $ticket_id - $date->occurrence_id;
		add_filter( 'pre_option_' . tribe( ID_Generator::class )->option_name(), static fn() => $base );

		$this->assertSame( $ticket_id, Occurrence::normalize_id( $ticket_id ) );
	}

	/**
	 * @test
	 */
	public function it_should_normalize_dates_and_posts_as_before(): void {
		$event = $this->create_recurring_event();
		$date  = $this->get_dates( $event )[1];

		$this->assertSame( $event, Occurrence::normalize_id( $date->provisional_id ) );
		$this->assertSame( $event, Occurrence::normalize_id( $event ) );
	}
}
