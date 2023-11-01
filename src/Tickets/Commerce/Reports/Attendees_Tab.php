<?php

namespace TEC\Tickets\Commerce\Reports;

/**
 * Class Attendees_Tab
 *
 * Models the tab for the Attendees report on a post with tickets.
 */
class Attendees_Tab extends \Tribe__Tabbed_View__Tab {

	/**
	 * @var bool
	 */
	protected $visible = true;

	/**
	 * @var string
	 */
	protected $slug = 'tickets-attendees';

	public function get_label() {
		return __( 'Attendees', 'event-tickets' );
	}
}
