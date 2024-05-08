<?php

namespace TEC\Tickets\Seating;

use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Seating\Tables\Layouts;
use TEC\Tickets\Seating\Tables\Seat_Types;
use TEC\Tickets\Seating\Tests\Integration\Layouts_Factory;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Events__Main as TEC;

class Editor_Test extends Controller_Test_Case {
	use Layouts_Factory;
	use SnapshotAssertions;
	use Ticket_Maker;
	use Order_Maker;

	protected string $controller_class = Editor::class;

	/**
	 * @before
	 */
	public function set_up_test_case(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		global $pagenow;
		$pagenow = '';
	}

	/**
	 * @after
	 */
	public function restore_pagenow(): void {
		global $pagenow;
		$pagenow = '';
	}

	/**
	 * @before
	 * @after
	 */
	public function truncate_custom_tables(): void {
		Seat_Types::truncate();
		Layouts::truncate();
	}

	public function get_store_data_provider(): \Generator {
		yield 'new post' => [
			function (): void {
				global $pagenow;
				$pagenow = 'post-new.php';
				$this->given_many_layouts_in_db( 3 );
				$this->given_layouts_just_updated();
			}
		];

		yield 'existing post, using meta not set' => [
			function (): void {
				$id = self::factory()->post->create();
				global $pagenow, $post;
				$pagenow = 'edit.php';
				$post    = get_post( $id );
				$this->given_many_layouts_in_db( 3 );
				$this->given_layouts_just_updated();
			}
		];

		yield 'existing post, using meta set, layout not set' => [
			function (): void {
				$id = self::factory()->post->create();
				global $pagenow, $post;
				$pagenow = 'edit.php';
				$post    = get_post( $id );
				$this->given_many_layouts_in_db( 3 );
				$this->given_layouts_just_updated();
				update_post_meta( $id, Meta::META_KEY_ENABLED, 'yes' );
				delete_post_meta( $id, Meta::META_KEY_LAYOUT_ID );
			}
		];

		yield 'existing post, using meta set, layout set' => [
			function (): void {
				$id = self::factory()->post->create();
				global $pagenow, $post;
				$pagenow = 'edit.php';
				$post    = get_post( $id );
				$this->given_many_layouts_in_db( 3 );
				$this->given_layouts_just_updated();
				update_post_meta( $id, Meta::META_KEY_ENABLED, 'yes' );
				update_post_meta( $id, Meta::META_KEY_LAYOUT_ID, 'layout-1' );
			}
		];

		yield 'existing post, using meta set, layout set, with tickets' => [
			function (): void {
				$id = self::factory()->post->create();
				global $pagenow, $post;
				$pagenow = 'edit.php';
				$post    = get_post( $id );
				$this->given_many_layouts_in_db( 3 );
				$this->given_layouts_just_updated();
				update_post_meta( $id, Meta::META_KEY_ENABLED, 'yes' );
				update_post_meta( $id, Meta::META_KEY_LAYOUT_ID, 'layout-1' );
				$ticket_1 = $this->create_tc_ticket( $id, 10.10 );
				update_post_meta( $ticket_1, Meta::META_KEY_SEAT_TYPE, 'uuid-normal' );
				$ticket_2 = $this->create_tc_ticket( $id, 20.30 );
				update_post_meta( $ticket_2, Meta::META_KEY_SEAT_TYPE, 'uuid-forward-block' );
				$ticket_3 = $this->create_tc_ticket( $id, 30.40 );
				update_post_meta( $ticket_3, Meta::META_KEY_SEAT_TYPE, 'uuid-vip' );
			}
		];

		yield 'new event' => [
			function (): void {
				$post_type = TEC::POSTTYPE;
				global $pagenow;
				$pagenow               = 'post-new.php';
				$_REQUEST['post_type'] = $post_type;
				$this->given_many_layouts_in_db( 3 );
				$this->given_layouts_just_updated();
			}
		];

		yield 'existing event, using meta not set' => [
			function (): void {
				$id = tribe_events()->set_args( [
					'title'      => 'Test Event',
					'start_date' => '+1 week',
					'duration'   => 3 * HOUR_IN_SECONDS,
				] )->create()->ID;
				global $pagenow, $post;
				$pagenow = 'edit.php';
				$post    = get_post( $id );
				$this->given_many_layouts_in_db( 3 );
				$this->given_layouts_just_updated();
			}
		];

		yield 'existing event, using meta set, layout not set' => [
			function (): void {
				$id = tribe_events()->set_args( [
					'title'      => 'Test Event',
					'start_date' => '+1 week',
					'duration'   => 3 * HOUR_IN_SECONDS,
				] )->create()->ID;
				global $pagenow, $post;
				$pagenow = 'edit.php';
				$post    = get_post( $id );
				$this->given_many_layouts_in_db( 3 );
				$this->given_layouts_just_updated();
				update_post_meta( $id, Meta::META_KEY_ENABLED, 'yes' );
				delete_post_meta( $id, Meta::META_KEY_LAYOUT_ID );
			}
		];

		yield 'existing event, using meta set, layout set' => [
			function (): void {
				$id = tribe_events()->set_args( [
					'title'      => 'Test Event',
					'start_date' => '+1 week',
					'duration'   => 3 * HOUR_IN_SECONDS,
				] )->create()->ID;
				global $pagenow, $post;
				$pagenow = 'edit.php';
				$post    = get_post( $id );
				$this->given_many_layouts_in_db( 3 );
				$this->given_layouts_just_updated();
				update_post_meta( $id, Meta::META_KEY_ENABLED, 'yes' );
				update_post_meta( $id, Meta::META_KEY_LAYOUT_ID, 'layout-1' );
			}
		];

		yield 'existing event, using meta set, layout set, with tickets' => [
			function (): void {
				$id = tribe_events()->set_args( [
					'title'      => 'Test Event',
					'start_date' => '+1 week',
					'duration'   => 3 * HOUR_IN_SECONDS,
				] )->create()->ID;
				global $pagenow, $post;
				$pagenow = 'edit.php';
				$post    = get_post( $id );
				$this->given_many_layouts_in_db( 3 );
				$this->given_layouts_just_updated();
				update_post_meta( $id, Meta::META_KEY_ENABLED, 'yes' );
				update_post_meta( $id, Meta::META_KEY_LAYOUT_ID, 'layout-1' );
				$ticket_1 = $this->create_tc_ticket( $id, 10.10 );
				update_post_meta( $ticket_1, Meta::META_KEY_SEAT_TYPE, 'uuid-normal' );
				$ticket_2 = $this->create_tc_ticket( $id, 20.30 );
				update_post_meta( $ticket_2, Meta::META_KEY_SEAT_TYPE, 'uuid-forward-block' );
				$ticket_3 = $this->create_tc_ticket( $id, 30.40 );
				update_post_meta( $ticket_3, Meta::META_KEY_SEAT_TYPE, 'uuid-vip' );
			}
		];
	}

	/**
	 * @dataProvider get_store_data_provider
	 */
	public function test_get_store_data( \Closure $fixture ): void {
		$fixture();

		$store_data = $this->make_controller()->get_store_data();

		$this->assertMatchesJsonSnapshot( wp_json_encode( $store_data, JSON_SNAPSHOT_OPTIONS ) );
	}
}