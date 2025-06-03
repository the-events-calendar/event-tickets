<?php
/**
 * Service Provider for interfacing with TEC\Tickets\Admin\Notice\Help_Hub.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Admin\Notice\Help_Hub
 */

namespace TEC\Tickets\Admin\Help_Hub;

use TEC\Common\Contracts\Service_Provider;

/**
 * Class Provider
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Admin\Notice\Help_Hub
 */
class Provider extends Service_Provider {

	/**
	 * Register implementations.
	 *
	 * @since 5.24.0
	 */
	public function register() {
		$this->container->singleton( self::class, $this );
		$this->container->bind( ET_Hub_Resource_Data::class );
	}
}
