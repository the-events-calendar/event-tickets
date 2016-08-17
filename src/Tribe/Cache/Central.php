<?php


class Tribe__Tickets__Cache__Central {

	/**
	 * @var self
	 */
	protected static $instance;

	/**
	 * @var Tribe__Tickets__Cache__Cache_Interface
	 */
	protected $cache;

	/**
	 *  The class singleton constructor.
	 *
	 * @return Tribe__Tickets__Cache__Central
	 */
	public static function instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Tribe__Tickets__Cache__Central constructor.
	 *
	 * @param Tribe__Tickets__Cache__Cache_Interface|null $cache An injectable cache object instance.
	 */
	public function __construct( Tribe__Tickets__Cache__Cache_Interface $cache = null ) {

		$this->cache = $cache;
	}

	/**
	 * Hooks the class to relevant filters.
	 */
	public function hook() {
		add_action( 'event_tickets_after_save_ticket', array( $this->cache, 'reset_all' ) );
	}
}