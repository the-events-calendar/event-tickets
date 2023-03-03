<?php

namespace TEC\Tickets\Emails\Tags;

class Provider extends \tad_DI52_ServiceProvider {

	/**
	 * @inheritDoc
	 */
	public function register() {
		$this->container->singleton( static::class );

		$this->register_tags();
	}

	public function register_tags() {
		$this->container->make( Ticket_Name::class );
	}
}