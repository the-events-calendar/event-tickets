<?php

namespace TEC\Tickets\Admin;

use TEC\Tickets\Commerce\Settings;
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
		add_filter( 'tribe_tickets_commerce_settings', [ $this, 'maybe_show_paystack_promo' ] );
	}

	/**
	 * Maybe show upsell for Capacity and ARF features.
	 *
	 * @since 5.5.7 - Added is_admin() to make sure upsells only display within the admin area.
	 * @since 5.3.4
	 */
	public function maybe_show_capacity_arf() {
		// If they already have ET+ activated or are not within the admin area, then bail.
		if ( class_exists( 'Tribe__Tickets_Plus__Main' ) || ! is_admin() ) {
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
	 * @since 5.5.7 - Added is_admin() to make sure upsells only display within the admin area.
	 * @since 5.3.4
	 */
	public function maybe_show_manual_attendees() {
		// If they already have ET+ activated or are not within the admin area, then bail.
		if ( class_exists( 'Tribe__Tickets_Plus__Main' ) || ! is_admin() ) {
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

	/**
	 * Maybe show upsell for Paystack.
	 *
	 * @since 5.6.5
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public function maybe_show_paystack_promo( $settings ) {

		// Bail if Paystack plugin is installed and activated.
		if ( class_exists( 'paystack\tec\classes\Core', false ) ) {
			return $settings;
		}

		// Bail if we aren't in the correct timezone.
		$timezone = get_option( 'timezone_string' );
		$paystack_timezones = [
			'Africa/Lagos',
			'Africa/Accra',
			'Africa/Johannesburg',
		];
		if ( ! in_array( $timezone, $paystack_timezones, true ) ) {
			return $settings;
		}

		/** @var \Tribe__Template $template  */
		$template = tribe( Settings::class )->get_template();
		$html = $template->template( 'paystack-promo', [], false );

		// Create the new setting.
		$new_setting = [
			'afterpay_promo' => [
				'type' => 'html',
				'html' => $html,
			]
		];

		// Find the General Setting header.
		$general_setting_index = array_search( 'tickets-commerce-settings-general-heading', array_keys( $settings ), true );

		// Insert the new setting before the General Setting header.
		$settings_before = array_slice( $settings, 0, $general_setting_index );
		$settings_after  = array_slice( $settings, $general_setting_index );
		return array_merge( $settings_before, $new_setting, $settings_after);
    }
}