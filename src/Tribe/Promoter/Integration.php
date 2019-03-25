<?php

/**
 * Class Tribe__Tickets__Promoter__Integration
 *
 * Class used to handle Event Tickets integration and customizations needed for Promoter.
 *
 * @since TBD
 */
class Tribe__Tickets__Promoter__Integration {

	/**
	 * Hooks on which this observer notifies promoter.
	 *
	 * @since TBD
	 */
	public function hook() {
		/** @var Tribe__Promoter__PUE $pue */
		$pue = tribe( 'promoter.pue' );

		/** @var Tribe__Promoter__Connector $connector */
		$connector = tribe( 'promoter.connector' );

		if ( ! $pue->has_license_key() || ! $connector->is_user_authorized() ) {
			return;
		}

		// Attendee data is needed by Promoter requests.
		add_filter( 'tribe_tickets_rest_api_always_show_attendee_data', '__return_true' );
	}

}