<?php

namespace TEC\Tickets\Commerce\Reports;

/**
 * Class Attendees_Tab
 *
 * Models the tab for the Attendees report on a post with tickets.
 *
 * @since 5.6.8
 */
class Attendees_Tab extends \Tribe__Tabbed_View__Tab {

	/**
	 * @inerhitDoc
	 * @since 5.6.8
	 */
	protected $visible = true;

	/**
	 * @inheritDoc
	 *
	 * @since 5.6.8
	 */
	protected $slug = 'tickets-attendees';

	/**
	 * @inheritDoc
	 *
	 * @since 5.6.8
	 *
	 * @return string|null
	 */
	public function get_label() {
		return esc_html__( 'Attendees', 'event-tickets' );
	}
}
