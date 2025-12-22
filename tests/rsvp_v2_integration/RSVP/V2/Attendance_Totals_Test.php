<?php

namespace TEC\Tickets\RSVP\V2;

use Codeception\TestCase\WPTestCase;

class Attendance_Totals_Test extends WPTestCase {
	/**
	 * @test
	 */
	public function it_should_be_instantiable(): void {
		$totals = tribe( Attendance_Totals::class );

		$this->assertInstanceOf( Attendance_Totals::class, $totals );
	}
}
