<?php

namespace Tribe\Tickets\Service_Providers;

use Tribe\Tickets\Shortcodes\Tribe_Tickets_Checkout;
use tad_DI52_ServiceProvider;

/**
 * Class Shortcode.
 *
 * @since   TBD
 * @package Tribe\Tickets\Service_Providers
 */
class Shortcode extends tad_DI52_ServiceProvider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since TBD
	 */
	public function register() {
		$this->container->singleton( 'tickets.service_providers.shortcode', $this );
		$this->container->singleton( static::class, $this );

		$this->hooks();
	}

	protected function hooks() {
		add_filter( 'tribe_shortcodes', [ $this, 'filter_register_shortcodes' ] );
	}

	/**
	 * Register shortcodes.
	 *
	 * @since TBD
	 *
	 * @param array $shortcodes An associative array of shortcodes in the shape `[ <slug> => <class> ]`.
	 *
	 * @return array
	 * @see   \Tribe\Shortcode\Manager::get_registered_shortcodes()
	 *
	 */
	public function filter_register_shortcodes( array $shortcodes ) {
		$shortcodes['tribe_tickets_checkout'] = Tribe_Tickets_Checkout::class;

		return $shortcodes;
	}
}