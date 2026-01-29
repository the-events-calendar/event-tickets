<?php
/**
 * RSVP to Tickets Commerce Migration.
 *
 * @since TBD
 */

namespace TEC\Tickets\Migrations;

use TEC\Common\StellarWP\Migrations\Abstracts\Migration_Abstract;
use TEC\Common\StellarWP\Migrations\Enums\Operation;

/**
 * RSVP to Tickets Commerce Migration.
 *
 * @since TBD
 */
class RSVP_To_Tickets_Commerce extends Migration_Abstract {
	/**
	 * Get the migration label.
	 *
	 * @since TBD
	 *
	 * @return string The migration label.
	 */
	public function get_label(): string {
		return 'RSVP to Tickets Commerce';
	}

	/**
	 * Get the migration description.
	 *
	 * @since TBD
	 *
	 * @return string The migration description.
	 */
	public function get_description(): string {
		return 'Migrate your RSVPs to Tickets Commerce.';
	}

	/**
	 * Get the total number of items to process.
	 *
	 * @since TBD
	 *
	 * @return int The total number of items to process.
	 */
	public function get_total_items( ?Operation $operation = null ): int {
		if ( null === $operation ) {
			$operation = Operation::UP();
		}

		if ( $operation->equals( Operation::DOWN() ) ) {
			return 0;
		}

		return 1000;
	}

	/**
	 * Get the number of retries per batch.
	 *
	 * @since TBD
	 *
	 * @return int The number of retries per batch.
	 */
	public function get_default_batch_size(): int {
		return 100;
	}

	/**
	 * Whether the migration is applicable.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the migration is applicable.
	 */
	public function is_applicable(): bool {
		return true;
	}

	/**
	 * Whether the migration can run.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the migration can run.
	 */
	public function can_run(): bool {
		return true;
	}

	/**
	 * Whether the migration has been completed.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the migration has been completed.
	 */
	public function is_up_done(): bool {
		return false;
	}

	/**
	 * Whether the migration has been rolled back.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the migration has been rolled back.
	 */
	public function is_down_done(): bool {
		return false;
	}

	/**
	 * Run the migration.
	 *
	 * @since TBD
	 *
	 * @param int $batch The batch number.
	 * @param int $batch_size The batch size.
	 *
	 * @return void
	 */
	public function up( int $batch, int $batch_size ): void {
		// TODO: Implement up() method.
	}

	/**
	 * Roll back the migration.
	 *
	 * @since TBD
	 *
	 * @param int $batch The batch number.
	 * @param int $batch_size The batch size.
	 *
	 * @return void
	 */
	public function down( int $batch, int $batch_size ): void {
		// TODO: Implement down() method.
	}

	/**
	 * Get the migration tags.
	 *
	 * @since TBD
	 *
	 * @return array<string> The migration tags.
	 */
	public function get_tags(): array {
		return [ 'event-tickets', 'rsvp', 'tickets-commerce' ];
	}
}
