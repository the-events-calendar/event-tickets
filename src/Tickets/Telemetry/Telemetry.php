<?php
/**
 * Class that handles interfacing with TEC\Common\Telemetry.
 *
 * @since 5.6.0.1
 *
 * @package TEC\Tickets\Telemetry
 */

namespace TEC\Tickets\Telemetry;

use TEC\Common\StellarWP\Telemetry\Config;
use TEC\Common\StellarWP\Telemetry\Opt_In\Opt_In_Subscriber;
use TEC\Common\StellarWP\Telemetry\Opt_In\Status;
use TEC\Common\Telemetry\Telemetry as Common_Telemetry;

use Tribe__Main;
use Tribe__Tickets__Main;
use Tribe__Admin__Helpers;
use Tribe\Tickets\Admin\Settings;

/**
 * Class Telemetry
 *
 * @since 5.6.0.1
 * @package TEC\Tickets\Telemetry
 */
class Telemetry {

	/**
	 * The Telemetry plugin slug for Event Tickets.
	 *
	 * @since 5.6.0.1
	 *
	 * @var string
	 */
	protected static $plugin_slug = 'event-tickets';

	/**
	 * The "plugin path" for Event Tickets main file.
	 *
	 * @since 5.6.0.1
	 *
	 * @var string
	 */
	protected static $plugin_path = 'event-tickets.php';

	/**
	 * Filters the modal optin args to be specific to TEC
	 *
	 * @since 5.6.0.1
	 *
	 * @param array<string|mixed> $original_optin_args The original args, provided by Common.
	 *
	 * @return array<string|mixed> The filtered args.
	 */
	public function filter_tec_common_telemetry_optin_args( $original_optin_args ): array {
		// @todo: check for ET admin page.

		$intro_message = sprintf(
			/* Translators: %1$s - the user name. */
			__( 'Hi, %1$s! This is an invitation to help our StellarWP community.', 'event-tickets' ),
			wp_get_current_user()->display_name // escaped after string is assembled, below.
		);

		$intro_message .= ' ' . __( 'If you opt-in, some data about your usage of Event Tickets and future StellarWP Products will be shared with our teams (so they can work their butts off to improve).' , 'event-tickets');
		$intro_message .= ' ' . __( 'We will also share some helpful info on WordPress, and our products from time to time.' , 'event-tickets');
		$intro_message .= ' ' . __( 'And if you skip this, that’s okay! Our products still work just fine.', 'event-tickets' );

		$tec_optin_args = [
			'plugin_logo_alt' => 'Event Tickets Logo',
			'plugin_name'     => 'Event Tickets',
			'heading'         => __( 'We hope you love Event Tickets!', 'event-tickets' ),
			'intro'           => esc_html( $intro_message )
		];

		return array_merge( $original_optin_args, $tec_optin_args );
	}

	/**
	 * Adds the opt in/out control to the general tab debug section.
	 *
	 *
	 * @since 5.6.0.1
	 *
	 * @param array<string|mixed> $fields The fields for the general tab Debugging section.
	 *
	 * @return array<string|mixed> The fields, with the optin control appended.
	 */
	public function filter_tribe_tickets_settings_tab_fields(  $fields ): array {
		$status = Config::get_container()->get( Status::class );
		$opted = $status->get();

		switch( $opted ) {
			case $status::STATUS_ACTIVE :
				$label = esc_html_x( 'Opt out of Telemetry', 'Settings label for opting out of Telemetry.', 'the-events-calendar' );
			default :
				$label = esc_html_x( 'Opt in to Telemetry', 'the-events-calendar' );
		}

		$opt_in_status['ticket-telemetry-optin-heading'] =  [
			'type' => 'html',
			'html' => '<h3 id="event-tickets-telemetry-settings">' . __( 'Telemetry', 'event-tickets' ) . '</h3>',
		];

		$opt_in_status['opt-in-status'] = [
			'type'            => 'checkbox_bool',
			'label'           => $label,
			'tooltip'         => sprintf(
				/* Translators: Description of the Telemetry optin setting.
				%1$s: opening anchor tag for permissions link.
				%2$s: opening anchor tag for terms of service link.
				%3$s: opening anchor tag for privacy policy link.
				%4$s: closing anchor tags.
				*/
				_x( 'Enable this option to share usage data with Event Tickets and StellarWP. %1$sWhat permissions are being granted?%4$s %2$sRead our Terms of Service%4$s %3$sRead our Privacy Policy%4$s', 'Description of optin setting.', 'the-events-calendar' ),
				'<a href=" ' . Common_Telemetry::get_permissions_url() . ' ">',
				'<a href=" ' . Common_Telemetry::get_terms_url() . ' ">',
				'<a href=" ' . Common_Telemetry::get_privacy_url() . ' ">',
				'</a>'
			),
			'default'         => false,
			'validation_type' => 'boolean',
		];

		$fields = Tribe__Main::array_insert_before_key( 'tribe-form-content-end', $fields, $opt_in_status );

		return $fields;
	}

	/**
	 * Ensures the admin control reflects the actual opt-in status.
	 * We save this value in tribe_options but since that could get out of sync,
	 * we always display the status from TEC\Common\StellarWP\Telemetry\Opt_In\Status directly.
	 *
	 * @since 5.6.0.1
	 *
	 * @param mixed  $value  The value of the attribute.
	 * @param string $id  The field object id.
	 *
	 * @return mixed $value
	 */
	public function filter_tribe_field_opt_in_status( $value, $id ) {
		if ( 'opt-in-status' !== $id ) {
			return $value;
		}

		// Trigger this before we try use the value.
		tribe( Common_Telemetry::class )->normalize_optin_status();

		// We don't care what the value stored in tribe_options is - give us Telemetry's Opt_In\Status value.
		$status = Config::get_container()->get( Status::class );
		$value  = $status->get() === $status::STATUS_ACTIVE;

		return $value;
	}

	/**
	 * Adds Event Tickets to the list of plugins
	 * to be opted in/out alongside tribe-common.
	 *
	 * @since 5.6.0.1
	 *
	 * @param array<string,string> $slugs The default array of slugs in the format  [ 'plugin_slug' => 'plugin_path' ]
	 *
	 * @see \TEC\Common\Telemetry\Telemetry::get_tec_telemetry_slugs()
	 *
	 * @return array<string,string> $slugs The same array with Event Tickets added to it.
	 */
	public function filter_tec_telemetry_slugs( $slugs ) {
		$dir = Tribe__Tickets__Main::instance()->plugin_dir;
		$slugs[ static::$plugin_slug ] =  $dir . static::$plugin_path;
		return array_unique( $slugs, SORT_STRING );
	}

	/**
	 * Outputs the hook that renders the Telemetry action on all TEC admin pages.
	 *
	 * @since 5.6.0.1
	 */
	public function inject_modal_link() {
		// Don't double-dip on the action.
		if ( did_action( 'tec_telemetry_modal' ) ) {
			return;
		}

		$current_screen = get_current_screen();
		$admin_helpers = Tribe__Admin__Helpers::instance();
		$admin_pages   = tribe( 'admin.pages' );
		$admin_page    = $admin_pages->get_current_page();

		if ( ! $admin_helpers->is_screen() ) {
			return;
		}

		if (
			$admin_helpers->is_post_type_screen( 'tribe_venue' )
			|| $admin_helpers->is_post_type_screen( 'tribe_organizer' )
		) {
			return false;
		}

		// Are we on a post edit screen?
		if ( $current_screen instanceof \WP_Screen && tribe_get_request_var( 'action' ) === 'edit' ) {
			return false;
		}

		// Are we on a new post screen?
		if ( $current_screen instanceof \WP_Screen && $current_screen->action === 'add' ) {
			return false;
		}

		$pages = [
			Settings::$settings_page_id,
			Settings::$help_page_id,
			Settings::$troubleshooting_page_id
		];

		// Load specifically on Ticket Settings pages only.
		if ( ! in_array( $admin_page, $pages ) ) {
			return false;
		}

		$show = Common_Telemetry::calculate_modal_status();

		if ( ! $show ) {
			return;
		}

		// 'event-tickets'
		$telemetry_slug = substr( basename( EVENT_TICKETS_MAIN_PLUGIN_FILE ), 0, -4 );

		/**
		 * Fires to trigger the modal content on admin pages.
		 *
		 * @since 5.6.0.1
		 */
		do_action( 'tec_telemetry_modal', $telemetry_slug );
	}

	/**
	 * Update our option and the stellar option when the user opts in/out via the TEC admin.
	 *
	 * @since 5.6.0.1
	 *
	 * @param bool $saved_value The option value
	 */
	public function save_opt_in_setting_field( $saved_value ): void {
		$saved_value = tribe_is_truthy( $saved_value );

		// Get the currently saved value.
		$option = tribe_get_option( 'opt-in-status', false );

		// Gotta catch them all.
		tribe( Common_Telemetry::class )->register_tec_telemetry_plugins( $saved_value );

		if ( $saved_value && $option !== $saved_value ) {
			// If changing the value, blow away the expiration datetime so we send updates on next shutdown.
			delete_option( 'stellarwp_telemetry_last_send' );

			$telemetry_data = get_option( 'stellarwp_telemetry' );

			if ( empty( $telemetry_data['token'] ) ) {
				// Force and Opt-in to be done, as we don't have a token yet.
				$opt_in_subscriber = Config::get_container()->get( Opt_In_Subscriber::class );
				$opt_in_subscriber->opt_in( static::$plugin_slug );
			}
		}
	}
}
