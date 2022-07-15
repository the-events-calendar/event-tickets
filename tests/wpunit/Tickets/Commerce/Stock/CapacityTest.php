<?php

namespace Tribe\Tickets\Commerce;

use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Provider;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

class CapacityTest extends \Codeception\TestCase\WPTestCase {

	use Ticket_Maker;

	/**
	 * @inheritDoc
	 */
	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();

		define( 'TEC_TICKETS_COMMERCE', true );

		tribe_register_provider( Provider::class );

		add_filter( 'tribe_tickets_ticket_object_is_ticket_cache_enabled', '__return_false' );
	}

	public function test_if_provider_is_loaded() {
		$provider = tribe( Module::class );

		$this->assertNotFalse( $provider );
	}
}