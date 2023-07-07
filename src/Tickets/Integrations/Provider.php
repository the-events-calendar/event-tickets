<?php
/**
 * Handles The Event Tickets integration.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Integrations
 */
namespace TEC\Tickets\Integrations;

use TEC\Common\Contracts\Service_Provider;


/**
 * Class Provider
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Integrations
 */
class Provider extends Service_Provider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since TBD
	 */
	public function register() {
		$this->container->singleton( static::class, $this );

		$this->container->register( Plugins\Yoast_Duplicate_Post\Duplicate_Post::class);
	}
}
