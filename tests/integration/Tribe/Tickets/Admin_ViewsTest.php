<?php

namespace Tribe\Tickets;

use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Tickets__Admin__Views as Admin_Views;
use Tribe__Tickets__Global_Stock as Global_Stock;
use Tribe__Events__Main as TEC;

class Admin_ViewsTest extends \Codeception\TestCase\WPTestCase {
	use SnapshotAssertions;
	use Ticket_Maker;

	/**
	 * @before
	 */
	public function ensure_ticketable_post_types(): void {
		$ticketable   = tribe_get_option( 'ticket-enabled-post-types', [] );
		$ticketable[] = 'post';
		$ticketable[] = 'page';
		$ticketable[] = TEC::POSTTYPE;
		tribe_update_option( 'ticket-enabled-post-types', array_values( array_unique( $ticketable ) ) );
	}

	/**
	 * @before
	 */
	public function ensure_user_can_edit_posts(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	public function editor_total_capacity_template_data_provider(): \Generator {
		yield 'post' => [
			function () {
				$post_id   = static::factory()->post->create();
				$ticket_id = $this->create_tc_ticket( $post_id, 23, [
					'tribe-ticket' => [
						'mode'     => Global_Stock::OWN_STOCK_MODE,
						'capacity' => 89,
					],
				] );

				return [ $post_id, $ticket_id ];
			}
		];

		yield 'page' => [
			function () {
				$post_id   = static::factory()->post->create( [ 'post_type' => 'page' ] );
				$ticket_id = $this->create_tc_ticket( $post_id, 23, [
					'tribe-ticket' => [
						'mode'     => Global_Stock::OWN_STOCK_MODE,
						'capacity' => 89,
					],
				] );

				return [ $post_id, $ticket_id ];
			}
		];

		yield 'event' => [
			function () {
				$post_id   = tribe_events()->set_args( [
					'title'      => 'Test Event',
					'status'     => 'publish',
					'start_date' => '2020-01-01 00:00:00',
					'duration'   => 2 * HOUR_IN_SECONDS,
				] )->create()->ID;
				$ticket_id = $this->create_tc_ticket( $post_id, 23, [
					'tribe-ticket' => [
						'mode'     => Global_Stock::OWN_STOCK_MODE,
						'capacity' => 89,
					],
				] );

				return [ $post_id, $ticket_id ];
			}
		];
	}

	/**
	 * @dataProvider editor_total_capacity_template_data_provider
	 */
	public function test_editor_total_capacity_template( \Closure $fixture ): void {
		$ids     = $fixture();
		$post_id = $ids[0];
		// Simulate the editing of this post.
		global $post;
		$post = get_post( $post_id );

		$admin_views = tribe( Admin_Views::class );

		$html = $admin_views->template( 'editor/total-capacity', [], false );

		// Replace the post IDs in the snapshot with a placeholder.
		$html = str_replace(
			$ids,
			array_fill( 0, count( $ids ), '{POST_ID}' ),
			$html
		);

		$this->assertMatchesHtmlSnapshot( $html );
	}
}
