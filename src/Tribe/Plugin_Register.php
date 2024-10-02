<?php
/**
 * Plugin Register
 */

//phpcs:disable PEAR.NamingConventions.ValidClassName.Invalid,StellarWP.Classes.ValidClassName.NotSnakeCase
/**
 * Class Tribe__Tickets__Plugin_Register
 */
class Tribe__Tickets__Plugin_Register extends Tribe__Abstract_Plugin_Register {

	/**
	 * The main class of this plugin.
	 *
	 * @var string
	 */
	protected $main_class = 'Tribe__Tickets__Main';

	/**
	 * Dependencies: AKA Min plugin versions.
	 *
	 * @var array<string,string>
	 */
	protected $dependencies = [
		'addon-dependencies' => [
			'Tribe__Tickets_Plus__Main'               => '5.9.2-dev',
			'Tribe__Events__Community__Tickets__Main' => '4.9.3-dev',
			'Tribe__Events__Main'                     => '6.7.0-dev',
		],
	];

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->base_dir = EVENT_TICKETS_MAIN_PLUGIN_FILE;
		$this->version  = Tribe__Tickets__Main::VERSION;

		$this->register_plugin();
	}
}
