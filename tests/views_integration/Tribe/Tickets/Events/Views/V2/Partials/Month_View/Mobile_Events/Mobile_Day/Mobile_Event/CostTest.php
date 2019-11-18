<?php

namespace Tribe\Tickets\Events\Views\V2\Partials\Month_View\Mobile_Events\Mobile_Day\Mobile_Event;

use Tribe\Test\PHPUnit\Traits\With_Post_Remapping;
use Tribe\Test\Products\WPBrowser\Views\V2\HtmlPartialTestCase;

class CostTest extends HtmlPartialTestCase
{
	use With_Post_Remapping;

	protected $partial_path = 'month/mobile-events/mobile-day/mobile-event/cost';

	/**
	 * Test render with cost
	 */
	public function test_render_with_cost() {

		$event = $this->get_mock_event( 'events/single/1.json', [] );
		$event->cost = '$10';

		$this->assertMatchesSnapshot( $this->get_partial_html( [ 'event' => $event ] ) );
	}

	/**
	 * Test render without cost
	 */
	public function test_render_without_cost() {
		$event = $this->get_mock_event( 'events/single/1.json', [] );

		$this->assertMatchesSnapshot( $this->get_partial_html( [ 'event' => $event ] ) );
	}

}
