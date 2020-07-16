<?php

namespace Tribe\Tickets\Promoter;

use tad_DI52_ServiceProvider;
use Tribe\Tickets\Promoter\Triggers\Dispatcher;
use Tribe\Tickets\Promoter\Triggers\Factory;
use Tribe\Tickets\Promoter\Triggers\Observers\Commerce;
use Tribe\Tickets\Promoter\Triggers\Observers\RSVP;
use Tribe__Tickets__Promoter__Integration;
use Tribe__Tickets__Promoter__Observer;

/**
 * Class Tribe__Tickets__Service_Providers__Promoter
 *
 * @since TBD
 */
class Service_Provider extends tad_DI52_ServiceProvider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since TBD
	 */
	public function register() {
		$this->container->singleton( 'tickets.promoter.integration', Tribe__Tickets__Promoter__Integration::class, [ 'hook' ] );
		$this->container->singleton( 'tickets.promoter.observer', Tribe__Tickets__Promoter__Observer::class, [ 'hook' ] );
		$this->container->singleton( 'tickets.promoter.triggers.factory', Factory::class, [ 'hook' ] );
		$this->container->singleton( 'tickets.promoter.triggers.dispatcher', Dispatcher::class, [ 'hook' ] );
		$this->container->singleton( 'tickets.promoter.triggers.observers.commerce', Commerce::class, [ 'hook' ] );
		$this->container->singleton( 'tickets.promoter.triggers.observers.rsvp', RSVP::class, [ 'hook' ] );

		$this->load();
	}

	/**
	 * Any hooking for any class needs happen here.
	 *
	 * In place of delegating the hooking responsibility to the single classes they are all hooked here.
	 *
	 * @since TBD
	 */
	protected function load() {
		tribe( 'tickets.promoter.integration' );
		tribe( 'tickets.promoter.observer' );
		tribe( 'tickets.promoter.triggers.factory' );
		tribe( 'tickets.promoter.triggers.dispatcher' );
		tribe( 'tickets.promoter.triggers.observers.rsvp' );
		tribe( 'tickets.promoter.triggers.observers.commerce' );
	}
}
