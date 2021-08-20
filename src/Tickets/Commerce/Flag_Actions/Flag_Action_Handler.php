<?php

namespace TEC\Tickets\Commerce\Flag_Actions;

/**
 * Class Flag_Action_Handler
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Flag_Actions
 */
class Flag_Action_Handler extends \tad_DI52_ServiceProvider {
	/**
	 * Flag Actions registered.
	 *
	 * @since TBD
	 *
	 * @var Flag_Action_Interface[]
	 */
	protected $flag_actions = [];

	/**
	 * Which classes we will load for order flag actions by default.
	 *
	 * @since TBD
	 *
	 * @var string[]
	 */
	protected $default_flag_actions = [
		Generate_Attendees::class,
	];

	/**
	 * Gets the flag actions registered.
	 *
	 * @since TBD
	 *
	 * @return Flag_Action_Interface[]
	 */
	public function get_all() {
		return $this->flag_actions;
	}

	/**
	 * Sets up all the Flag Action instances for the Classes registered in $default_flag_actions.
	 *
	 * @since TBD
	 */
	public function register() {
		foreach ( $this->default_flag_actions as $flag_action_class ) {
			// Spawn the new instance.
			$flag_action = new $flag_action_class;

			// Register as a singleton for internal ease of use.
			$this->container->singleton( $flag_action_class, $flag_action );

			// Collect this particular status instance in this class.
			$this->register_flag_action( $flag_action );
		}

		$this->container->singleton( static::class, $this );
	}

	/**
	 * Register a given flag action into the Handler, and hook the handling to WP.
	 *
	 * @since TBD
	 *
	 * @param Flag_Action_Interface $flag_action Which flag action we are registering.
	 */
	public function register_flag_action( Flag_Action_Interface $flag_action ) {
		$this->flag_actions[] = $flag_action;
		$flag_action->hook();
	}
}