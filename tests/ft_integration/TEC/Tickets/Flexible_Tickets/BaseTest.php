<?php

namespace TEC\Tickets\Flexible_Tickets;

use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;

class BaseTest extends Controller_Test_Case {
	use SnapshotAssertions;

	protected string $controller_class = Base::class;

	/**
	 * It should disable Tickets and RSVPs for Series
	 *
	 * @test
	 */
	public function should_disable_tickets_and_rsv_ps_for_series(): void {
		$controller = $this->make_controller();

		$filtered = $controller->enable_ticket_forms_for_series( [
			'default' => true,
			'rsvp'    => true,
		] );

		$this->assertEquals( [
			'default'                  => false,
			'rsvp'                     => false,
			Series_Passes::TICKET_TYPE => true,
		], $filtered );
	}
}
