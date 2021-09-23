<?php

namespace Tribe\Tickets\Test\Partials;

use Tribe__Tickets__Admin__Views;

/**
 * Class V2AdminTestCase for snapshot testing.
 * @package namespace Tribe\Tickets\Test\Partials
 */
abstract class V2AdminTestCase extends V2TestCase {

	public function setUp() {
		// before
		parent::setUp();
	}

	/**
	 * ETP Template class instance.
	 *
	 * @return Tribe__Tickets_Plus__Admin__Views
	 */
	public function template_class() {
		return tribe( 'tickets.admin.views' );
	}
}
