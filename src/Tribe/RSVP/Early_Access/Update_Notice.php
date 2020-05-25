<?php

namespace Tribe\Tickets\RSVP\Early_Access;

use Tribe__Tickets__Main;
use Tribe__Settings;
use Tribe__Admin__Notices;

class Update_Notice {

	/**
	 * @var Early_Access
	 */
	private $early_access;

	/**
	 * The minimum version of Event Tickets needed to
	 * display the update notice.
	 *`
	 * @since TBD
	 */
	private $minimum_version_to_show = 'TBD';

	/**
	 * Update_Notice constructor.
	 *
	 * @since TBD
	 *
	 * @param Early_Access $early_access
	 */
	public function __construct( Early_Access $early_access ) {
		$this->early_access = $early_access;
	}

	/**
	 * Maybe display the RSVP template update notice.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the update notice was enqueued or not.
	 */
	public function maybe_display_update_notice() {
		// Early bail: Notice should not show up.
		if ( ! $this->should_display_update_notice() ) {
			return false;
		}

		// Bail if user cannot change settings
		if ( ! current_user_can( Tribe__Settings::instance()->requiredCap ) ) {
			return false;
		}

		$message = __( '<h3>Event Tickets</h3><p>With this new version, we\'ve made front-end style updates. If you have customized the RSVP submission form, this update will likely impact your customizations.</p>', 'event-tickets' );

		tribe_notice(
			__FUNCTION__,
			wp_kses_post_deep( $message ),
			[
				'dismiss' => true,
				'type'    => 'warning',
			]
		);

		return true;
	}

	/**
	 * Whether we should display the update notice.
	 *
	 * @since TBD
	 *
	 * @return bool True if should, false otherwise.
	 */
	private function should_display_update_notice() {
		$minimum_version_to_show = apply_filters(
			'tribe_tickets_min_version_to_show_rsvp_early_access_update_notice',
			$this->minimum_version_to_show
		);

		$has_minimum_version = version_compare( Tribe__Tickets__Main::VERSION, $minimum_version_to_show, '>=' );
		$has_early_access    = $this->early_access->is_rsvp_early_access();

		$should_display = $has_early_access || $has_minimum_version;

		return (bool) apply_filters( 'tribe_tickets_should_display_new_rsvp_notice', $should_display );
	}
}