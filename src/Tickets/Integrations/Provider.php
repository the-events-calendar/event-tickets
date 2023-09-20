<?php
/**
 * Handles The Event Tickets integration.
 *
 * @since 5.6.3
 *
 * @package TEC\Tickets\Integrations
 */
namespace TEC\Tickets\Integrations;

use TEC\Common\Contracts\Service_Provider;


/**
 * Class Provider
 *
 * @since 5.6.3
 *
 * @package TEC\Tickets\Integrations
 */
class Provider extends Service_Provider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 5.6.3
	 */
	public function register() {
		$this->container->singleton( static::class, $this );

		$this->container->register( Plugins\Yoast_Duplicate_Post\Duplicate_Post::class);

		$this->container->register( Themes\Divi\Provider::class);
	}
}
