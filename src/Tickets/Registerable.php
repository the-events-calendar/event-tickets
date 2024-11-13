<?php
/**
 * Registerable interface.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets;

/**
 * Interface Registerable
 *
 * @since TBD
 */
interface Registerable {

	/**
	 * Registers the class with WordPress hooks.
	 *
	 * @since TBD
	 *
	 * @return void The method does not return any value.
	 */
	public function register(): void;
}