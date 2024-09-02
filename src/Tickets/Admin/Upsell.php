<?php

namespace TEC\Tickets\Admin;

use TEC\Tickets\Commerce\Settings;
use \Tribe\Admin\Upsell_Notice;
use Tribe__Template as Template;

/**
 * Class Upsell
 *
 * @since 5.13.1 Updated Wallet Plus notices to be Event Tickets Plus.
 * @since 5.3.4
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
		add_action( 'tec_tickets_attendees_event_summary_table_extra', [ $this, 'show_on_attendees_page' ] );
		add_filter( 'tribe_tickets_commerce_settings', [ $this, 'maybe_show_paystack_promo' ] );

		// Display ticket type upsell notice.
		add_action( 'tribe_template_after_include:tickets/admin-views/editor/ticket-type-default-header', [
			$this,
			'render_ticket_type_upsell_notice'
		], 20, 3 );

		add_filter( 'tec_tickets_emails_settings_template_list', [ $this, 'show_on_emails_settings_page' ] );
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
	 * @since 5.13.1 Update notice logic.
	 * @since 5.7.1
	 *
	 * @return void
	 */
	public function show_on_attendees_page() {
		// If not within the admin area, then bail.
		if ( ! is_admin() ) {
			return;
		}

		$has_tickets_plus = did_action( 'tec_container_registered_provider_Tribe__Tickets_Plus__Service_Provider' );

		// If Tickets Plus is installed, then bail.
		if ( $has_tickets_plus ) {
			return;
		}

		// 50% chance of showing either upsell.
		if ( wp_rand( 0, 1 ) ) {
			$this->show_wallet_plus();
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
	 * @since 5.13.1 Update plugin name and URL.
	 * @since 5.7.1
	 *
	 * @return void
	 */
	public function show_wallet_plus() {

		echo '<div class="welcome-panel-column welcome-panel-extra">';
		tribe( Upsell_Notice\Main::class )->render( [
			'classes' => [
				'tec-admin__upsell-tec-tickets-wallet-plus'
			],
			'text'    => sprintf(
				// Translators: %s: Link to "Wallet Plus" plugin.
				esc_html__( 'Get additional ticketing flexibility including Apple Wallet and PDF tickets with %s' , 'event-tickets' ),
				''
			),
			'link'    => [
				'classes' => [
					'tec-admin__upsell-link--underlined'
				],
				'text'    => 'Event Tickets Plus',
				'url'     => 'https://evnt.is/1bdz',
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
	 * @since 5.13.1 Update notice logic, plugin name and URL.
	 * @since 5.7.1
	 *
	 * @param array $fields Template list settings fields.
	 *
	 * @return array Filtered template list settings fields.
	 */
	public function show_on_emails_settings_page( $fields ) {
		// If they already have ET+ activated or are not within the admin area, then bail.
		if (
			did_action( 'tec_container_registered_provider_Tribe__Tickets_Plus__Service_Provider' )
			|| ! is_admin()
		) {
			return $fields;
		}

		$fields[] = [
			'type' => 'html',
			'html'  => tribe( Upsell_Notice\Main::class )->render( [
				'classes' => [
					'tec-admin__upsell-tec-tickets-wallet-plus'
				],
				'text'    => sprintf(
					// Translators: %s: Link to "Wallet Plus" plugin.
					esc_html__( 'Get additional ticketing flexibility including Apple Wallet and PDF tickets with %s' , 'event-tickets' ),
					''
				),
				'link'    => [
					'classes' => [
						'tec-admin__upsell-link--underlined'
					],
					'text'    => 'Event Tickets Plus',
					'url'     => 'https://evnt.is/1bdz',
				],
			], false ),
		];

		return $fields;
	}
}
