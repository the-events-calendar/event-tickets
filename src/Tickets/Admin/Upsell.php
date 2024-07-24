<?php

namespace TEC\Tickets\Admin;

use TEC\Tickets\Commerce\Settings;
use \Tribe\Admin\Upsell_Notice;
use Tribe__Template as Template;

/**
 * Class Upsell
 *
 * @since 5.3.4
 *
 * @package TEC\Tickets\Admin
 */
class Upsell {

	/**
	 * Method to register Upsell-related hooks.
	 *
	 * @since 5.3.4
	 * @since TBD Remove emails settings filter.
	 */
	public function hooks() {
		add_action( 'tribe_events_tickets_pre_edit', [ $this, 'maybe_show_capacity_arf' ] );
		add_action( 'tec_tickets_attendees_event_summary_table_extra', [ $this, 'show_on_attendees_page' ] );
		add_filter( 'tribe_tickets_commerce_settings', [ $this, 'maybe_show_paystack_promo' ] );

		// Display ticket type upsell notice.
		add_action( 'tribe_template_after_include:tickets/admin-views/editor/ticket-type-default-header', [
			$this,
			'render_ticket_type_upsell_notice'
		], 20, 3 );
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
	 * Show upsell on Attendees page.
	 *
	 * @since 5.7.1
	 * @since TBD Remove wallet plus notice.
	 *
	 * @return void
	 */
	public function show_on_attendees_page() {
		// If not within the admin area, then bail.
		if ( ! is_admin() ) {
			return;
		}

		$has_tickets_plus = class_exists( '\Tribe__Tickets_Plus__Main', false );

		// If Tickets Plus is installed, then bail.
		if ( $has_tickets_plus ) {
			return;
		}

		$this->maybe_show_manual_attendees();
	}

	/**
	 * Maybe show upsell for Manual Attendees.
	 *
	 * @since 5.7.1   - Move logic into show_on_attendees_page().
	 * @since 5.5.7 - Added is_admin() to make sure upsells only display within the admin area.
	 * @since 5.3.4
	 *
	 * @return void
	 */
	public function maybe_show_manual_attendees() {

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
	 * Maybe show upsell for Wallet Plus.
	 *
	 * @deprecated TBD
	 *
	 * @since 5.7.1
	 *
	 * @return void
	 */
	public function show_wallet_plus() {
		_deprecated_function( __METHOD__, 'TBD', '' );
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

	/**
	 * Filters the default Ticket type description in the context of Events part of a Series.
	 *
	 * @since 5.8.0
	 * @since 5.8.4   Add logic to bail in scenarios when upsell should not show.
	 *
	 * @param string   $file     Complete path to include the PHP File.
	 * @param string[] $name     Template name.
	 * @param Template $template Current instance of the Tribe__Template.
	 */
	public function render_ticket_type_upsell_notice( string $file, array $name, Template $template ): void {
		// Check if post type is an event.
		if ( ! function_exists( 'tribe_is_event' ) || ! tribe_is_event() ) {
			return;
		}

		// If not within the admin area, then bail.
		if ( ! is_admin() ) {
			return;
		}

		// If Events Calendar Pro is activated, then bail.
		if ( did_action( 'tribe_events_pro_init_apm_filters' ) ) {
			return;
		}

		$admin_views = tribe( 'tickets.admin.views' );
		$admin_views->template( 'flexible-tickets/admin/tickets/editor/upsell-notice' );
	}

	/**
	 * Show upsell on Emails Settings page.
	 *
	 * @deprecated TBD
	 *
	 * @since 5.7.1
	 *
	 * @param array $fields Template list settings fields.
	 *
	 * @return array Filtered template list settings fields.
	 */
	public function show_on_emails_settings_page( $fields ) {
		_deprecated_function( __METHOD__, 'TBD', '' );
	}
}
