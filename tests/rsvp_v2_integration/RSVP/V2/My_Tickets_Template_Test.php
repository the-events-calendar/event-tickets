<?php

namespace TEC\Tickets\RSVP\V2;

use Closure;
use Codeception\TestCase\WPTestCase;
use Generator;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe__Tickets__Editor__Template as Tickets_Editor_Template;

/**
 * Snapshot tests for My Tickets template changes (ticket-information.php and tickets-list.php).
 */
class My_Tickets_Template_Test extends WPTestCase {
	use SnapshotAssertions;
	use Ticket_Maker;
	use Order_Maker;

	/**
	 * Get the tickets editor template instance.
	 *
	 * @return Tickets_Editor_Template
	 */
	private function get_template(): Tickets_Editor_Template {
		return tribe( 'tickets.editor.template' );
	}

	/**
	 * Create an RSVP order fixture and return attendee data suitable for template rendering.
	 *
	 * @param bool   $show_not_going Whether the RSVP has "show not going" enabled.
	 * @param string $rsvp_status    The RSVP status meta value ('yes' or 'no').
	 * @param string $holder_name    The attendee holder name.
	 *
	 * @return array{attendee: array, post_id: int, ticket_id: int, order_id: int, post: \WP_Post}
	 */
	private function create_rsvp_fixture( bool $show_not_going = true, string $rsvp_status = 'yes', string $holder_name = 'Test User' ): array {
		$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_ticket( $post_id, 0 );

		$user_id = static::factory()->user->create( [
			'role'       => 'subscriber',
			'first_name' => 'Test',
			'last_name'  => 'User',
		] );

		$order = $this->create_order(
			[ $ticket_id => 1 ],
			[ 'purchaser_user_id' => $user_id ]
		);

		// Retroactively set the ticket type to TC-RSVP.
		update_post_meta( $ticket_id, '_type', Constants::TC_RSVP_TYPE );

		if ( $show_not_going ) {
			update_post_meta( $ticket_id, Constants::SHOW_NOT_GOING_META_KEY, '1' );
		}

		$attendees   = tribe( Module::class )->get_attendees_by_order_id( $order->ID );
		$attendee    = $attendees[0];
		$attendee_id = $attendee['attendee_id'];

		// Set the RSVP status meta on the attendee.
		update_post_meta( $attendee_id, Constants::RSVP_STATUS_META_KEY, $rsvp_status );

		// Set holder name.
		update_post_meta( $attendee_id, '_tec_tickets_commerce_purchaser_full_name', $holder_name );

		// Ensure the attendee array has the fields the templates need.
		$attendee['ticket_type'] = Constants::TC_RSVP_TYPE;
		$attendee['holder_name'] = $holder_name;
		$attendee['ID']          = $attendee_id;

		return [
			'attendee'  => $attendee,
			'post_id'   => $post_id,
			'ticket_id' => $ticket_id,
			'order_id'  => (int) $order->ID,
			'post'      => get_post( $post_id ),
		];
	}

	/**
	 * Replaces dynamic IDs with placeholders for stable snapshots.
	 *
	 * @param string $html The HTML to process.
	 * @param array  $ids  Associative array of placeholder => ID.
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

	public function ticket_information_data_provider(): Generator {
		yield 'RSVP going with show_not_going enabled' => [
			function (): array {
				return $this->create_rsvp_fixture( true, 'yes' );
			},
		];

		yield 'RSVP not going with show_not_going enabled' => [
			function (): array {
				return $this->create_rsvp_fixture( true, 'no' );
			},
		];

		yield 'RSVP going with show_not_going disabled' => [
			function (): array {
				return $this->create_rsvp_fixture( false, 'yes' );
			},
		];
	}

	/**
	 * @test
	 * @dataProvider ticket_information_data_provider
	 */
	public function it_should_render_ticket_information_for_rsvp( Closure $fixture ): void {
		$data     = $fixture();
		$template = $this->get_template();

		$html = $template->template(
			'tickets/my-tickets/ticket-information',
			[ 'attendee' => $data['attendee'] ],
			false
		);

		$html = $this->placehold_ids( $html, [
			'ATTENDEE_ID' => $data['attendee']['attendee_id'],
			'TICKET_ID'   => $data['ticket_id'],
			'POST_ID'     => $data['post_id'],
			'ORDER_ID'    => $data['order_id'],
		] );

		$this->assertMatchesHtmlSnapshot( $html );
	}

	public function tickets_list_data_provider(): Generator {
		yield 'RSVP attendee with holder name' => [
			function (): array {
				return $this->create_rsvp_fixture( false, 'yes', 'Jane Doe' );
			},
		];

		yield 'non-RSVP attendee' => [
			function (): array {
				$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
				$ticket_id = $this->create_tc_ticket( $post_id, 10 );

				$order     = $this->create_order( [ $ticket_id => 1 ] );
				$attendees = tribe( Module::class )->get_attendees_by_order_id( $order->ID );
				$attendee  = $attendees[0];

				$attendee['ticket_type'] = 'default';
				$attendee['holder_name'] = 'Should Not Appear';

				return [
					'attendee'  => $attendee,
					'post_id'   => $post_id,
					'ticket_id' => $ticket_id,
					'order_id'  => (int) $order->ID,
					'post'      => get_post( $post_id ),
				];
			},
		];
	}

	/**
	 * @test
	 * @dataProvider tickets_list_data_provider
	 */
	public function it_should_render_tickets_list( Closure $fixture ): void {
		$data     = $fixture();
		$template = $this->get_template();

		$titles = [
			'default'               => 'Tickets',
			Constants::TC_RSVP_TYPE => 'RSVPs',
		];

		$html = $template->template(
			'tickets/my-tickets/tickets-list',
			[
				'attendees' => [ $data['attendee'] ],
				'order_id'  => $data['order_id'],
				'post'      => $data['post'],
				'post_id'   => $data['post_id'],
				'titles'    => $titles,
			],
			false
		);

		$html = $this->placehold_ids( $html, [
			'ATTENDEE_ID' => $data['attendee']['attendee_id'],
			'TICKET_ID'   => $data['ticket_id'],
			'POST_ID'     => $data['post_id'],
			'ORDER_ID'    => $data['order_id'],
		] );

		$this->assertMatchesHtmlSnapshot( $html );
	}
}
