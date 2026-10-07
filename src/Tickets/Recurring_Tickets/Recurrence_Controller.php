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
use TEC\Tickets\Recurring_Tickets\Editor\Classic;

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
	 * The action fired before the classic ticket form's dates, where a ticket type renders its header.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private const TYPE_HEADER_ACTION = 'tribe_template_before_include:tickets/admin-views/editor/panel/fields/dates';

	/**
	 * The filter of the classic editor's warning about tickets on recurring events.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private const WARNING_CONTEXT_FILTER = 'tribe_template_context:tickets/admin-views/editor/recurring-warning';

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
	 * Whether the tier can run: the kill switch is not set, ECP fires the hooks it needs, and the rows table exists.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the tier can run.
	 */
	public function is_active(): bool {
		return ! self::is_disabled() && self::ecp_has_hooks() && (bool) $this->container->getVar( Core_Controller::TABLE_READY );
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
		remove_action( 'tec_events_custom_tables_v1_after_save_occurrences', $this->container->callback( Sync::class, 'sync_event' ), 20 );
		remove_action( 'tribe_tickets_ticket_add', $this->container->callback( Sync::class, 'sync_saved_ticket' ) );
		remove_action( 'tec_tickets_commerce_ticket_deleted', $this->container->callback( Sync::class, 'sync_deleted_ticket' ) );
		remove_action( 'tribe_events_tickets_new_ticket_buttons', $this->container->callback( Classic::class, 'render_button' ) );
		remove_action( self::TYPE_HEADER_ACTION, $this->container->callback( Classic::class, 'render_type_header' ) );
		remove_filter( self::WARNING_CONTEXT_FILTER, $this->container->callback( Classic::class, 'filter_warning' ), 20 );
		remove_action( 'admin_init', $this->container->callback( Classic::class, 'hide_legacy_notice' ), 9 );
		remove_filter( 'tec_tickets_editor_list_table_data_' . Template_Guard::TICKET_TYPE, $this->container->callback( Classic::class, 'title_list' ) );
		remove_filter( 'tec_tickets_views_v2_ticket_model_cache_id', $this->container->callback( Views::class, 'cache_id' ) );
		remove_action( 'tec_tickets_recurring_tickets_synced', $this->container->callback( Views::class, 'forget_dates' ) );
		remove_action( 'tec_events_custom_tables_v1_custom_tables_query_results', $this->container->callback( Views::class, 'prime_dates' ) );
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
		add_action( 'tribe_tickets_ticket_add', $this->container->callback( Ticket_Type::class, 'assign' ), 5, 5 );

		$this->container->singleton( Sync::class );

		// After ECP has pruned the dates it no longer generates, at 10.
		add_action( 'tec_events_custom_tables_v1_after_save_occurrences', $this->container->callback( Sync::class, 'sync_event' ), 20 );
		add_action( 'tribe_tickets_ticket_add', $this->container->callback( Sync::class, 'sync_saved_ticket' ), 10, 2 );
		add_action( 'tec_tickets_commerce_ticket_deleted', $this->container->callback( Sync::class, 'sync_deleted_ticket' ), 10, 2 );

		$this->container->singleton( Classic::class );

		add_action( 'tribe_events_tickets_new_ticket_buttons', $this->container->callback( Classic::class, 'render_button' ) );
		add_action( self::TYPE_HEADER_ACTION, $this->container->callback( Classic::class, 'render_type_header' ), 10, 3 );
		// After Flexible Tickets adds its message, at 10.
		add_filter( self::WARNING_CONTEXT_FILTER, $this->container->callback( Classic::class, 'filter_warning' ), 20 );
		// Before the notice, at 10.
		add_action( 'admin_init', $this->container->callback( Classic::class, 'hide_legacy_notice' ), 9 );
		add_filter( 'tec_tickets_editor_list_table_data_' . Template_Guard::TICKET_TYPE, $this->container->callback( Classic::class, 'title_list' ) );

		$this->container->singleton( Views::class );

		add_filter( 'tec_tickets_views_v2_ticket_model_cache_id', $this->container->callback( Views::class, 'cache_id' ), 10, 2 );
		add_action( 'tec_tickets_recurring_tickets_synced', $this->container->callback( Views::class, 'forget_dates' ) );
		add_action( 'tec_events_custom_tables_v1_custom_tables_query_results', $this->container->callback( Views::class, 'prime_dates' ) );

		$this->container->make( Assets::class )->register();
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
