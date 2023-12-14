<?php
/**
 * Class that handles interfacing with core Site Health.
 *
 * @since   5.6.0.1
 *
 * @package TEC\Tickets\Site_Health
 */

namespace TEC\Tickets\Site_Health;

use TEC\Common\Site_Health\Info_Section_Abstract;
use TEC\Common\Site_Health\Factory;

/**
 * Class Site_Health
 *
 * @since   5.6.0.1
 * @package TEC\Tickets\Site_Health
 */
class Info_Section extends Info_Section_Abstract {
	/**
	 * Slug for the section.
	 *
	 * @since 5.6.0.1
	 *
	 * @var string $slug
	 */
	protected static string $slug = 'tec-tickets';

	/**
	 * Label for the section.
	 *
	 * @since 5.6.0.1
	 *
	 * @var string $label
	 */
	protected string $label;

	/**
	 * If we should show the count of fields in the site health info page.
	 *
	 * @since 5.6.0.1
	 *
	 * @var bool $show_count
	 */
	protected bool $show_count = false;

	/**
	 * If this section is private.
	 *
	 * @since 5.6.0.1
	 *
	 * @var bool $is_private
	 */
	protected bool $is_private = false;

	/**
	 * Description for the section.
	 *
	 * @since 5.6.0.1
	 *
	 * @var string $description
	 */
	protected string $description;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->label = esc_html__(
			'Event Tickets',
			'event-tickets'
		);
		$this->description = esc_html__(
			'This section contains information on the Events Tickets Plugin.',
			'event-tickets'
		);
		$this->add_fields();
	}

	/**
	 * Adds our default section to the Site Health Info tab.
	 *
	 * @since 5.6.0.1
	 */
	public function add_fields() {
		$fields = [];

		$fields = array_merge(
			$fields,
			tribe( Plugin_Data_Subsection::class )->get_subsection()
		);
		$fields = array_merge(
			$fields,
			tribe( The_Events_Calendar_Subsection::class )->get_subsection()
		);
		$fields = array_merge(
			$fields,
			tribe( Tickets_Commerce_Subsection::class )->get_subsection()
		);

		printr(
			$fields,
			"I am here"
		);
		foreach ( $fields as $field ) {
			$this->add_field(
				Factory::generate_generic_field(
					$field['id'],
					$field['title'],
					$field['value'],
					$field['priority']
				)
			);
		}
	}
}
