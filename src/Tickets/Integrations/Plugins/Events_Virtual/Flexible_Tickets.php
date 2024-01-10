<?php

namespace TEC\Tickets\Integrations\Plugins\Events_Virtual;

use TEC\Common\Contracts\Provider\Controller;
use Tribe\Events\Virtual\Compatibility\Event_Tickets\Template_Modifications as Events_Virtual_Template_Modifications;
use Tribe__Tickets__Tickets as Tickets;
use TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes;
use WP_Post;

/**
 * Class Flexible_Tickets for Virtual Events.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Integrations\Plugins\Events_Virtual
 */
class Flexible_Tickets extends Controller {
	/**
	 * @inheritDoc
	 */
	protected function do_register(): void {
		add_filter( 'tec_events_virtual_user_has_ticket', [ $this, 'filter_events_virtual_show_to_content' ], 10, 3 );
	}
	
	/**
	 * @inheritDoc
	 */
	public function unregister(): void {
		remove_filter( 'tec_events_virtual_user_has_ticket', [ $this, 'filter_events_virtual_show_to_content' ], 10, 3 );
	}
	
	/**
	 * Filters the content of the virtual event show page.
	 *
	 * @since TBD
	 *
	 * @param boolean $has_ticket Whether the current user has a ticket for the event.
	 * @param WP_Post $event      The post object or ID of the viewed event.
	 * @param int     $user_id    ID of the current user.
	 *
	 * @return bool Whether the current user can view the content.
	 */
	public function filter_events_virtual_show_to_content( bool $has_ticket, WP_Post $event, int $user_id ): bool {
		// If series passes are allowed, we don't need to do anything.
		if ( tribe( Events_Virtual_Template_Modifications::class )->should_render_show_to_content_for_series_passes() ) {
			return $has_ticket;
		}
		
		$series = tec_series()->where( 'event_post_id', $event->ID )->first_id();
		
		if ( null === $series ) {
			return $has_ticket;
		}
		
		$series_passes = Tickets::get_event_tickets( $series );
		
		if ( empty( $series_passes ) ) {
			return $has_ticket;
		}
		
		$args = [
			'by' => [
				'provider__not_in' => 'rsvp',
				'status'           => 'publish',
				'user'             => $user_id,
			],
		];
		
		$attendees = Tickets::get_event_attendees( $event->ID, $args );
		
		// Filter out series pass attendees.
		$ticketed_attendees = array_filter(
			$attendees,
			function ( $attendee ) {
				return Series_Passes::TICKET_TYPE !== $attendee['ticket_type'];
			}
		);
		
		// If there are no default ticketed attendees, then the content should not be shown.
		if ( empty( $ticketed_attendees ) ) {
			return false;
		}
		
		return $has_ticket;
	}
}
