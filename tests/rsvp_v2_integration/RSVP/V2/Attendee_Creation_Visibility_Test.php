<?php

namespace TEC\Tickets\RSVP\V2;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;

/**
 * The RSVP attendee repository scopes every query to Attendees carrying the RSVP status meta. Anything it
 * creates therefore has to carry that meta too, or the repository produces Attendees it cannot find.
 *
 * That is not a cosmetic gap. The Attendees screen looks an Attendee up before offering to edit it, and
 * when the lookup comes back empty the edit form renders with no attendee ID and the JS submits it as an
 * "add" instead. Every attempt to edit creates another Attendee, which is itself unfindable, so the
 * problem compounds with each attempt.
 */
class Attendee_Creation_Visibility_Test extends WPTestCase {
	use Ticket_Maker;

	private function create_attendee_via_repository( array $args ): int {
		$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 10 ] ] );

		$attendee = tribe_attendees( 'rsvp' )
			->set_args(
				array_merge(
					[
						'title'     => 'Repository Attendee',
						'full_name' => 'Repository Attendee',
						'email'     => 'repository@example.com',
						'ticket_id' => $ticket_id,
						'post_id'   => $post_id,
					],
					$args
				)
			)
			->create();

		$this->assertInstanceOf( \WP_Post::class, $attendee, 'The repository should have created an Attendee.' );

		return $attendee->ID;
	}

	public function test_a_created_attendee_carries_the_rsvp_status_meta(): void {
		$attendee_id = $this->create_attendee_via_repository( [] );

		$this->assertTrue(
			metadata_exists( 'post', $attendee_id, Constants::RSVP_STATUS_META_KEY ),
			'An Attendee created without an explicit status still needs one, or no RSVP query will match it.'
		);
		$this->assertSame( 'yes', get_post_meta( $attendee_id, Constants::RSVP_STATUS_META_KEY, true ) );
	}

	public function test_a_created_attendee_is_findable_by_the_repository(): void {
		$attendee_id = $this->create_attendee_via_repository( [] );

		$found = tribe_attendees( 'rsvp' )->by( 'status', 'any' )->by( 'id', $attendee_id )->first();

		$this->assertInstanceOf(
			\WP_Post::class,
			$found,
			'The repository must be able to find the Attendee it just created.'
		);
		$this->assertEquals( $attendee_id, $found->ID );
	}

	public function test_a_supplied_attendee_status_is_preserved(): void {
		// The Attendees screen's add form supplies the answer under this name.
		$attendee_id = $this->create_attendee_via_repository( [ 'attendee_status' => 'no' ] );

		$this->assertSame(
			'no',
			get_post_meta( $attendee_id, Constants::RSVP_STATUS_META_KEY, true ),
			'A Not Going answer must not be overwritten by the default.'
		);
	}

	/**
	 * The Attendees screen reaches Tickets Commerce rather than the RSVP repository, building a manual
	 * Order and letting attendee generation run off it. That path has to stamp the status too.
	 */
	public function test_an_attendee_created_through_tickets_commerce_is_findable(): void {
		$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 10 ] ] );

		$attendee = tribe( Module::class )->create_attendee(
			tribe( Module::class )->get_ticket( $post_id, $ticket_id ),
			[
				'full_name'         => 'Commerce Attendee',
				'email'             => 'commerce@example.com',
				'send_ticket_email' => false,
			]
		);

		$this->assertInstanceOf( \WP_Post::class, $attendee, 'Tickets Commerce should have created an Attendee.' );

		$this->assertTrue(
			metadata_exists( 'post', $attendee->ID, Constants::RSVP_STATUS_META_KEY ),
			'An Attendee added from the Attendees screen still needs the RSVP status meta.'
		);

		$found = tribe_attendees( 'rsvp' )->by( 'status', 'any' )->by( 'id', $attendee->ID )->first();

		$this->assertInstanceOf(
			\WP_Post::class,
			$found,
			'The RSVP repository must be able to find an Attendee added from the Attendees screen.'
		);
	}
}
