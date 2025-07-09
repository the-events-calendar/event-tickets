<?php
/**
 * Service Provider for interfacing with TEC\Common\Notifications.
 *
 * @since 5.19.3
 *
 * @package TEC\Tickets\Notifications
 */

namespace TEC\Tickets\Notifications;

use TEC\Common\Contracts\Service_Provider;

/**
 * Class Provider
 *
 * @since 5.19.3
 * @package TEC\Tickets\Notifications
 */
class Provider extends Service_Provider {

	/**
	 * Handles the registering of the provider.
	 *
	 * @since 5.19.3
	 */
	public function register() {
		$this->container->register_on_action( 'tec_common_ian_loaded', Notifications::class );
	}
}
