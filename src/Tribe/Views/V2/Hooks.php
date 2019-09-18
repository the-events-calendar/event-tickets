<?php
/**
 * Handles hooking all the actions and filters used by the module.
 *
 * To remove a filter:
 * remove_filter( 'some_filter', [ tribe( Tribe\Events\Tickets\Views\V2\Hooks::class ), 'some_filtering_method' ] );
 * remove_filter( 'some_filter', [ tribe( 'tickets.views.v2.hooks' ), 'some_filtering_method' ] );
 *
 * To remove an action:
 * remove_action( 'some_action', [ tribe( Tribe\Events\Tickets\Views\V2\Hooks::class ), 'some_method' ] );
 * remove_action( 'some_action', [ tribe( 'tickets.views.v2.hooks' ), 'some_method' ] );
 *
 * @since TBD
 *
 * @package Tribe\Events\Tickets\Views\V2
 */

namespace Tribe\Events\Tickets\Views\V2;

/**
 * Class Hooks.
 *
 * @since TBD
 *
 * @package Tribe\Events\Tickets\Views\V2
 */
class Hooks extends \tad_DI52_ServiceProvider {
	/**
	 * Binds and sets up implementations.
	 *
	 * @since TBD
	 */
	public function register() {
		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Filters the list of folders TEC will look up to find templates to add the ones defined by Tickets.
	 *
	 * @since TBD
	 *
	 * @param array $folders The current list of folders that will be searched template files.
	 *
	 * @return array The filtered list of folders that will be searched for the templates.
	 */
	public function filter_template_path_list( array $folders = [] ) {
		$folders[] = [
			'id'       => 'event-tickets',
			'priority' => 17,
			'path'     => \Tribe__Tickets__Main::instance()->plugin_path . 'src/views/v2',
		];

		return $folders;
	}

	/**
	 * Add tickets data to the event object.
	 *
	 * @since TBD
	 *
	 * @param array    $props An associative array of all the properties that will be set on the "decorated" post
	 *                        object.
	 * @param \WP_Post $post  The post object handled by the class.
	 *
	 * @return array The model properties. This value might be cached.
	 */
	public function add_tickets_data( $props, $event ) {

		$event_id = $event->ID;

		if ( ! tribe_events_has_tickets_on_sale( $event_id ) ) {
			$props['tickets_data'] = false;
			return $props;
		}

		// get an array for ticket and rsvp counts
		$types = \Tribe__Tickets__Tickets::get_ticket_counts( $event_id );

		// if no rsvp or tickets return
		if ( ! $types ) {
			$props['tickets_data'] = false;
			return $props;
		}

		$html  = [];
		$parts = [];

		// If we have tickets or RSVP, but everything is Sold Out then display the Sold Out message
		foreach ( $types as $type => $data ) {

			if ( ! $data['count'] ) {
				continue;
			}

			if ( ! $data['available'] ) {
				$parts[ $type . '_stock' ] = esc_html_x( 'Sold out', 'list view stock sold out', 'event-tickets' );

				// Only re-apply if we don't have a stock yet
				if ( empty( $html['stock'] ) ) {
					$html['stock'] = $parts[ $type . '_stock' ];
				}
			} else {
				$stock = $data['stock'];
				if ( $data['unlimited'] || ! $data['stock'] ) {
					// if unlimited tickets, tickets with no stock and rsvp, or no tickets and rsvp unlimited - hide the remaining count
					$stock = false;
				}

				$stock_html = '';

				if ( $stock ) {
					$threshold = \Tribe__Settings_Manager::get_option( 'ticket-display-tickets-left-threshold', 0 );

					/**
					 * Overwrites the threshold to display "# tickets left".
					 *
					 * @param int   $threshold Stock threshold to trigger display of "# tickets left"
					 * @param array $data      Ticket data.
					 * @param int   $event_id  Event ID.
					 *
					 * @since 4.10.1
					 */
					$threshold = absint( apply_filters( 'tribe_display_tickets_left_threshold', $threshold, $data, $event_id ) );

					if ( ! $threshold || $stock <= $threshold ) {

						$number = number_format_i18n( $stock );
						if ( 'rsvp' === $type ) {
							$text = _n( '%s spot left', '%s spots left', $stock, 'event-tickets' );
						} else {
							$text = _n( '%s ticket left', '%s tickets left', $stock, 'event-tickets' );
						}

						$stock_html = esc_html( sprintf( $text, $number ) );
					}
				}

				$parts[ $type . '_stock' ] = $html['stock'] = $stock_html;

				if ( 'rsvp' === $type ) {
					$link_label  = esc_html_x( 'RSVP Now', 'list view rsvp now ticket button', 'event-tickets' );
					$link_anchor = '#rsvp-now';
				} else {
					$link_label  = esc_html_x( 'Get Tickets', 'list view buy now ticket button', 'event-tickets' );
					$link_anchor = '#buy-tickets';
				}

				$parts[ $type . '_link' ] = (object) [ 'anchor' => get_the_permalink( $event->ID ) . $link_anchor, 'label' => $link_label ];

			}
		}

		$tickets_data = array_merge( $parts, $html );

		$props['tickets_data'] = (object) $tickets_data;

		return $props;
	}

	/**
	 * Adds the actions required by each Tickets Views v2 component.
	 *
	 * @since TBD
	 */
	protected function add_actions() {
		// silence is golden
	}

	/**
	 * Adds the filters required by each Tickets Views v2 component.
	 *
	 * @since TBD
	 */
	protected function add_filters() {
		add_filter( 'tribe_template_path_list', [ $this, 'filter_template_path_list' ] );
		add_filter( 'tribe_post_type_events__properties', [ $this, 'add_tickets_data' ], 20, 2 );
	}
}
