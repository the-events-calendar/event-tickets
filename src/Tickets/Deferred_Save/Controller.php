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
final class Controller extends Controller_Contract {
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
		if ( defined( self::DISABLED ) && tribe_is_truthy( constant( self::DISABLED ) ) ) {
			return false;
		}

		if ( tribe_is_truthy( getenv( self::DISABLED ) ) ) {
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
		return tribe_is_truthy( apply_filters( 'tec_tickets_deferred_save_active', true ) );
	}

	/**
	 * Registers the feature.
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
