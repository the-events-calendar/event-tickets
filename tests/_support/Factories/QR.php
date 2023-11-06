<?php

namespace Tribe\Tickets\Test\Factories;

use TEC\Tickets\Commerce\Attendee as Commerce_Attendee;

class QR extends \WP_UnitTest_Factory_For_Post {

	/**
	 * Inserts an attendee in the database.
	 *
	 * @param array $args An array of values to override the default arguments.
	 *
	 * @return int The generated attendee post ID
	 */
	function create_object( $args = [] ) {
		$args['post_type']   = $this->get_post_type();
		$args['post_status'] = isset( $args['post_status'] ) ? $args['post_status'] : 'publish';

		$id       = uniqid();
		$defaults = [
			'post_type'  => $this->get_post_type(),
			'post_title' => "Attendee {$id}",
			'post_name'  => "attendee-{$id}",
		];

		unset( $args['meta_input'] );

		$args = array_merge( $defaults, $args );

		$id = parent::create_object( $args );

		return $id;
	}

	/**
	 * Inserts many attendees in the database.
	 *
	 * @param int $count The number of attendees to insert.
	 * @param array $args  An array of arguments to override the defaults
	 * @param array $generation_definitions
	 *
	 * @return array An array of generated attendee post IDs.
	 */
	function create_many( $count, $args = [], $generation_definitions = null ) {
		$ids = [];
		for ( $n = 0; $n < $count; $n ++ ) {
			$attendee_args = $args;
			$ids[]         = $this->create_object( $attendee_args );
		}

		return $ids;
	}

	/**
	 * @return string
	 */
	protected function get_post_type() {
		return Commerce_Attendee::POSTTYPE;
	}
}
