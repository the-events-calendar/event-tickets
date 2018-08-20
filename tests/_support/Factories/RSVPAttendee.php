<?php

namespace Tribe\Tickets\Test\Factories;

use Tribe__Tickets__RSVP as RSVP;

class RSVPAttendee extends \WP_UnitTest_Factory_For_Post {

	/**
	 * Inserts an ticket in the database.
	 *
	 * @param array $args      An array of values to override the default arguments.
	 *
	 * @return int The generated ticket post ID
	 */
	function create_object( $args = array() ) {
		$args['post_type'] = $this->get_post_type();
		$args['post_status'] = isset( $args['post_status'] ) ? $args['post_status'] : 'publish';

		$id = uniqid();
		$defaults = [
			'post_type'  => $this->get_post_type(),
			'post_title' => "Ticket {$id}",
			'post_name'  => "ticket-{$id}",
		];

		unset( $args['meta_input'] );

		$args = array_merge( $defaults, $args );

		$id = parent::create_object( $args );

		return $id;
	}

	/**
	 * Inserts many tickets in the database.
	 *
	 * @param      int $count The number of tickets to insert.
	 * @param array    $args  An array of arguments to override the defaults
	 * @param array    $generation_definitions
	 *
	 * @return array An array of generated ticket post IDs.
	 */
	function create_many( $count, $args = array(), $generation_definitions = null ) {
		$ids = [];
		for ( $n = 0; $n < $count; $n ++ ) {
			$ticket_args = $args;
			$ids[] = $this->create_object( $ticket_args );
		}

		return $ids;
	}

	/**
	 * @return string
	 */
	protected function get_post_type() {
		return RSVP::ATTENDEE_OBJECT;
	}
}