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
use TEC\Tickets\Site_Health\Fieldset\Commerce;
use TEC\Tickets\Site_Health\Fieldset\Settings;
use Tribe__Events__Main;
use Tribe__Utils__Array as Arr;

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
	 * Constructor for the Site Health section.
	 *
	 * @since 5.6.0.1
	 */
	public function __construct() {
		$this->label       = esc_html__( 'Event Tickets', 'event-tickets' );
		$this->description = esc_html__( 'This section contains information on the Events Tickets Plugin.', 'event-tickets' );
		$this->add_fields();
	}

	/**
	 * Include the fields to this section.
	 *
	 * @since 5.6.0.1
	 */
	protected function add_fields(): void {
		tribe( Settings::class )->register_fields_to( $this );
		tribe( Commerce::class )->register_fields_to( $this );

		$this->add_field(
			Factory::generate_generic_field(
				'previous_versions',
				esc_html__( 'Previous ET versions', 'event-tickets' ),
				Arr::to_list( array_filter( (array) tribe_get_option( 'previous_event_tickets_versions', [] ) ), ', ' ),
				20
			)
		);

		$this->add_field(
			Factory::generate_generic_field(
				'ticketed_posts',
				esc_html__( 'Total ticketed posts', 'event-tickets' ),
				tribe( 'tickets.post-repository' )->per_page( -1 )->where( 'has_tickets' )->count(),
				30
			)
		);

		$this->add_field(
			Factory::generate_generic_field(
				'rsvp_posts',
				esc_html__( 'Total posts with RSVPs', 'event-tickets' ),
				tribe( 'tickets.post-repository' )->per_page( -1 )->where( 'has_rsvp' )->count(),
				40
			)
		);

		if ( class_exists( 'Tribe__Events__Main' ) ) {
			$this->add_field(
				Factory::generate_generic_field(
					'ticketed_events',
					esc_html__( 'Total ticketed events', 'event-tickets' ),
					tribe( 'tickets.event-repository' )->per_page( -1 )->where( 'has_tickets' )->count(),
					50
				)
			);

			$this->add_field(
				Factory::generate_generic_field(
					'rsvp_events',
					esc_html__( 'Total events with RSVPs', 'event-tickets' ),
					tribe( 'tickets.event-repository' )->per_page( -1 )->where( 'has_rsvp' )->count(),
					60
				)
			);
		}
	}
}
