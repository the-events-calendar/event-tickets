<?php

namespace TEC\Tickets\Admin;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;

class Provider_Lock_Test extends WPTestCase {
	use Ticket_Maker;

	private function get_lock(): Provider_Lock {
		return tribe( Provider_Lock::class );
	}

	public function test_provider_is_not_locked_for_a_post_without_tickets(): void {
		$post_id = static::factory()->post->create();

		$this->assertFalse( $this->get_lock()->is_locked( $post_id ) );
	}

	public function test_provider_is_locked_once_a_ticket_exists(): void {
		$post_id = static::factory()->post->create();
		$this->create_tc_ticket( $post_id, 10 );

		$this->assertTrue( $this->get_lock()->is_locked( $post_id ) );
	}

	public function test_provider_is_not_locked_by_an_rsvp(): void {
		$post_id = static::factory()->post->create();
		$this->create_tc_rsvp_ticket( $post_id );

		$this->assertFalse( $this->get_lock()->is_locked( $post_id ) );
	}

	public function test_provider_is_locked_by_a_ticket_next_to_an_rsvp(): void {
		$post_id = static::factory()->post->create();
		$this->create_tc_rsvp_ticket( $post_id );
		$this->create_tc_ticket( $post_id, 10 );

		$this->assertTrue( $this->get_lock()->is_locked( $post_id ) );
	}
}
