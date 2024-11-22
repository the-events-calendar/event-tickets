<?php

namespace TEC\Tickets\Commerce\Order_Modifiers\Admin;

use TEC\Common\Tests\Provider\Controller_Test_Case;
use Tribe\Tests\Traits\With_Uopz;
use TEC\Common\StellarWP\Assets\Assets;
use Closure;
use Tribe__Events__Main as TEC;
use Generator;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use Tribe\Tickets\Test\Commerce\OrderModifiers\Fee_Creator;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

class Editor_Test extends Controller_Test_Case {
	use With_Uopz;
	use Fee_Creator;
	use Ticket_Maker;
	use SnapshotAssertions;

	protected string $controller_class = Editor::class;

	public function get_store_data_provider(): Generator {
		yield 'new post' => [
			function (): array {
				global $pagenow;
				$pagenow = 'post-new.php';

				return [[], []];
			}
		];

		yield 'existing post with tickets' => [
			function (): array {
				$id = self::factory()->post->create();
				global $pagenow, $post;
				$pagenow = 'edit.php';
				$post    = get_post( $id );
				$ticket_1 = $this->create_tc_ticket( $id, 10.10 );
				$ticket_2 = $this->create_tc_ticket( $id, 20.30 );
				$ticket_3 = $this->create_tc_ticket( $id, 30.40 );
				$ticket_4 = $this->create_tc_ticket( $id, 40.50 );
				$ticket_5 = $this->create_tc_ticket( $id, 50.60 );

				return [[$ticket_1, $ticket_2, $ticket_3, $ticket_4, $ticket_5], []];
			}
		];
		yield 'existing post with tickets with fees' => [
			function (): array {
				$id = self::factory()->post->create();
				global $pagenow, $post;
				$pagenow = 'edit.php';
				$post    = get_post( $id );
				$ticket_1 = $this->create_tc_ticket( $id, 10.10 );
				$ticket_2 = $this->create_tc_ticket( $id, 20.30 );
				$ticket_3 = $this->create_tc_ticket( $id, 30.40 );
				$ticket_4 = $this->create_tc_ticket( $id, 40.50 );
				$ticket_5 = $this->create_tc_ticket( $id, 50.60 );

				$fee_id_2 = $this->create_fee_for_ticket( $ticket_2, [ 'raw_amount' => 3.25 ] );
				$fee_id_4 = $this->create_fee_for_ticket( $ticket_4, [ 'raw_amount' => 5.50 ] );
				$fee_id_5 = $this->create_fee_for_ticket( $ticket_5, [ 'raw_amount' => 7.77 ] );

				return [[$ticket_1, $ticket_2, $ticket_3, $ticket_4, $ticket_5], [$fee_id_2, $fee_id_4, $fee_id_5]];
			}
		];

		yield 'new event' => [
			function (): array {
				$post_type = TEC::POSTTYPE;
				global $pagenow;
				$pagenow               = 'post-new.php';
				$_REQUEST['post_type'] = $post_type;

				return [[], []];
			}
		];

		yield 'existing event with tickets' => [
			function (): array {
				$id = tribe_events()->set_args( [
					'title'      => 'Test Event',
					'start_date' => '+1 week',
					'duration'   => 3 * HOUR_IN_SECONDS,
				] )->create()->ID;
				global $pagenow, $post;
				$pagenow = 'edit.php';
				$post    = get_post( $id );

				$ticket_1 = $this->create_tc_ticket( $id, 10.10 );
				$ticket_2 = $this->create_tc_ticket( $id, 20.30 );
				$ticket_3 = $this->create_tc_ticket( $id, 30.40 );
				$ticket_4 = $this->create_tc_ticket( $id, 40.50 );
				$ticket_5 = $this->create_tc_ticket( $id, 50.60 );

				return [[$ticket_1, $ticket_2, $ticket_3, $ticket_4, $ticket_5], []];
			}
		];

		yield 'existing event with tickets with fees' => [
			function (): array {
				$id = tribe_events()->set_args( [
					'title'      => 'Test Event',
					'start_date' => '+1 week',
					'duration'   => 3 * HOUR_IN_SECONDS,
				] )->create()->ID;
				global $pagenow, $post;
				$pagenow = 'edit.php';
				$post    = get_post( $id );

				$ticket_1 = $this->create_tc_ticket( $id, 10.10 );
				$ticket_2 = $this->create_tc_ticket( $id, 20.30 );
				$ticket_3 = $this->create_tc_ticket( $id, 30.40 );
				$ticket_4 = $this->create_tc_ticket( $id, 40.50 );
				$ticket_5 = $this->create_tc_ticket( $id, 50.60 );

				$fee_id_2 = $this->create_fee_for_ticket( $ticket_2, [ 'raw_amount' => 3.25 ] );
				$fee_id_4 = $this->create_fee_for_ticket( $ticket_4, [ 'raw_amount' => 5.50 ] );
				$fee_id_5 = $this->create_fee_for_ticket( $ticket_5, [ 'raw_amount' => 7.77 ] );

				return [[$ticket_1, $ticket_2, $ticket_3, $ticket_4, $ticket_5], [$fee_id_2, $fee_id_4, $fee_id_5]];
			}
		];
	}

	/**
	 * @dataProvider get_store_data_provider
	 */
	public function test_get_store_data( Closure $fixture ): void {
		[$ticket_ids, $fee_ids] = $fixture();

		$store_data = $this->make_controller()->get_store_data();

		$json = wp_json_encode( $store_data, JSON_SNAPSHOT_OPTIONS );

		$json = str_replace(
			$ticket_ids,
			'{{ticket_id}}',
			$json
		);

		$json = str_replace(
			$fee_ids,
			'{{fee_id}}',
			$json
		);
		$this->assertMatchesJsonSnapshot( $json );
	}
	/**
	 * @test
	 * @dataProvider asset_data_provider
	 */
	public function it_should_locate_assets_where_expected( $slug, $path ) {
		$this->make_controller()->register();

		$this->assertTrue( Assets::init()->exists( $slug ) );

		// We use false, because in CI mode the assets are not build so min aren't available. Its enough to check that the non-min is as expected.
		$asset_url = Assets::init()->get( $slug )->get_url( false );
		$this->assertEquals( plugins_url( $path, EVENT_TICKETS_MAIN_PLUGIN_FILE ), $asset_url );
	}

	public function asset_data_provider() {
		$assets = [
			'tec-tickets-order-modifiers-block-editor' => 'build/OrderModifiers/block-editor.js',
		];

		foreach ( $assets as $slug => $path ) {
			yield $slug => [ $slug, $path ];
		}
	}

	public function should_enqueue_block_editor_assets_data_provider(): Generator {
		yield 'Assets should not enqueue on incorrect action' => [
			function (): bool {
				// Simulate an action where the assets should NOT enqueue.
				do_action( 'some_other_action' );

				return false;
			},
		];

		yield 'Assets should enqueue on block editor action' => [
			function (): bool {
				// Simulate the block editor action.
				do_action( 'enqueue_block_editor_assets' );

				return true;
			},
		];
	}

	/**
	 * @dataProvider should_enqueue_block_editor_assets_data_provider
	 */
	public function test_should_enqueue_block_editor_assets( Closure $fixture ): void {
		$this->make_controller()->register();
		// Setup: Mock WordPress enqueue functions to capture enqueued scripts.
		$enqueued_assets = [];
		$this->set_fn_return(
			'wp_enqueue_script',
			function ( $handle ) use ( &$enqueued_assets ) {
				$enqueued_assets[] = $handle;
			},
			true
		);

		// Execute the fixture to simulate the action.
		$should_enqueue_assets = $fixture();

		// Assert whether the asset is enqueued based on the condition.
		if ( $should_enqueue_assets ) {
			$this->assertContains(
				'tec-tickets-order-modifiers-block-editor',
				$enqueued_assets,
				'The block editor asset should be enqueued.'
			);
		} else {
			$this->assertNotContains(
				'tec-tickets-order-modifiers-block-editor',
				$enqueued_assets,
				'The block editor asset should not be enqueued.'
			);
		}
	}
}
