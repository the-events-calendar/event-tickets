<?php

namespace TEC\Tickets\Admin\Attendees;

use Codeception\TestCase\WPTestCase;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use WP_Error;

/**
 * @covers \TEC\Tickets\Admin\Attendees\Modal
 */
class ModalTest extends WPTestCase {
	use Ticket_Maker;
	use Attendee_Maker;

	public function user_provider(): \Generator {
		yield 'administrator' => [ 'administrator', false, true ];
		yield 'author of the attendee event' => [ 'author', true, true ];
		yield 'author of another event' => [ 'author', false, false ];
		yield 'contributor' => [ 'contributor', false, false ];
		yield 'logged out' => [ null, false, false ];
	}

	/**
	 * @test
	 * @dataProvider user_provider
	 */
	public function it_should_only_render_attendee_details_for_users_who_can_edit_the_attendee_event( ?string $role, bool $owns_event, bool $expect_allowed ): void {
		$user_id     = $role ? static::factory()->user->create( [ 'role' => $role ] ) : 0;
		$owner_id    = $owns_event ? $user_id : static::factory()->user->create( [ 'role' => 'author' ] );
		$event_id    = static::factory()->post->create( [ 'post_author' => $owner_id ] );
		$ticket_id   = $this->create_tc_ticket( $event_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $event_id );

		// The client-supplied event ID points at a post the user can edit; only the attendee's own event may count.
		$own_post_id = $user_id ? static::factory()->post->create( [ 'post_author' => $user_id ] ) : 0;

		wp_set_current_user( $user_id );

		$response = tribe( Modal::class )->get_modal_content_ajax(
			'',
			[
				'request'       => 'tec_tickets_attendee_details',
				'attendeeId'    => $attendee_id,
				'eventId'       => $own_post_id,
				'ticketId'      => $ticket_id,
				'attendeeName'  => '',
				'attendeeEmail' => '',
			]
		);

		if ( $expect_allowed ) {
			$this->assertIsString( $response );
			$this->assertNotEmpty( $response );

			return;
		}

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertSame( 'tec_tickets_attendee_details_forbidden', $response->get_error_code() );
	}
}
