<?php
/**
 * Handles the maintenance mode set during migration to prevent WRITE operations on Events
 * and related information.
 *
 * @since 5.5.0
 *
 * @package TEC\Tickets\Custom_Tables\V1\Migration\Maintenance_Mode;
 */

namespace TEC\Tickets\Commerce\Custom_Tables\V1\Migration\Maintenance_Mode;

use TEC\Common\Contracts\Service_Provider;

/**
 * Class Provider.
 *
 * @since 5.5.0
 *
 * @package TEC\Tickets\Custom_Tables\V1\Migration\Maintenance_Mode;
 */
class Provider extends Service_Provider {
	/**
	 * @var bool Flag whether we have registered already yet or not.
	 */
	private $has_registered = false;

	/**
	 * Activates the migration mode, disabling a number of UI elements
	 * across ET.
	 *
	 * @since 5.5.0
	 *
	 * @return bool Whether the Event-wide maintenance mode was activated or not.
	 */
	public function register() {
		if ( $this->has_registered ) {

			return false;
		}
		$this->has_registered = true;
		add_action( 'tec_events_custom_tables_v1_migration_maintenance_mode', [ $this, 'add_filters' ] );

		return true;
	}

	/**
	 * Hooks into filters and actions disabling a number of UI across plugins to make sure
	 * no Event-related data will be modified during the migration.
	 *
	 * @since 5.5.0
	 *
	 * @return void
	 */
	public function add_filters() {
		// Display a message for Tickets & RSVPs on the frontend.
		add_action( 'tribe_tickets_before_front_end_ticket_form', [
			$this,
			'include_migration_in_progress_tickets_and_rsvp_message'
		] );
		// Display a message for the Ticket & RSVP edit for on the frontend.
		add_action( 'tribe_tickets_orders_before_submit', [
			$this,
			'include_migration_in_progress_tickets_and_rsvp_update_message'
		] );

		// This is ugly but there's no easy way to hijack the submission of ticket/rsvp updates on the frontend.
		if ( isset( $_POST['process-tickets'] ) ) {
			unset( $_POST['process-tickets'] );
		}

		// Disable RSVP on the frontend.
		add_filter( 'tribe_events_tickets_template_tickets/rsvp', [
			$this,
			'filter_migration_in_progress_ticket_and_rsvp_message_file_path'
		] );
		// Prevent the loading of the templates if a migration is in progress.
		add_filter( 'tribe_template_done', [ $this, 'filter_template_done_state_for_tickets_and_rsvps' ], 10, 2 );
	}

	/**
	 * Includes the file for the Ticket/RSVP migration in-progress message.
	 *
	 * @since 5.5.0
	 *
	 * @return void The method does not return any value and will echo a mesage to the page.
	 */
	public function include_migration_in_progress_tickets_and_rsvp_message() {
		return $this->container->make( Maintenance_Mode::class )->include_migration_in_progress_tickets_and_rsvp_message();
	}

	/**
	 * Includes the file for the ticket/rsvp update form migration in-progress message.
	 *
	 * @since 5.5.0
	 *
	 * @return void The method does not return any value and will echo a message to the page.
	 */
	public function include_migration_in_progress_tickets_and_rsvp_update_message() {
		return $this->container->make( Maintenance_Mode::class )->include_migration_in_progress_tickets_and_rsvp_update_message();
	}

	/**
	 * Returns the file path for the Ticket/RSVP migration in-progress message.
	 *
	 * @since 5.5.0
	 *
	 * @return string The absolute file path to the Migration in progress message.
	 */
	public function filter_migration_in_progress_ticket_and_rsvp_message_file_path(): string {
		return $this->container->make( Maintenance_Mode::class )->filter_migration_in_progress_ticket_and_rsvp_message_file_path();
	}

	/**
	 * Filters the done state for templates that we wish to prevent from displaying when a migration is in progress.
	 *
	 * @since 5.5.0
	 *
	 * @param mixed $done_state    Indicates whether the template has been rendered.
	 * @param mixed $template_name The template being rendered.
	 *
	 * @return bool The filtered value that will indicate whether the template has rendered or not.
	 */
	public function filter_template_done_state_for_tickets_and_rsvps( $done_state, $template_name ): ?bool {
		return $this->container->make( Maintenance_Mode::class )->filter_template_done_state_for_tickets_and_rsvps( $done_state, $template_name );
	}
}
