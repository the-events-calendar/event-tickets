<?php
/**
 * Handles The Events Virtual integration.
 *
 * @since TBD
 *
 * @pacakge TEC\Tickets\Integrations\Plugins\Events_Virtual
 */

namespace TEC\Tickets\Integrations\Plugins\Events_Virtual;

use TEC\Common\Integrations\Traits\Plugin_Integration;
use TEC\Tickets\Integrations\Integration_Abstract;

/**
 * Class Provider.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Integrations\Plugins\Events_Virtual
 */
class Provider extends Integration_Abstract {
	use Plugin_Integration;
	
	/**
	 * @inheritDoc
	 */
	public static function get_slug(): string {
		return 'events-virtual';
	}
	
	/**
	 * @inheritDoc
	 */
	public function load_conditionals(): bool {
		return function_exists( 'tribe_events_virtual_load' );
	}
	
	/**
	 * @inheritDoc
	 */
	protected function load(): void {
		$this->container->register( Flexible_Tickets::class, Flexible_Tickets::class );
	}
}
