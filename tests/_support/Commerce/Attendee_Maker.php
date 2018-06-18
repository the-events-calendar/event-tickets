<?php

namespace Tribe\Tickets\Test\Commerce;


use Tribe__Utils__Array as Arr;

trait Attendee_Maker {

	/**
	 * Generates a number of attendees for a ticket.
	 *
	 * @param int   $count
	 * @param int   $ticket_id
	 * @param array $overrides See single generation method code for overrides possibilities.
	 *
	 * @return array An array of generated attendees post IDs.
	 */
	protected function create_many_attendees_for_ticket( $count, $ticket_id, array $overrides = [] ) {

		$attendes = [];

		for ( $i = 0; $i < $count; $i ++ ) {
			$attendes[] = $this->create_attendee_for_ticket( $ticket_id, $overrides );
		}

		return $attendes;
	}

	/**
	 * Generates an attendee for a ticket.
	 *
	 * @param int   $ticket_id
	 * @param array $overrides See code for overrides possibilities.
	 *
	 * @return int The generated attendee
	 */
	protected function create_attendee_for_ticket( $ticket_id, array $overrides = array() ) {
		$faker = \Faker\Factory::create();

		/** @var \Tribe__Tickets__Tickets $provider */
		$provider            = tribe_tickets_get_ticket_provider( $ticket_id );
		$provider_reflection = new \ReflectionClass( $provider );

		$product_key     = $provider->attendee_product_key ?: $provider_reflection->getConstant( 'ATTENDEE_PRODUCT_KEY' );
		$optout_key      = $provider->attendee_optout_key ?: $provider_reflection->getConstant( 'ATTENDEE_OPTOUT_KEY' );
		$user_id_key     = $provider->attendee_user_id ?: $provider_reflection->getConstant( 'ATTENDEE_USER_ID' );
		$ticket_sent_key = $provider->attendee_ticket_sent ?: $provider_reflection->getConstant( 'ATTENDEE_TICKET_SENT' );

		$meta = [
			$provider->checkin_key   => (bool) Arr::get( $overrides, 'checkin', false ),
			$provider->checkin_key . '_details' => Arr::get( $overrides, 'checkin_details', false ),
			$provider->security_code => Arr::get( $overrides, 'security_code', md5( time() ) ),
			$product_key             => $ticket_id,
			$optout_key              => Arr::get( $overrides, 'optout', false ),
			$user_id_key             => Arr::get( $overrides, 'user_id', 0 ),
			$ticket_sent_key         => Arr::get( $overrides, 'ticket_sent', true ),
			$provider->full_name     => Arr::get( $overrides, 'full_name', $faker->name ),
			$provider->email         => Arr::get( $overrides, 'email', $faker->email ),
		];

		if ( $provider instanceof \Tribe__Tickets__RSVP ) {
			$meta[ \Tribe__Tickets__RSVP::ATTENDEE_RSVP_KEY ] = $going = Arr::get( $overrides, 'rsvp_status', 'yes' );
		}

		$explicit_keys        = [
			'checkin',
			'checkin_details',
			'security_code',
			'optout',
			'user_id',
			'ticket_sent',
			'full_name',
			'email',
			'rsvp_status',
		];
		$meta_input_overrides = array_diff_key( $overrides, array_combine( $explicit_keys, $explicit_keys ) );

		$postarr = [
			'post_type'   => $provider_reflection->getConstant( 'ATTENDEE_OBJECT' ),
			'post_status' => 'publish',
			'meta_input'  => array_merge( $meta, $meta_input_overrides ),
		];

		$attendee_id = wp_insert_post( $postarr );

		if ( empty( $attendee_id ) || $attendee_id instanceof \WP_Error ) {
			throw new \RuntimeException( 'There was an error while generating the attendee, data: ' . json_encode( $postarr, JSON_PRETTY_PRINT ) );
		}

		return $attendee_id;
	}
}
