<?php
/**
 * Registers the Deferred Ticket Save feature.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save
 */

namespace TEC\Tickets\Deferred_Save;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;

/**
 * Class Controller.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save
 */
class Controller extends Controller_Contract {
	/**
	 * The name of the constant, and of the environment variable, that switches the feature off.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private const DISABLED = 'TEC_TICKETS_DEFERRED_SAVE_DISABLED';

	/**
	 * Whether the feature is active.
	 *
	 * Ticket changes are deferred to the post save on every ticketable post while the feature is
	 * active. It is active unless the constant, the environment variable or the filter switch it off.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the feature is active.
	 */
	public function is_active(): bool {
		if ( defined( self::DISABLED ) && constant( self::DISABLED ) ) {
			// The constant to disable the feature is defined and it's truthy.
			return false;
		}

		if ( getenv( self::DISABLED ) ) {
			// The environment variable to disable the feature is truthy.
			return false;
		}

		/**
		 * Filters whether the Deferred Ticket Save feature is active.
		 *
		 * Note: this filter will only apply if the disable constant or env var
		 * are not set or are set to falsy values.
		 *
		 * @since TBD
		 *
		 * @param bool $active Whether the feature is active. Defaults to `true`.
		 */
		return (bool) apply_filters( 'tec_tickets_deferred_save_active', true );
	}

	/**
	 * Registers the feature.
	 *
	 * Nothing hooks into a request yet: the payload is read and committed by the
	 * save entry points, which are added by SOFT-4824. The container already
	 * holds this controller as a singleton once it is registered.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		// Nothing to hook yet.
	}

	/**
	 * Unregisters the feature.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		// Nothing to unhook yet.
	}
}
