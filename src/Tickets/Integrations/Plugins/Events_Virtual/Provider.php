<?php

namespace TEC\Tickets\Integrations\Plugins\Events_Virtual;

use TEC\Tickets\Integrations\Plugins\Events_Virtual\Flexible_Tickets as Flexible_Tickets;
use TEC\Common\Integrations\Traits\Plugin_Integration;
use TEC\Tickets\Integrations\Integration_Abstract;
use Tribe\Events\Virtual\Compatibility\Event_Tickets\Template_Modifications as Events_Virtual_Template_Modifications;

class Provider extends Integration_Abstract {
	use Plugin_Integration;
	public static function get_slug(): string {
		return 'events-virtual';
	}
	
	public function load_conditionals(): bool {
		return function_exists( 'tribe_events_virtual_load' );
	}
	
	protected function load(): void {
		$this->container->register( Flexible_Tickets::class, Flexible_Tickets::class );
	}
}