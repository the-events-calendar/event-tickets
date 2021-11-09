<?php

namespace TEC\Tickets\Commerce\Flag_Actions;

use TEC\Tickets\Commerce\Attendee;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Settings;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\Commerce\Status\Status_Abstract;
use TEC\Tickets\Commerce\Status\Status_Handler;
use TEC\Tickets\Commerce\Status\Status_Interface;
use TEC\Tickets\Commerce\Ticket;
use Tribe__Utils__Array as Arr;

/**
 * Class Archive_Attendees normally triggers when handling refunds and stuff like that.
 *
 * @since   5.1.10
 *
 * @package TEC\Tickets\Commerce\Flag_Actions
 */
class Archive_Attendees extends Flag_Action_Abstract {
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
		Order::POSTTYPE
	];

	/**
	 * {@inheritDoc}
	 */
	public function handle( Status_Interface $new_status, $old_status, \WP_Post $post ) {
		
		if ( empty( $post->items ) || $new_status->wp_arguments['label'] !== $post->status_name ) {
			return;
		}

		foreach ( $post->items as $ticket_id => $item ) {
			$ticket = \Tribe__Tickets__Tickets::load_ticket_object( $item['ticket_id'] );
			if ( null === $ticket ) {
				continue;
			}

			$attendees = tribe_tickets_get_attendees( $ticket->ID );
			$quantity  = count( $attendees );

			// Skip archiving for zero-ed items.
			if ( 0 >= $quantity ) {
				continue;
			}

			foreach ( $attendees as $attendee ) {
				if ( empty( $attendee['ID'] ) || empty( $attendee['order_id'] ) || $post->ID !== $attendee['order_id'] ) {
					continue;
				}

				tribe( Attendee::class )->archive( $attendee['ID'] );
			}
		}
	}
}