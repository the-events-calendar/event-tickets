<?php
/**
 * Displays a list of the Series Upcoming Events.
 *
 * @since 5.8.4
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes;
 */

namespace TEC\Tickets\Flexible_Tickets\Series_Passes\Emails;

use Tribe__Template as Template;

/**
 * Class Upcoming_Series_Events_List.
 *
 * @since 5.8.4
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes;
 */
class Upcoming_Series_Events_List {
	/**
	 * The template instance.
	 *
	 * @since 5.8.4
	 *
	 * @var Template|null
	 */
	private ?Template $template = null;

	/**
	 * The event IDs.
	 *
	 * @since 5.8.4
	 *
	 * @var int[]
	 */
	private array $event_ids = [];

	/**
	 * A reference to the Upcoming Events instance.
	 *
	 * @since 5.8.4
	 *
	 * @var Upcoming_Events
	 */
	private Upcoming_Events $upcoming_events;

	/**
	 * Upcoming_Series_Events_List constructor.
	 *
	 * @since 5.8.4
	 *
	 * @param Upcoming_Events $upcoming_events The Upcoming Events instance.
	 */
	public function __construct( Upcoming_Events $upcoming_events ) {
		$this->upcoming_events = $upcoming_events;
	}

	/**
	 * Sets the template instance.
	 *
	 * @since 5.8.4
	 *
	 * @param Template $template The template instance.
	 *
	 * @return $this The current instance, for method chaining.
	 */
	public function set_template( Template $template ): self {
		$this->template = $template;

		return $this;
	}

	/**
	 * Renders the list of upcoming Events part of the Series the Email is being sent for.
	 *
	 * @since 5.8.4
	 *
	 * @param int $series_id The Series ID.
	 *
	 * @return void
	 */
	public function render( int $series_id ): void {
		[ $events, $found ] = $this->upcoming_events->fetch( $series_id );

		if ( $found === 0 ) {
			// Nothing to print.
			return;
		}

		$this->event_ids = $events;

		if ( $found > count( $events ) ) {
			// Show the Series Link only if the number of fetched events is less than the total found.
			$series_link = get_post_permalink( $series_id );
		} else {
			$series_link = '';
		}

		$this->template->template(
			'template-parts/body/series-events-list',
			[
				// translators: %s: The lowercase events label, e.g. 'events'.
				'title'            => sprintf(
					_x( 'Upcoming %s in this Series', 'Series Pass Email upcoming events list title', 'event-tickets' ),
					tribe_get_event_label_plural()
				),
				'series_id'        => $series_id,
				'events'           => array_map( 'tribe_get_event', $events ),
				'series_link'      => $series_link,
				'series_link_text' =>
					sprintf(
						// translators: %1$d: The number of events in the series. %2$s: The label for the event type.
						_x(
							'View all %1$d %2$s in this series',
							'Series Pass Email upcoming events list link text',
							'event-tickets'
						),
						$found,
						$found > 1 ? tribe_get_event_label_plural() : tribe_get_event_label_singular()
					),
			]
		);
	}

	/**
	 * Retrieves the event IDs last used in the render method.
	 *
	 * @since 5.8.4
	 *
	 * @return int[] The event IDs last used in the render method.
	 */
	public function get_event_ids(): array {
		return $this->event_ids;
	}
}
