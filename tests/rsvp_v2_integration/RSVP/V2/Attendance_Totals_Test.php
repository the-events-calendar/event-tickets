<?php
/**
 * Tests for the V2 Attendance_Totals class.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */

namespace TEC\Tickets\RSVP\V2;

use Codeception\TestCase\WPTestCase;

/**
 * Class Attendance_Totals_Test
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */
class Attendance_Totals_Test extends WPTestCase {

	/**
	 * @test
	 */
	public function it_should_be_instantiable(): void {
		$totals = tribe( Attendance_Totals::class );

		$this->assertInstanceOf( Attendance_Totals::class, $totals );
	}
}
