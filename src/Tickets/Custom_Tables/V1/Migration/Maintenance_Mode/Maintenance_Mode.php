<?php
/**
 * Handles the maintenance mode template overrides during migration to prevent WRITE operations on event
 * related information.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Custom_Tables\V1\Migration\Maintenance_Mode;
 */

namespace TEC\Tickets\Custom_Tables\V1\Migration\Maintenance_Mode;

/**
 * Class Maintenance_Mode.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Custom_Tables\V1\Migration\Maintenance_Mode;
 */
class Maintenance_Mode {

	/**
	 * Includes the file for the Ticket/RSVP migration in-progress message.
	 *
	 * @since TBD
	 *
	 * @return void The method does not return any value and will echo a mesage to the page.
	 */
	public function include_migration_in_progress_tickets_and_rsvp_message() {

		include_once TEC_ET_CUSTOM_TABLES_V1_ROOT . '/admin-views/migration/maintenance-mode/tickets-and-rsvps.php';
	}

	/**
	 * Includes the file for the ticket/rsvp update form migration in-progress message.
	 *
	 * @since TBD
	 *
	 * @return void The method does not return any value and will echo a message to the page.
	 */
	public function include_migration_in_progress_tickets_and_rsvp_update_message() {
		include_once TEC_ET_CUSTOM_TABLES_V1_ROOT . '/admin-views/migration/maintenance-mode/ticket-updates.php';
	}

	/**
	 * Returns the file path for the Ticket/RSVP migration in-progress message.
	 *
	 * @since TBD
	 *
	 * @return string The absolute file path to the Migration in progress message.
	 */
	public function filter_migration_in_progress_ticket_and_rsvp_message_file_path(): string {
		return TEC_ET_CUSTOM_TABLES_V1_ROOT . '/admin-views/migration/maintenance-mode/tickets-and-rsvps.php';
	}

	/**
	 * Filters the done state for templates that we wish to prevent from displaying when a migration is in progress.
	 *
	 * @since TBD
	 *
	 * @param bool   $done_state    Indicates whether the template has been rendered.
	 * @param string $template_name The template being rendered.
	 *
	 * @return bool|null The filtered value that will indicate whether the template has rendered or not.
	 */
	public function filter_template_done_state_for_tickets_and_rsvps( $done_state, $template_name ): ?bool {
		if ( 'v2/rsvp' === $template_name ) {
			return true;
		}

		if ( 'blocks/rsvp' === $template_name ) {
			return true;
		}

		if ( 'v2/tickets' === $template_name ) {
			return true;
		}

		if ( 'blocks/tickets' === $template_name ) {
			return true;
		}

		return $done_state;
	}
}