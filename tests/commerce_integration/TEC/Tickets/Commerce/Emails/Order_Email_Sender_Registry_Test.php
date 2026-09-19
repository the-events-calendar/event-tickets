<?php

namespace TEC\Tickets\Commerce\Emails;

use Codeception\TestCase\WPTestCase;
use WP_Post;

/**
 * The registry's whole point is to be constructible with a plain array of senders, with no
 * container involved, so these tests build it directly with hand-written stub senders.
 */
class Order_Email_Sender_Registry_Test extends WPTestCase {
	/**
	 * Builds a stub `Order_Email_Sender_Interface` that records whether `send()` was called.
	 */
	private function make_sender( bool $supports, object $calls, string $name ): Order_Email_Sender_Interface {
		return new class( $supports, $calls, $name ) implements Order_Email_Sender_Interface {
			private bool $supports;
			private object $calls;
			private string $name;

			public function __construct( bool $supports, object $calls, string $name ) {
				$this->supports = $supports;
				$this->calls    = $calls;
				$this->name     = $name;
			}

			public function supports( WP_Post $order ): bool {
				return $this->supports;
			}

			public function send( WP_Post $order ): void {
				$this->calls->sent[] = $this->name;
			}
		};
	}

	/**
	 * @test
	 */
	public function it_should_dispatch_to_the_first_supporting_sender_only(): void {
		$calls  = (object) [ 'sent' => [] ];
		$order  = static::factory()->post->create_and_get();

		$registry = new Order_Email_Sender_Registry(
			[
				$this->make_sender( false, $calls, 'not_supporting' ),
				$this->make_sender( true, $calls, 'first_match' ),
				$this->make_sender( true, $calls, 'second_match' ),
			]
		);

		$registry->send( $order );

		$this->assertSame( [ 'first_match' ], $calls->sent, 'Only the first supporting sender should send.' );
	}

	/**
	 * @test
	 */
	public function it_should_be_a_silent_no_op_when_no_sender_supports_the_order(): void {
		$calls = (object) [ 'sent' => [] ];
		$order = static::factory()->post->create_and_get();

		$registry = new Order_Email_Sender_Registry(
			[
				$this->make_sender( false, $calls, 'a' ),
				$this->make_sender( false, $calls, 'b' ),
			]
		);

		$registry->send( $order );

		$this->assertSame( [], $calls->sent, 'No sender should have been called.' );
	}

	/**
	 * @test
	 */
	public function it_should_ignore_registered_values_that_are_not_senders(): void {
		$calls = (object) [ 'sent' => [] ];
		$order = static::factory()->post->create_and_get();

		$registry = new Order_Email_Sender_Registry(
			[
				'not-a-sender-object',
				$this->make_sender( true, $calls, 'valid_sender' ),
			]
		);

		$registry->send( $order );

		$this->assertSame( [ 'valid_sender' ], $calls->sent );
	}

	/**
	 * @test
	 */
	public function it_should_not_dispatch_to_any_sender_when_emails_are_disabled(): void {
		$calls = (object) [ 'sent' => [] ];
		$order = static::factory()->post->create_and_get();

		add_filter( 'tec_tickets_emails_is_enabled', '__return_false' );

		$registry = new Order_Email_Sender_Registry(
			[
				$this->make_sender( true, $calls, 'would_have_sent' ),
			]
		);

		$registry->send( $order );

		remove_filter( 'tec_tickets_emails_is_enabled', '__return_false' );

		$this->assertSame( [], $calls->sent, 'No sender should be dispatched to while emails are disabled.' );
	}
}
