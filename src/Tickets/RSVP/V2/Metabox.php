<?php
/**
 * V2 Metabox class for RSVP.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */

namespace TEC\Tickets\RSVP\V2;

use TEC\Tickets\Admin\Panels_Data\Ticket_Panel_Data;
use TEC\Tickets\Event;
use Tribe__Tickets__Admin__Views as Admin_Views;
use Tribe__Tickets__Main;
use Tribe__Date_Utils;
use WP_Post;
use Tribe__Tickets__Ticket_Object;

/**
 * Class Metabox
 *
 * Handles RSVP-specific metabox rendering and data for V2 implementation.
 * V2 RSVP uses TC (Tickets Commerce) infrastructure.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */
class Metabox {
	/**
	 * Configures the RSVP metabox for the given post type.
	 *
	 * @since TBD
	 *
	 * @param string|null $post_type The post type to configure the metabox for.
	 */
	public function add( $post_type = null ) {
		if ( ! in_array( $post_type, Tribe__Tickets__Main::instance()->post_types() ) ) {
			return;
		}

		add_meta_box(
			'tec-tickets-commerce-rsvp',
			_x( 'RSVP', 'RSVP metabox header', 'event-tickets' ),
			[ $this, 'render' ],
			$post_type,
			'normal',
			'high',
			[
				'__back_compat_meta_box' => true,
			]
		);
	}

	/**
	 * Renders the RSVP metabox for the event editor in the admin area.
	 *
	 * @since TBD
	 *
	 * @param WP_Post|int $post_id The post ID or WP_Post object for the event.
	 *
	 * @return string The rendered HTML template for the RSVP metabox.
	 */
	public function render( $post_id ): string {
		$original_id = $post_id instanceof WP_Post ? $post_id->ID : (int) $post_id;
		$post_id     = Event::filter_event_id( $original_id, 'tickets-metabox-render' );

		$post = get_post( $post_id );

		// Prepare all the variables required.
		$start_date = wp_date( 'Y-m-d H:00:00' );
		$end_date   = wp_date( 'Y-m-d H:00:00' );
		$start_time = Tribe__Date_Utils::time_only( $start_date, false );
		$end_time   = Tribe__Date_Utils::time_only( $start_date, false );

		$tc_rsvp = $this->get_tc_rsvp_ticket( $post->ID );

		/** @var Admin_Views $admin_views */
		$admin_views = tribe( 'tickets.admin.views' );

		$context = get_defined_vars();

		$rsvp_id = $tc_rsvp instanceof Tribe__Tickets__Ticket_Object ? $tc_rsvp->ID : null;
		$context = array_merge( $context, ( new Ticket_Panel_Data( $post->ID, $rsvp_id ) )->to_array() );

		// Add the data required by each panel to render correctly.
		$context['rsvp_id']        = 0;
		$context['show_not_going'] = '';
		$context['rsvp_limit']     = '';
		$context['ticket_type']    = Constants::TC_RSVP_TYPE;

		if ( $tc_rsvp instanceof Tribe__Tickets__Ticket_Object ) {
			$context['rsvp_id']        = $tc_rsvp->ID;
			$capacity                  = $tc_rsvp->capacity();
			$context['rsvp_limit']     = $capacity === - 1 ? '' : $capacity;
			$context['show_not_going'] = tribe_is_truthy(
				get_post_meta( $tc_rsvp->ID, Constants::SHOW_NOT_GOING_META_KEY, true )
			);
		}

		return $admin_views->template(
			[ 'editor', 'rsvp', 'metabox' ],
			$context
		);
	}

	/**
	 * Get first ticket of type tc-rsvp
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post ID to get the ticket for.
	 *
	 * @return Tribe__Tickets__Ticket_Object|null Matching ticket object or null if not found.
	 */
	private function get_tc_rsvp_ticket( int $post_id ): ?Tribe__Tickets__Ticket_Object {
		$ticket_id = tribe( 'tickets.ticket-repository.rsvp' )
			->where( 'event', $post_id )
			->first_id();

		if ( ! $ticket_id ) {
			return null;
		}

		return tribe( 'tickets.rsvp' )->get_ticket( $post_id, $ticket_id );
	}
}
