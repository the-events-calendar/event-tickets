<?php
/**
 * Tests for the Migrations Controller.
 *
 * @since TBD
 */

namespace TEC\Tickets\Tests\Integration\Migrations;

use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Migrations\Controller;
use TEC\Common\StellarWP\Migrations\Abstracts\Migration_Abstract;
use TEC\Common\StellarWP\Migrations\Contracts\Migration;
use TEC\Common\StellarWP\Migrations\Enums\Operation;

/**
 * Class Controller_Test
 *
 * @since TBD
 */
class Controller_Test extends Controller_Test_Case {
	protected string $controller_class = Controller::class;

	/**
	 * @test
	 */
	public function should_add_filtered_migrations_filter_on_before_content_action(): void {
		$controller = $this->make_controller();
		$controller->register();

		$this->assertFalse(
			has_filter( 'stellarwp_migrations_tec_filtered_migrations', [ $controller, 'keep_only_event_tickets_migrations' ] ),
			'The filter should not be added before the tab renders.'
		);

		do_action( 'tribe_settings_before_content_tab_migrations' );

		$this->assertNotFalse(
			has_filter( 'stellarwp_migrations_tec_filtered_migrations', [ $controller, 'keep_only_event_tickets_migrations' ] ),
			'The filter should be added after the before_content action fires.'
		);
	}

	/**
	 * @test
	 */
	public function should_keep_migrations_tagged_with_event_tickets(): void {
		$controller = $this->make_controller();

		$tagged_migration = $this->make_migration( 'tagged', [ 'event-tickets', 'rsvp' ] );
		$untagged_migration = $this->make_migration( 'untagged', [ 'the-events-calendar' ] );

		$result = $controller->keep_only_event_tickets_migrations( [ $tagged_migration, $untagged_migration ] );

		$this->assertCount( 1, $result );
		$this->assertSame( 'tagged', $result[0]->get_id() );
	}

	/**
	 * @test
	 */
	public function should_remove_all_migrations_when_none_tagged_with_event_tickets(): void {
		$controller = $this->make_controller();

		$migration_a = $this->make_migration( 'a', [ 'the-events-calendar' ] );
		$migration_b = $this->make_migration( 'b', [ 'other-plugin' ] );

		$result = $controller->keep_only_event_tickets_migrations( [ $migration_a, $migration_b ] );

		$this->assertCount( 0, $result );
	}

	/**
	 * @test
	 */
	public function should_keep_all_migrations_when_all_tagged_with_event_tickets(): void {
		$controller = $this->make_controller();

		$migration_a = $this->make_migration( 'a', [ 'event-tickets', 'rsvp' ] );
		$migration_b = $this->make_migration( 'b', [ 'event-tickets', 'tickets-commerce' ] );

		$result = $controller->keep_only_event_tickets_migrations( [ $migration_a, $migration_b ] );

		$this->assertCount( 2, $result );
	}

	/**
	 * @test
	 */
	public function should_return_sequential_keys_after_filtering(): void {
		$controller = $this->make_controller();

		$migration_a = $this->make_migration( 'a', [ 'the-events-calendar' ] );
		$migration_b = $this->make_migration( 'b', [ 'event-tickets' ] );
		$migration_c = $this->make_migration( 'c', [ 'other-plugin' ] );

		$result = $controller->keep_only_event_tickets_migrations( [ $migration_a, $migration_b, $migration_c ] );

		$this->assertCount( 1, $result );
		$this->assertArrayHasKey( 0, $result );
		$this->assertSame( 'b', $result[0]->get_id() );
	}

	/**
	 * @test
	 */
	public function should_handle_empty_migrations_list(): void {
		$controller = $this->make_controller();

		$result = $controller->keep_only_event_tickets_migrations( [] );

		$this->assertCount( 0, $result );
	}

	/**
	 * Creates a stub migration with the given ID and tags.
	 *
	 * @param string   $id   The migration ID.
	 * @param string[] $tags The migration tags.
	 *
	 * @return Migration The stub migration.
	 */
	private function make_migration( string $id, array $tags ): Migration {
		return new class( $id, $tags ) extends Migration_Abstract {
			/**
			 * @var string[]
			 */
			private array $tags;

			public function __construct( string $id, array $tags ) {
				parent::__construct( $id );
				$this->tags = $tags;
			}

			public function get_label(): string {
				return 'Test Migration';
			}

			public function get_description(): string {
				return 'A test migration.';
			}

			public function get_total_items( ?Operation $operation = null ): int {
				return 0;
			}

			public function get_default_batch_size(): int {
				return 10;
			}

			public function get_tags(): array {
				return $this->tags;
			}

			public function is_applicable(): bool {
				return true;
			}

			public function is_up_done(): bool {
				return false;
			}

			public function is_down_done(): bool {
				return false;
			}

			public function up( int $batch, int $batch_size ): void {
			}

			public function down( int $batch, int $batch_size ): void {
			}
		};
	}
}
