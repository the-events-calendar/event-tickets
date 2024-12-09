<?php

namespace TEC\Tickets\Commerce\Flag_Actions;

use TEC\Tickets\Commerce\Attendee;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Status_Interface;
use TEC\Tickets\Commerce\Traits\Is_Ticket;

/**
 * Class Archive_Attendees normally triggers when handling refunds and stuff like that.
 *
 * @since   5.1.10
 *
 * @package TEC\Tickets\Commerce\Flag_Actions
 */
class Archive_Attendees extends Flag_Action_Abstract {

	use Is_Ticket;

	/**
	 * {@inheritDoc}
	 */
	protected $flags = [
		'archive_attendees',
	];

	/**
	 * {@inheritDoc}
	 */
	protected $post_types = [
		Order::POSTTYPE,
	];

	/**
	 * {@inheritDoc}
	 */
	public function handle( Status_Interface $new_status, $old_status, \WP_Post $post ) {
		if ( empty( $post->items ) || $new_status->get_slug() !== $post->status_obj->get_slug() ) {
			return;
		}

		foreach ( $post->items as $item ) {
			if ( ! $this->is_ticket( $item ) ) {
				continue;
			}

			$ticket = \Tribe__Tickets__Tickets::load_ticket_object( $item['ticket_id'] );
			if ( null === $ticket ) {
				continue;
			}

			$attendees_repo = tec_tc_attendees();
			$attendees_repo->by( 'ticket_id', $ticket->ID );
			$attendees_repo->by( 'parent', $post->ID );
			$attendees_repo->by( 'status', 'any' );

			$attendees = $attendees_repo->all();

			// Skip archiving for zero-ed items.
			if ( ! $attendees_repo->found() ) {
				continue;
			}

			foreach ( $attendees as $attendee ) {
				/**
				 * Allows filtering whether an attendee should archived, or hard-deleted from the database.
				 *
				 * To permanently delete an attendee, this filter must return a boolean false. Any other value will fallback to archiving.
				 *
				 * @since 5.2.1
				 *
				 * @param \WP_Post                       $attendee the attendee data
				 * @param \Tribe__Tickets__Ticket_Object $ticket   the ticket
				 * @param \WP_Post                       $post     the order
				 */
				$archive_attendee = apply_filters( 'tec_tickets_commerce_archive_attendee_delete_permanently', true, $attendee, $ticket, $post );

				if ( false === $archive_attendee ) {
					tribe( Attendee::class )->delete( $attendee->ID );

					return;
				}

				tribe( Attendee::class )->archive( $attendee->ID );
			}
		}
	}
}
