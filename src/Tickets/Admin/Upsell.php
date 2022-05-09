<?php

namespace TEC\Tickets\Admin;

use \Tribe\Admin\Upsell_Notice;

/**
 * Class Upsell
 *
 * @since   5.3.4
 *
 * @package TEC\Tickets\Admin
 */
class Upsell {

	/**
	 * Method to register Upsell-related hooks.
	 *
	 * @since 5.3.4
	 */
	public function hooks() {
		add_action( 'tribe_events_tickets_pre_edit', [ $this, 'maybe_show_capacity_arf' ] );
		add_action( 'tec_tickets_attendees_event_summary_table_extra', [ $this, 'maybe_show_manual_attendees' ] );
	}

	/**
	 * Maybe show upsell for Capacity and ARF features.
	 *
	 * @since 5.3.4
	 */
	public function maybe_show_capacity_arf() {
		// If they already have ET+ activated, then bail.
		if ( class_exists( 'Tribe__Tickets_Plus__Main' ) ) {
			return;
		}

		tribe( Upsell_Notice\Main::class )->render( [
			'classes' => [
				'tec-admin__upsell-tec-tickets-capacity-arf'
			],
			'text'    => sprintf(
				// Translators: %s: Link to "Event Tickets Plus" plugin.
				esc_html__( 'Get individual information collection from each attendee and advanced capacity options with %s' , 'event-tickets' ),
				''
			 ),
			'link'    => [
				'classes' => [
					'tec-admin__upsell-link--underlined'
				],
				'text'    => 'Event Tickets Plus',
				'url'     => 'https://evnt.is/et-in-app-capacity-arf',
			],
		] );
	}

	/**
	 * Maybe show upsell for Manual Attendees.
	 *
	 * @since 5.3.4
	 */
	public function maybe_show_manual_attendees() {
		// If they already have ET+ activated, then bail.
		if ( class_exists( 'Tribe__Tickets_Plus__Main' ) ) {
			return;
		}

		echo '<div class="welcome-panel-column welcome-panel-extra">';
		tribe( Upsell_Notice\Main::class )->render( [
			'classes' => [
				'tec-admin__upsell-tec-tickets-manual-attendees'
			],
			'text'    => sprintf(
				// Translators: %s: Link to "Event Tickets Plus" plugin.
				esc_html__( 'Manually add attendees with %s' , 'event-tickets' ),
				''
			),
			'link'    => [
				'classes' => [
					'tec-admin__upsell-link--underlined'
				],
				'text'    => 'Event Tickets Plus',
				'url'     => 'https://evnt.is/et-in-app-manual-attendees',
			],
		] );
		echo '</div>';
	}

}