<?php

namespace TEC\Tickets\Seating\Service;

use Codeception\TestCase\WPTestCase;

class Service_Test extends WPTestCase {
	protected string $controller_class = Service::class;

	/**
	 * @test
	 */
	public function it_should_get_frontend_url() {
		$service = tribe( Service::class );

		$this->assertEquals( 'https://seating.theeventscalendar.com', $service->get_frontend_url() );
		$this->assertEquals( 'https://seating.theeventscalendar.com/test/path', $service->get_frontend_url( '/test/path/' ) );
		$_GET['isNew'] = 0;
		$this->assertEquals( 'https://seating.theeventscalendar.com/test/path/2', $service->get_frontend_url( '/test/path/2' ) );
		$_GET['isNew'] = 'tree';
		$this->assertEquals( 'https://seating.theeventscalendar.com/test/path/2', $service->get_frontend_url( '/test/path/2' ) );
		$_GET['isNew'] = '1';
		$this->assertEquals( 'https://seating.theeventscalendar.com/test/path/2?isNew=1', $service->get_frontend_url( '/test/path/2' ) );
	}
}
