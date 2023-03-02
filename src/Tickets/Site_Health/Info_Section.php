<?php
/**
 * Class that handles interfacing with core Site Health.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Site_Health
 */

namespace TEC\Tickets\Site_Health;

use TEC\Common\Site_Health\Info_Section_Abstract;

/**
 * Class Site_Health
 *
 * @since   TBD

 * @package TEC\Tickets\Site_Health
 */
class Info_Section extends Info_Section_Abstract {
	/**
	 * Slug for the section.
	 *
	 * @since TBD
	 *
	 * @var string $slug
	 */
	protected static string $slug = 'tec-tickets';

	/**
	 * Label for the section.
	 *
	 * @since TBD
	 *
	 * @var string $label
	 */
	protected string $label;

	/**
	 * If we should show the count of fields in the site health info page.
	 *
	 * @since TBD
	 *
	 * @var bool $show_count
	 */
	protected bool $show_count = false;

	/**
	 * If this section is private.
	 *
	 * @since TBD
	 *
	 * @var bool $is_private
	 */
	protected bool $is_private = false;

	/**
	 * Description for the section.
	 *
	 * @since TBD
	 *
	 * @var string $description
	 */
	protected string $description;

	public function __construct() {
		$this->label       = esc_html__( 'Event Tickets', 'event-tickets' );
		$this->description = esc_html__( 'This section contains information on the Events Tickets Plugin.', 'event-tickets' );
	}

	/**
	 * Adds our default section to the Site Health Info tab.
	 *
	 * @since TBD
	 *
	 * @param array $info The debug information to be added to the core information page.
	 *
	 * @return array The debug information to be added to the core information page.
	 */
	public function add_fields() {
		$fields = [
			'ticketed_posts' => [
				'label' => esc_html__( 'Total ticketed posts', 'event-tickets' ),
				'value' => 100,
			],
		];

		return $fields;
	}
}

/**
number of ticketed events
number of RSVP'd events
 */
