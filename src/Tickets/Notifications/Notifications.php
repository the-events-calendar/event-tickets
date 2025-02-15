<?php
/**
 * Class that handles interfacing with TEC\Common\Notifications.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Notifications
 */

namespace TEC\Tickets\Notifications;

use TEC\Tickets\Integrations\Integration_Abstract;
use TEC\Common\Integrations\Traits\Plugin_Integration;

/**
 * Class Notifications
 *
 * @since   TBD
 * @package TEC\Tickets\Notifications
 */
class Notifications extends Integration_Abstract {
	use Plugin_Integration;

	/**
	 * The slug of this plugin calling the Notifications class.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public static function get_slug(): string {
		return 'event-tickets';
	}

	/**
	 * @inheritDoc
	 */
	public function load_conditionals(): bool {
		return has_action( 'tribe_common_loaded' );
	}

	/**
	 * @inheritDoc
	 */
	protected function load(): void {
		add_action( 'admin_footer', [ $this, 'render_icon' ] );
	}

	/**
	 * Outputs the hook that renders the Notifications icon on all TEC admin pages.
	 *
	 * @since TBD
	 */
	public function render_icon() {
		// Don't double-dip on the action.
		if ( did_action( 'tec_ian_icon' ) ) {
			return;
		}

		/**
		 * Fires to trigger the IAN icon on admin pages.
		 *
		 * @since TBD
		 */
		do_action( 'tec_ian_icon', $this->get_slug() );
	}
}
