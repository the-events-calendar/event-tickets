<?php
/**
 * Class that handles interfacing with core Site Health.
 *
 * @since 5.6.0.1
 *
 * @package TEC\Tickets\Site_Health
 */

namespace TEC\Tickets\Site_Health;

use TEC\Common\Site_Health\Factory;
use TEC\Common\Site_Health\Info_Section_Abstract;
use TEC\Tickets\Site_Health\Subsections\Features\Tickets_Commerce_Subsection;
use TEC\Tickets\Site_Health\Subsections\Plugins\Plugin_Data_Subsection;

/**
 * Class Site_Health
 *
 * @since 5.6.0.1
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
		$this->label       = esc_html__(
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
	public function add_fields(): void {
		$subsections = [
			tribe( Plugin_Data_Subsection::class )->get_subsection(),
			tribe( Tickets_Commerce_Subsection::class )->get_subsection(),
		];

		/**
		 * Filters the subsections array to allow modifications for the Event Tickets Info Section.
		 *
		 * This filter allows external modification of the `$subsections` array. It can be used to add,
		 * remove, or modify the subsections before they are merged into the `$fields` array.
		 *
		 * @since 5.8.1
		 *
		 * @param array $subsections The array of subsections. Each subsection is an array of fields.
		 *
		 * @return array The modified array of subsections.
		 */
		$subsections = apply_filters( 'tec_tickets_site_health_subsections', $subsections );

		$fields = array_merge(
			...$subsections
		);

		// Add each field to the section.
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
