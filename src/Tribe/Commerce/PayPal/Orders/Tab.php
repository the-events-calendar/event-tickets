<?php

/**
 * Class Tribe__Tickets__Commerce__PayPal__Orders__Tab
 *
 * @since TBD
 */
class Tribe__Tickets__Commerce__PayPal__Orders__Tab extends Tribe__Tabbed_View__Tab {
	/**
	 * @var bool
	 */
	protected $visible = true;

	/**
	 * Returns this tab slug.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_slug() {
		return Tribe__Tickets__Commerce__PayPal__Orders__Report::$tab_slug;
	}

	/**
	 * Returns this tab label
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_label() {
		return __( 'PayPal Orders', 'event-tickets' );
	}
}
