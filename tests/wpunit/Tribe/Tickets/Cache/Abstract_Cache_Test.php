<?php

namespace Tribe\Tickets\Cache;

use Codeception\TestCase\WPTestCase;
use Tribe__Tickets__Cache__Abstract_Cache as Abstract_Cache;
use Tribe__Tickets__Commerce__PayPal__Main as PayPal;
use TEC\Tickets\Commerce\Module as Commerce;
use TEC\Tickets\Commerce\Ticket as Commerce_Ticket;

class Abstract_Cache_Test extends WPTestCase {
	private function make_test_class(): Abstract_Cache {
		return new class extends Abstract_Cache {
			public function reset_all() {
				return;
			}

			public function posts_without_ticket_types( array $post_types = null, $refetch = false ) {
				return $this->fetch_posts_without_ticket_types( $post_types, $refetch );
			}

			public function posts_with_ticket_types( array $post_types = null, $refetch = false ) {
				return $this->fetch_posts_with_ticket_types( $post_types, $refetch );
			}

			public function past_events( $refetch = false ) {
				return $this->fetch_past_events();
			}
		};
	}

	public function posts_with_ticket_types_fixtures(): \Generator {
		yield 'no ticket types' => [
			static function () {
				return [
					[],
					[],
					[]
				];
			}
		];

		yield 'no ticket types with 6 posts' => [
			static function () {
				// Create 6 posts.
				$post_1 = static::factory()->post->create( [ 'post_type' => 'post' ] );
				$post_2 = static::factory()->post->create( [ 'post_type' => 'post' ] );
				$post_3 = static::factory()->post->create( [ 'post_type' => 'post' ] );
				$post_4 = static::factory()->post->create( [ 'post_type' => 'post' ] );
				$post_5 = static::factory()->post->create( [ 'post_type' => 'post' ] );
				$post_6 = static::factory()->post->create( [ 'post_type' => 'post' ] );

				return [
					[ 'post' ],
					[],
					[ $post_1, $post_2, $post_3, $post_4, $post_5, $post_6 ]
				];
			}
		];

		yield 'PayPal tickets' => [
			static function () {
				// Create 6 posts.
				$post_1 = static::factory()->post->create( [ 'post_type' => 'post' ] );
				$post_2 = static::factory()->post->create( [ 'post_type' => 'post' ] );
				$post_3 = static::factory()->post->create( [ 'post_type' => 'post' ] );
				$post_4 = static::factory()->post->create( [ 'post_type' => 'post' ] );
				$post_5 = static::factory()->post->create( [ 'post_type' => 'post' ] );
				$post_6 = static::factory()->post->create( [ 'post_type' => 'post' ] );

				// Create 3 PayPal tickets credible simulacra.
				// Associate the 3 PayPal tickets to posts 1, 2 and 5 by meta.
				$ticket_cpt            = PayPal::get_instance()->ticket_object;
				$relationship_meta_key = PayPal::get_instance()->event_key;
				$ticket_1              = static::factory()->post->create(
					[
						'post_type'  => $ticket_cpt,
						'meta_input' => [
							$relationship_meta_key => $post_1
						]
					]
				);
				$ticket_2              = static::factory()->post->create(
					[
						'post_type'  => $ticket_cpt,
						'meta_input' => [
							$relationship_meta_key => $post_2
						]
					]
				);

				$ticket_3 = static::factory()->post->create(
					[
						'post_type'  => $ticket_cpt,
						'meta_input' => [
							$relationship_meta_key => $post_5
						]
					]
				);

				return [
					[ 'post' ],
					[ $post_1, $post_2, $post_5 ],
					[ $post_3, $post_4, $post_6 ]
				];
			}
		];

		yield 'Commerce Tickets' => [
			static function () {
				// Create 6 posts.
				$post_1 = static::factory()->post->create( [ 'post_type' => 'post' ] );
				$post_2 = static::factory()->post->create( [ 'post_type' => 'post' ] );
				$post_3 = static::factory()->post->create( [ 'post_type' => 'post' ] );
				$post_4 = static::factory()->post->create( [ 'post_type' => 'post' ] );
				$post_5 = static::factory()->post->create( [ 'post_type' => 'post' ] );
				$post_6 = static::factory()->post->create( [ 'post_type' => 'post' ] );

				// Create 3 Commerce tickets credible simulacra.
				// Associate the 3 Commerce tickets to posts 1, 2 and 5 by meta.
				$ticket_cpt            = Commerce_Ticket::POSTTYPE;
				$relationship_meta_key = Commerce_Ticket::$event_relation_meta_key;
				$ticket_1              = static::factory()->post->create(
					[
						'post_type'  => $ticket_cpt,
						'meta_input' => [
							$relationship_meta_key => $post_1
						]
					]
				);
				$ticket_2              = static::factory()->post->create(
					[
						'post_type'  => $ticket_cpt,
						'meta_input' => [
							$relationship_meta_key => $post_2
						]
					]
				);

				$ticket_3 = static::factory()->post->create(
					[
						'post_type'  => $ticket_cpt,
						'meta_input' => [
							$relationship_meta_key => $post_5
						]
					]
				);

				return [
					[ 'post' ],
					[ $post_1, $post_2, $post_5 ],
					[ $post_3, $post_4, $post_6 ]
				];
			}
		];
	}

	/**
	 * It should correctly fetch posts with ticket types
	 *
	 * @test
	 * @dataProvider posts_with_ticket_types_fixtures
	 */
	public function should_correctly_fetch_posts_with_ticket_types( \Closure $fixture ): void {
		[ $post_types, $expected_with, $expected_without ] = $fixture();

		$test_class = $this->make_test_class();
		$refetch    = true;
		$with       = $test_class->posts_with_ticket_types( $post_types, $refetch );
		$without    = $test_class->posts_without_ticket_types( $post_types, $refetch );

		$this->assertEquals( $expected_with, $with );
		$this->assertEquals( $expected_without, $without );
	}
}