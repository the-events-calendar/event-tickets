<?php

class Tribe__Tickets__Commerce__PayPal__Orders__Tab extends Tribe__Tabbed_View__Tab {
	/**
	 * @var bool
	 */
	protected $visible = true;

	public function get_slug() {
		return Tribe__Tickets__Commerce__PayPal__Orders__Report::$tab_slug;
	}

	public function get_label() {
		return __( 'Orders', 'event-tickets-plus' );
	}
}