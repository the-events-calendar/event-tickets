<?php
/**
 * The Recurrence tier of Recurring Event Tickets.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */

namespace TEC\Tickets\Recurring_Tickets;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\StellarWP\AdminNotices\AdminNotices;
use TEC\Events_Pro\Custom_Tables\V1\Updates\Events;

/**
 * Registers what needs ECP's recurring events: the editor ticket type and Sync.
 *
 * It registers when ECP's custom tables are fully active, ECP fires the hooks that announce a date leaving its event,
 * and the kill switch is not set. The Core tier stays on either way, so tickets already sold keep working.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */
final class Recurrence_Controller extends Controller_Contract {
	/**
	 * The constant or environment variable that turns the Recurrence tier off when truthy.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const DISABLED = 'TEC_TICKETS_RECURRING_TICKETS_DISABLED';

	/**
	 * The ID of the notice shown when ECP is too old for the Recurrence tier.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const OUTDATED_ECP_NOTICE = 'tec_tickets_recurring_tickets_outdated_ecp';

	/**
	 * Registers the tier, or names an ECP too old for it.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function register() {
		if ( ! self::is_disabled() && ! self::ecp_has_hooks() ) {
			$this->show_outdated_ecp_notice();
		}

		parent::register();
	}

	/**
	 * Whether the tier can run: the kill switch is not set and ECP fires the hooks it needs.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the tier can run.
	 */
	public function is_active(): bool {
		return ! self::is_disabled() && self::ecp_has_hooks();
	}

	/**
	 * Unregisters the tier's hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'tec_tickets_ticket_pre_save', $this->container->callback( Ticket_Type::class, 'remember' ) );
		remove_action( 'tribe_tickets_ticket_add', $this->container->callback( Ticket_Type::class, 'assign' ), 5 );
	}

	/**
	 * Registers the tier's hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		$this->container->singleton( Ticket_Type::class );

		add_action( 'tec_tickets_ticket_pre_save', $this->container->callback( Ticket_Type::class, 'remember' ), 10, 2 );
		// Before anything else reads the type of the ticket just saved.
		add_action( 'tribe_tickets_ticket_add', $this->container->callback( Ticket_Type::class, 'assign' ), 5, 2 );
	}

	/**
	 * Whether the kill switch is set, as a constant or an environment variable.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the kill switch is set.
	 */
	private static function is_disabled(): bool {
		return ( defined( self::DISABLED ) && constant( self::DISABLED ) ) || (bool) getenv( self::DISABLED );
	}

	/**
	 * Whether ECP fires the actions for a detached date and for dates moved to another event.
	 *
	 * @since TBD
	 *
	 * @return bool Whether ECP fires both actions.
	 */
	private static function ecp_has_hooks(): bool {
		return defined( Events::class . '::AFTER_DETACH_OCCURRENCE_ACTION' )
			&& defined( Events::class . '::AFTER_TRANSFER_OCCURRENCES_ACTION' );
	}

	/**
	 * Shows, to those who can update plugins, that ECP is too old for recurring event tickets.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	private function show_outdated_ecp_notice(): void {
		AdminNotices::show(
			self::OUTDATED_ECP_NOTICE,
			esc_html__( 'Recurring event tickets need a newer version of Events Calendar Pro. Update Events Calendar Pro to sell tickets for each date of your recurring events.', 'event-tickets' )
		)
			->urgency( 'warning' )
			->dismissible()
			->ifUserCan( 'update_plugins' );
	}
}
