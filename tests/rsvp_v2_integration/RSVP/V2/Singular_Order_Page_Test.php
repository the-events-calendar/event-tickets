<?php

namespace TEC\Tickets\RSVP\V2;

use Closure;
use Codeception\TestCase\WPTestCase;
use Generator;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Tickets\Commerce\Admin\Singular_Order_Page;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tests\Traits\With_Uopz;

class Singular_Order_Page_Test extends WPTestCase {
	use SnapshotAssertions;
	use Ticket_Maker;
	use Order_Maker;
	use With_Uopz;

	/**
	 * Helper to create an RSVP order with a specific going status.
	 *
	 * @param bool   $show_not_going Whether the RSVP has "show not going" enabled.
	 * @param string $rsvp_status    The RSVP status meta value ('yes' or 'no').
	 *
	 * @return array{ order: \WP_Post, post_id: int, rsvp_id: int }
	 */
	private function create_rsvp_order( bool $show_not_going = true, string $rsvp_status = 'yes' ): array {
		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$rsvp_id = $this->create_tc_rsvp_ticket( $post_id );

		if ( $show_not_going ) {
			update_post_meta( $rsvp_id, Constants::SHOW_NOT_GOING_META_KEY, '1' );
		}

		$order = $this->create_order( [ $rsvp_id => 1 ], [ 'purchaser_email' => 'user@example.com' ] );
		wp_update_post(
			[
				'ID'            => $order->ID,
				'post_date'     => '2024-05-12 12:30:45',
				'post_date_gmt' => '2024-05-12 12:30:45',
			]
		);
		$order = tec_tc_get_order( $order->ID );

		// Set the RSVP status on the attendee.
		$attendees = tribe( Module::class )->get_attendees_by_order_id( $order->ID );
		if ( ! empty( $attendees ) ) {
			update_post_meta( $attendees[0]['attendee_id'], Constants::RSVP_STATUS_META_KEY, $rsvp_status );
		}

		return [
			'order'   => $order,
			'post_id' => $post_id,
			'rsvp_id' => $rsvp_id,
		];
	}

	/**
	 * Replaces dynamic IDs with placeholders for stable snapshots.
	 *
	 * @param string $html     The HTML to process.
	 * @param array  $ids      Associative array of placeholder => ID.
	 *
	 * @return string
	 */
	private function placehold_ids( string $html, array $ids ): string {
		$ids = array_filter( $ids, static fn( $id ) => $id !== null );

		return str_replace(
			array_map( 'strval', array_values( $ids ) ),
			array_map( static fn( string $name ) => "{{ $name }}", array_keys( $ids ) ),
			$html
		);
	}

	public function order_details_data_provider(): Generator {
		yield 'RSVP order with going status and show_not_going enabled' => [
			function (): array {
				return $this->create_rsvp_order( true, 'yes' );
			},
		];

		yield 'RSVP order with not going status and show_not_going enabled' => [
			function (): array {
				return $this->create_rsvp_order( true, 'no' );
			},
		];

		yield 'RSVP order with show_not_going disabled' => [
			function (): array {
				return $this->create_rsvp_order( false, 'yes' );
			},
		];
	}

	/**
	 * @test
	 * @dataProvider order_details_data_provider
	 */
	public function it_should_render_order_details_for_rsvp_order( Closure $fixture ): void {
		$data  = $fixture();
		$order = $data['order'];

		$singular_page = tribe( Singular_Order_Page::class );

		ob_start();
		$singular_page->render_order_details( $order );
		$html = ob_get_clean();

		$html = $this->placehold_ids(
			$html,
			[
				'ORDER_ID' => $order->ID,
				'POST_ID'  => $data['post_id'],
				'RSVP_ID'  => $data['rsvp_id'],
			]
		);

		$this->assertMatchesHtmlSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function it_should_render_order_items_with_rsvp_ticket_type(): void {
		$data  = $this->create_rsvp_order();
		$order = $data['order'];

		$singular_page = tribe( Singular_Order_Page::class );

		ob_start();
		$singular_page->render_order_items( $order );
		$html = ob_get_clean();

		$html = $this->placehold_ids(
			$html,
			[
				'ORDER_ID' => $order->ID,
				'POST_ID'  => $data['post_id'],
				'RSVP_ID'  => $data['rsvp_id'],
			]
		);

		$this->assertMatchesHtmlSnapshot( $html );
	}

	public function rsvp_status_output_data_provider(): Generator {
		yield 'going status with show_not_going enabled' => [
			function (): array {
				return $this->create_rsvp_order( true, 'yes' );
			},
			'Going',
		];

		yield 'not going status with show_not_going enabled' => [
			function (): array {
				return $this->create_rsvp_order( true, 'no' );
			},
			'Not going',
		];
	}

	/**
	 * @test
	 * @dataProvider rsvp_status_output_data_provider
	 */
	public function it_should_render_rsvp_status_in_order_details( Closure $fixture, string $expected_status ): void {
		$data  = $fixture();
		$order = $data['order'];

		$metabox = tribe( Metabox::class );

		ob_start();
		$metabox->add_rsvp_status_to_single_order_details_metabox( $order );
		$html = ob_get_clean();

		$html = $this->placehold_ids(
			$html,
			[
				'ORDER_ID' => $order->ID,
				'POST_ID'  => $data['post_id'],
				'RSVP_ID'  => $data['rsvp_id'],
			]
		);

		$this->assertMatchesHtmlSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function it_should_not_render_rsvp_status_when_show_not_going_is_disabled(): void {
		$data  = $this->create_rsvp_order( false, 'yes' );
		$order = $data['order'];

		$metabox = tribe( Metabox::class );

		ob_start();
		$metabox->add_rsvp_status_to_single_order_details_metabox( $order );
		$html = ob_get_clean();

		$this->assertEmpty( trim( $html ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_render_rsvp_status_for_non_rsvp_order(): void {
		$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_ticket( $post_id, 10 );
		$order     = $this->create_order( [ $ticket_id => 1 ] );

		$metabox = tribe( Metabox::class );

		ob_start();
		$metabox->add_rsvp_status_to_single_order_details_metabox( $order );
		$html = ob_get_clean();

		$this->assertEmpty( trim( $html ) );
	}
}
