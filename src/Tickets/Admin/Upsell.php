<?php
/**
 * Handles upsell notices for Event Tickets features.
 *
 * @since 5.3.4
 *
 * @package TEC\Tickets\Admin
 */

namespace TEC\Tickets\Admin;

use TEC\Tickets\Commerce\Settings;
use TEC\Common\Admin\Conditional_Content\Inline_Upsell;

/**
 * Class Upsell
 *
 * Displays inline upsell notices throughout Event Tickets admin pages.
 *
 * @since 5.3.4
 * @since 5.13.1 Updated Wallet Plus notices to be Event Tickets Plus.
 * @since 5.26.7     Migrated to use new Inline_Upsell component.
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
		add_action(
			'tribe_template_after_include:tickets/admin-views/editor/ticket-type-default-header',
			[ $this, 'render_ticket_type_upsell_notice' ],
			20
		);

		add_filter( 'tec_tickets_emails_settings_template_list', [ $this, 'show_on_emails_settings_page' ] );
	}

	/**
	 * Maybe show upsell for Capacity and ARF features.
	 *
	 * @since 5.3.4
	 * @since 5.5.7 Added is_admin() to make sure upsells only display within the admin area.
	 * @since 5.26.7 Updated to use new Inline_Upsell component.
	 */
	public function maybe_show_capacity_arf() {
		// If not within the admin area, then bail.
		if ( ! is_admin() ) {
			return;
		}

		$upsell = new Inline_Upsell();

		$upsell->render(
			[
				'slug'       => 'et-capacity-arf',
				'classes'    => [
					'tec-admin__upsell-tec-tickets-capacity-arf',
				],
				'text'       => sprintf(
					// Translators: %s: Link to "Event Tickets Plus" plugin.
					esc_html__( 'Get individual information collection from each attendee and advanced capacity options with %s', 'event-tickets' ),
					''
				),
				'link'       => [
					'classes' => [
						'tec-admin__upsell-link--underlined',
					],
					'text'    => 'Event Tickets Plus',
					'url'     => 'https://evnt.is/et-in-app-capacity-arf',
				],
				'conditions' => [
					'plugin_not_active' => 'event-tickets-plus/event-tickets-plus.php',
				],
			]
		);
	}

	/**
	 * Show upsell on Attendees page.
	 *
	 * Randomly displays one of two upsells if Event Tickets Plus is not active.
	 * Both methods now handle the ET+ check internally via Inline_Upsell conditions.
	 *
	 * @since 5.7.1
	 * @since 5.13.1 Update notice logic.
	 * @since 5.26.7 Simplified logic - ET+ check now in Inline_Upsell conditions.
	 *
	 * @return void
	 */
	public function show_on_attendees_page() {
		// If not within the admin area, then bail.
		if ( ! is_admin() ) {
			return;
		}

		// 50% chance of showing either upsell (both check for ET+ internally).
		if ( wp_rand( 0, 1 ) ) {
			$this->show_wallet_plus();
			return;
		}

		$this->maybe_show_manual_attendees();
	}

	/**
	 * Maybe show upsell for Manual Attendees.
	 *
	 * @since 5.3.4
	 * @since 5.5.7 Added is_admin() to make sure upsells only display within the admin area.
	 * @since 5.7.1 Move logic into show_on_attendees_page().
	 * @since 5.26.7 Updated to use new Inline_Upsell component.
	 *
	 * @return void
	 */
	public function maybe_show_manual_attendees() {
		$upsell = new Inline_Upsell();

		echo '<div class="welcome-panel-column welcome-panel-extra">';
		$upsell->render(
			[
				'slug'       => 'et-manual-attendees',
				'classes'    => [ 'tec-admin__upsell-tec-tickets-manual-attendees' ],
				'text'       => sprintf(
					// Translators: %s: Link to "Event Tickets Plus" plugin.
					esc_html__( 'Manually add attendees with %s', 'event-tickets' ),
					''
				),
				'link'       => [
					'classes' => [ 'tec-admin__upsell-link--underlined' ],
					'text'    => 'Event Tickets Plus',
					'url'     => 'https://evnt.is/et-in-app-manual-attendees',
				],
				'conditions' => [
					'plugin_not_active' => 'event-tickets-plus/event-tickets-plus.php',
				],
			]
		);

		echo '</div>';
	}

	/**
	 * Maybe show upsell for Wallet Plus.
	 *
	 * @since 5.7.1
	 * @since 5.13.1 Update plugin name and URL.
	 * @since 5.26.7 Updated to use new Inline_Upsell component.
	 *
	 * @return void
	 */
	public function show_wallet_plus() {
		$upsell = new Inline_Upsell();

		echo '<div class="welcome-panel-column welcome-panel-extra">';
		$upsell->render(
			[
				'slug'       => 'et-wallet-plus',
				'classes'    => [ 'tec-admin__upsell-tec-tickets-wallet-plus' ],
				'text'       => sprintf(
					// Translators: %s: Link to "Wallet Plus" plugin.
					esc_html__( 'Get additional ticketing flexibility including Apple Wallet and PDF tickets with %s', 'event-tickets' ),
					''
				),
				'link'       => [
					'classes' => [ 'tec-admin__upsell-link--underlined' ],
					'text'    => 'Event Tickets Plus',
					'url'     => 'https://evnt.is/1bdz',
				],
				'conditions' => [
					'plugin_not_active' => 'event-tickets-plus/event-tickets-plus.php',
				],
			]
		);
		echo '</div>';
	}

	/**
	 * Maybe show upsell for Paystack.
	 *
	 * @since 5.6.5
	 *
	 * @param array $settings The settings array to filter.
	 *
	 * @return array The filtered settings array.
	 */
	public function maybe_show_paystack_promo( $settings ) {
		// Bail if Paystack plugin is installed and activated.
		if ( class_exists( 'paystack\tec\classes\Core', false ) ) {
			return $settings;
		}

		// Bail if we aren't in the correct timezone.
		$timezone           = get_option( 'timezone_string' );
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
		$html     = $template->template( 'paystack-promo', [], false );

		// Create the new setting.
		$new_setting = [
			'afterpay_promo' => [
				'type' => 'html',
				'html' => $html,
			],
		];

		// Find the General Setting header.
		$general_setting_index = array_search( 'tickets-commerce-settings-general-heading', array_keys( $settings ), true );

		// Insert the new setting before the General Setting header.
		$settings_before = array_slice( $settings, 0, $general_setting_index );
		$settings_after  = array_slice( $settings, $general_setting_index );
		return array_merge( $settings_before, $new_setting, $settings_after );
	}

	/**
	 * Filters the default Ticket type description in the context of Events part of a Series.
	 *
	 * @since 5.8.0
	 * @since 5.8.4   Add logic to bail in scenarios when upsell should not show.
	 * @since 5.26.7     Updated to use new Inline_Upsell component. Removed unused parameters.
	 */
	public function render_ticket_type_upsell_notice(): void {
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
	 * @since 5.7.1
	 * @since 5.13.1 Update notice logic, plugin name and URL.
	 * @since 5.26.7     Updated to use new Inline_Upsell component.
	 *
	 * @param array $fields Template list settings fields.
	 *
	 * @return array Filtered template list settings fields.
	 */
	public function show_on_emails_settings_page( $fields ) {
		// If not within the admin area, then bail.
		if ( ! is_admin() ) {
			return $fields;
		}

		$upsell = new Inline_Upsell();

		$fields[] = [
			'type' => 'html',
			'html' => $upsell->render(
				[
					'slug'       => 'et-emails-wallet-plus',
					'classes'    => [ 'tec-admin__upsell-tec-tickets-wallet-plus' ],
					'text'       => sprintf(
						// Translators: %s: Link to "Wallet Plus" plugin.
						esc_html__( 'Get additional ticketing flexibility including Apple Wallet and PDF tickets with %s', 'event-tickets' ),
						''
					),
					'link'       => [
						'classes' => [ 'tec-admin__upsell-link--underlined' ],
						'text'    => 'Event Tickets Plus',
						'url'     => 'https://evnt.is/1bdz',
					],
					'conditions' => [
						'plugin_not_active' => 'event-tickets-plus/event-tickets-plus.php',
					],
				],
				false
			),
		];

		return $fields;
	}
}
