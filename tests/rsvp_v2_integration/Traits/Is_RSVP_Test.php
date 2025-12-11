<?php

namespace TECTicketsRSVPV2Tests\Traits;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\RSVP\V2\Meta;
use TEC\Tickets\RSVP\V2\Traits\Is_RSVP;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

class Is_RSVP_Test extends WPTestCase {
	use Ticket_Maker;

	/**
	 * A test class that uses the Is_RSVP trait for testing purposes.
	 *
	 * @var object
	 */
	private $trait_user;

	public function setUp(): void {
		parent::setUp();

		// Create an anonymous class that uses the trait.
		$this->trait_user = new class {
			use Is_RSVP;

			public function test_is_rsvp_data( array $data ): bool {
				return $this->is_rsvp_data( $data );
			}
		};
	}

	public function test_should_identify_rsvp_ticket_by_pinged_column(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_ticket( $post_id, 10 );

		// Initially not an RSVP ticket.
		$this->assertFalse( $this->trait_user->is_rsvp_ticket( $ticket_id ) );

		// Set the pinged column to tc-rsvp.
		global $wpdb;
		$wpdb->update(
			$wpdb->posts,
			[ 'pinged' => Meta::TC_RSVP_TYPE ],
			[ 'ID' => $ticket_id ]
		);
		clean_post_cache( $ticket_id );

		$this->assertTrue( $this->trait_user->is_rsvp_ticket( $ticket_id ) );
	}

	public function test_should_identify_rsvp_ticket_by_type_meta(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_ticket( $post_id, 10 );

		// Initially not an RSVP ticket.
		$this->assertFalse( $this->trait_user->is_rsvp_ticket( $ticket_id ) );

		// Set the _type meta.
		update_post_meta( $ticket_id, Meta::TYPE_META_KEY, Meta::TC_RSVP_TYPE );

		$this->assertTrue( $this->trait_user->is_rsvp_ticket( $ticket_id ) );
	}

	public function test_should_return_false_for_non_ticket_post_type(): void {
		$post_id = static::factory()->post->create();

		$this->assertFalse( $this->trait_user->is_rsvp_ticket( $post_id ) );
	}

	public function test_should_return_false_for_invalid_ticket_id(): void {
		$this->assertFalse( $this->trait_user->is_rsvp_ticket( 0 ) );
		$this->assertFalse( $this->trait_user->is_rsvp_ticket( 999999 ) );
	}

	public function test_should_identify_rsvp_attendee(): void {
		$attendee_id = static::factory()->post->create( [ 'post_type' => 'tec_tc_attendee' ] );

		// Initially not an RSVP attendee (no RSVP status meta).
		$this->assertFalse( $this->trait_user->is_rsvp_attendee( $attendee_id ) );

		// Set the RSVP status meta.
		update_post_meta( $attendee_id, Meta::RSVP_STATUS_KEY, Meta::STATUS_GOING );

		$this->assertTrue( $this->trait_user->is_rsvp_attendee( $attendee_id ) );
	}

	public function test_should_identify_rsvp_attendee_with_not_going_status(): void {
		$attendee_id = static::factory()->post->create( [ 'post_type' => 'tec_tc_attendee' ] );

		// Set the RSVP status to not going.
		update_post_meta( $attendee_id, Meta::RSVP_STATUS_KEY, Meta::STATUS_NOT_GOING );

		$this->assertTrue( $this->trait_user->is_rsvp_attendee( $attendee_id ) );
	}

	public function test_should_return_false_for_non_rsvp_attendee(): void {
		$attendee_id = static::factory()->post->create( [ 'post_type' => 'tec_tc_attendee' ] );

		$this->assertFalse( $this->trait_user->is_rsvp_attendee( $attendee_id ) );
	}

	public function test_should_identify_rsvp_data_array(): void {
		$rsvp_data = [
			'type' => Meta::TC_RSVP_TYPE,
			'name' => 'Test RSVP',
		];

		$this->assertTrue( $this->trait_user->test_is_rsvp_data( $rsvp_data ) );
	}

	public function test_should_return_false_for_non_rsvp_data_array(): void {
		$ticket_data = [
			'type' => 'default',
			'name' => 'Test Ticket',
		];

		$this->assertFalse( $this->trait_user->test_is_rsvp_data( $ticket_data ) );
	}

	public function test_should_return_false_for_data_without_type_key(): void {
		$data = [
			'name' => 'Test',
		];

		$this->assertFalse( $this->trait_user->test_is_rsvp_data( $data ) );
	}

	public function test_should_return_false_for_empty_array(): void {
		$this->assertFalse( $this->trait_user->test_is_rsvp_data( [] ) );
	}
}
