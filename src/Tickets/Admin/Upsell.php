<?php

namespace TEC\Tickets\Admin;

use \Tribe\Admin\Upsell_Notice;

/**
 * Class Upsell
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Admin
 */
class Upsell {
	
	/**
	 * Method to register Upsell-related hooks.
	 * 
	 * @since TBD
	 */
	public function hooks() {
		add_action( 'tribe_events_tickets_pre_edit', [ $this, 'maybe_show_capacity_arf' ] );
	}
	
	/**
	 * Maybe show upsell for Capacity and ARF features.
	 * 
	 * @since TBD
	 */
	public function maybe_show_capacity_arf() {
		// If they already have ET+ activated, then bail.
		if ( class_exists( 'Tribe__Tickets_Plus__Main' ) ) {
			return;
		}

		tribe( Upsell_Notice\Main::class )->render( [
			'classes' => [
				'tec-admin__upsell-event_tickets-capacity_arf'
			],
			'text'    => 'Get individual information collection from each attendee and advanced capacity options with',
			'link'    => [
				'classes' => [
					'tec-admin__upsell-link--underlined'
				],
				'text'    => 'Event Tickets Plus',
				'url'     => 'https://evnt.is/et-in-app-capacity-arf',
			],
		] );
	}

}