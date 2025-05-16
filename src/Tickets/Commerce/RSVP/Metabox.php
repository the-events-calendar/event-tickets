<?php
/**
 * Handles RSVP metabox functionality in Tickets Commerce.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\RSVP
 */

namespace TEC\Tickets\Commerce\RSVP;

use TEC\Tickets\Admin\Panel_Data;
use TEC\Tickets\Admin\Panels_Data\Ticket_Panel_Data;
use TEC\Tickets\Event;
use Tribe__Tickets__Main;
use Tribe__Date_Utils;
use Tribe__Tickets__Tickets;
use WP_Post;
use Tribe__Tickets__Ticket_Object;

/**
 * Class Metabox.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\RSVP
 */
class Metabox {

	/**
	 * Configures the RSVP metabox for the given post type.
	 *
	 * @since TBD
	 *
	 * @param string|null $post_type The post type to configure the metabox for.
	 */
	public function configure( $post_type = null ) {
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

		// If we get here means that we will need Thickbox
		//add_thickbox();
	}

	public function render( $post_id ) {
		$original_id = $post_id instanceof WP_Post ? $post_id->ID : (int) $post_id;
		$post_id = Event::filter_event_id( $original_id, 'tickets-metabox-render' );

		$post = get_post( $post_id );

		// Prepare all the variables required.
		$start_date = date( 'Y-m-d H:00:00' );
		$end_date   = date( 'Y-m-d H:00:00' );
		$start_time = Tribe__Date_Utils::time_only( $start_date, false );
		$end_time   = Tribe__Date_Utils::time_only( $start_date, false );

		$tc_rsvp = $this->get_tc_rsvp_ticket(  Tribe__Tickets__Tickets::get_event_tickets( $post->ID ) );

		/** @var Tribe__Tickets__Admin__Views $admin_views */
		$admin_views = tribe( 'tickets.admin.views' );

		$context = get_defined_vars();

		// Add the data required by each panel to render correctly.
		$context = array_merge( $context, ( new Ticket_Panel_Data( $post->ID, $tc_rsvp->ID ) )->to_array() );

		$context['rsvp_id'] = $tc_rsvp->ID ?? null;
		$context['rsvp_limit'] = $tc_rsvp->capacity() ?? '';

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
	 * @param array $tickets List of ticket objects.
	 *
	 * @return Tribe__Tickets__Ticket_Object|null Matching ticket object or null if not found.
	 */
	public function get_tc_rsvp_ticket( array $tickets ) {
		foreach ( $tickets as $ticket ) {
			if ( $ticket instanceof Tribe__Tickets__Ticket_Object && $ticket->type() === 'tc-rsvp' ) {
				return $ticket;
			}
		}

		return null;
	}

}
