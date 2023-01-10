<?php

namespace Tribe\Tickets\Main\Post;

use Tribe\Tickets\Test\Testcases\Ticket_Object_TestCase;
use Tribe__Tickets__Main as Main;

class UTCTest extends Ticket_Object_TestCase {
	protected $timezone = 'UTC';

	/**
	 * It should inject buy button into oembed for post with tickets.
	 *
	 * @skip // @todo @moraleida fix this test
	 *
	 * @test
	 */
	public function should_inject_buy_button_into_oembed_for_post_with_tickets() {
		$main = Main::instance();

		$post_id  = $this->factory()->post->create();
		$post_id2 = $this->factory()->post->create();

		$overrides = [
			'meta_input' => [
				'_ticket_start_date' => $this->get_local_datetime_string_from_utc_time( strtotime( '-10 minutes' ) ),
				'_ticket_end_date'   => $this->get_local_datetime_string_from_utc_time( strtotime( '+10 minutes' ) ),
			],
		];

		$paypal_ticket_id = $this->create_paypal_ticket_basic( $post_id, 1, $overrides );
		$rsvp_ticket_id   = $this->create_rsvp_ticket( $post_id, $overrides );

		// Add other ticket/attendees for another post so we can confirm we only returned the correct attendees.
		$paypal_ticket_id2 = $this->create_paypal_ticket_basic( $post_id2, 1, $overrides );
		$rsvp_ticket_id2   = $this->create_rsvp_ticket( $post_id2, $overrides );

		$GLOBALS['post'] = get_post( $post_id );
		setup_postdata( $post_id );

		ob_start();
		$main->inject_buy_button_into_oembed();
		$output = ob_get_clean();

		self::assertContains( '<a class="tribe-event-buy"', $output );
	}

	/**
	 * It should not inject buy button into oembed for post with no tickets.
	 *
	 * @test
	 */
	public function should_not_inject_buy_button_into_oembed_for_post_with_no_tickets() {
		$main = Main::instance();

		$post_id  = $this->factory()->post->create();
		$post_id2 = $this->factory()->post->create();

		$GLOBALS['post'] = get_post( $post_id );
		setup_postdata( $post_id );

		ob_start();
		$main->inject_buy_button_into_oembed();
		$output = ob_get_clean();

		self::assertEquals( '', $output );
	}

	/**
	 * It should not inject buy button into oembed for non-post.
	 *
	 * @test
	 */
	public function should_not_inject_buy_button_into_oembed_for_non_post() {
		$main = Main::instance();

		$post_id  = $this->factory()->post->create();
		$post_id2 = $this->factory()->post->create();

		$overrides = [
			'meta_input' => [
				'_ticket_start_date' => $this->get_local_datetime_string_from_utc_time( $this->earlier_date ),
				'_ticket_end_date'   => $this->get_local_datetime_string_from_utc_time( $this->later_date ),
			],
		];

		$paypal_ticket_id = $this->create_paypal_ticket_basic( $post_id, 1, $overrides );
		$rsvp_ticket_id   = $this->create_rsvp_ticket( $post_id, $overrides );

		// Add other ticket/attendees for another post so we can confirm we only returned the correct attendees.
		$paypal_ticket_id2 = $this->create_paypal_ticket_basic( $post_id2, 1, $overrides );
		$rsvp_ticket_id2   = $this->create_rsvp_ticket( $post_id2, $overrides );

		wp_reset_postdata();

		ob_start();
		$main->inject_buy_button_into_oembed();
		$output = ob_get_clean();

		self::assertEquals( '', $output );
	}
}
