<?php

namespace Tribe\Tickets\Admin;

use Generator;
use Codeception\TestCase\WPTestCase;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use Tribe\Tests\Traits\With_Uopz;
use Tribe__Tickets__Admin__Move_Tickets;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;

class Move_Tickets_TemplateTest extends WPTestCase {
	use SnapshotAssertions;
	use With_Uopz;
	use Ticket_Maker;
	use Attendee_Maker;

	public function get_move_tickets_data_provider(): Generator {
		yield 'no attendees' => [
			[
				'title'              => __( 'Move Attendees', 'event-tickets' ),
				'mode'               => 'move_tickets',
				'check'              => wp_create_nonce( 'move_tickets' ),
				'event_name'         => 'Moving Attendee - Test Event',
				'attendees'          => [],
				'multiple_providers' => false,
			]
		];

		yield 'event has multiple providers' => [
			[
				'title'              => __( 'Move Attendees', 'event-tickets' ),
				'mode'               => 'move_tickets',
				'check'              => wp_create_nonce( 'move_tickets' ),
				'event_name'         => 'Moving Attendee - Test Event',
				'attendees'          => [ 1, 2 ],
				'multiple_providers' => true,
			]
		];

		yield 'event has single provider' => [
			[
				'title'              => __( 'Move Attendees', 'event-tickets' ),
				'mode'               => 'move_tickets',
				'check'              => wp_create_nonce( 'move_tickets' ),
				'event_name'         => 'Moving Attendee - Test Event',
				'attendees'          => [ 1 ],
				'multiple_providers' => false,
			]
		];

		yield 'event has single provider with multiple attendees' => [
			[
				'title'              => __( 'Move Attendees', 'event-tickets' ),
				'mode'               => 'move_tickets',
				'check'              => wp_create_nonce( 'move_tickets' ),
				'event_name'         => 'Moving Attendee - Test Event',
				'attendees'          => [ 1, 2 ],
				'multiple_providers' => false,
			]
		];
	}

	/**
	 * @dataProvider get_move_tickets_data_provider
	 * @covers Tribe__Tickets__Admin__Move_Tickets::dialog
	 */
	public function test_move_tickets_html( array $template_vars ): void {
		ob_start();
		extract( $template_vars );
		include EVENT_TICKETS_DIR . '/src/admin-views/move-tickets.php';
		$html = ob_get_clean();

		$this->assertMatchesHtmlSnapshot( $html );
	}

	/**
	 * Regression test: ensures the $hook_suffix global is initialized to a string before
	 * iframe_header() fires admin_enqueue_scripts. Strictly-typed listeners (e.g. WC Stripe's
	 * WC_Stripe_Plugins_Page_Controller::enqueue_scripts(string $hook_suffix)) otherwise
	 * fatal with a TypeError when the Move Attendees dialog is loaded.
	 *
	 * @covers Tribe__Tickets__Admin__Move_Tickets::dialog
	 */
	public function test_dialog_initializes_hook_suffix_to_string_before_admin_enqueue_scripts(): void {
		if ( ! function_exists( 'uopz_set_return' ) ) {
			$this->markTestSkipped( 'uopz extension is required for this test.' );
		}

		// wp-admin/includes/template.php (where iframe_header lives) is not loaded by default in wpunit.
		if ( ! function_exists( 'iframe_header' ) ) {
			require_once ABSPATH . 'wp-admin/includes/template.php';
		}

		$original_hook_suffix = $GLOBALS['hook_suffix'] ?? null;
		$original_get         = $_GET;

		// Simulate the failure mode: $hook_suffix global was never populated for this iframe request.
		$GLOBALS['hook_suffix'] = null;

		// A strictly-typed listener — mirrors WC Stripe's signature and would TypeError on null.
		$captured = 'NOT_CALLED';
		$listener = static function ( string $hook_suffix ) use ( &$captured ): void {
			$captured = $hook_suffix;
		};
		add_action( 'admin_enqueue_scripts', $listener );

		// Set up dialog request prerequisites.
		$event_id = $this->factory->post->create( [ 'post_type' => 'page' ] );
		$_GET     = [
			'dialog'     => 'move_tickets',
			'check'      => wp_create_nonce( 'move_tickets' ),
			'event_id'   => (string) $event_id,
			'ticket_ids' => '',
		];

		// Replace iframe_header with a stub that fires admin_enqueue_scripts the way WP core does
		// and then throws a marker — short-circuits the rest of dialog() (template include + exit())
		// without emitting headers or producing iframe markup.
		$marker = new \RuntimeException( 'iframe_header_stub_marker' );
		$this->set_fn_return(
			'iframe_header',
			static function () use ( $marker ) {
				do_action( 'admin_enqueue_scripts', $GLOBALS['hook_suffix'] );
				throw $marker;
			},
			true
		);

		$type_error = null;
		try {
			( new \Tribe__Tickets__Admin__Move_Tickets() )->dialog();
			$this->fail( 'iframe_header stub should have thrown the marker exception.' );
		} catch ( \TypeError $e ) {
			// Regression: strictly-typed listener received non-string $hook_suffix.
			$type_error = $e;
		} catch ( \RuntimeException $e ) {
			if ( $e !== $marker ) {
				throw $e;
			}
		} finally {
			remove_action( 'admin_enqueue_scripts', $listener );
			$GLOBALS['hook_suffix'] = $original_hook_suffix;
			$_GET                   = $original_get;
		}

		$this->assertNull(
			$type_error,
			'dialog() must not raise a TypeError when a strictly-typed admin_enqueue_scripts listener is registered.'
		);
		$this->assertNotSame( 'NOT_CALLED', $captured, 'Listener should have been invoked via admin_enqueue_scripts.' );
		$this->assertIsString( $captured, 'Listener must receive a string $hook_suffix, not null.' );
	}

	/**
	 * Test that the posts returned by get_possible_matches() are sorted alphabetically by title.
	 *
	 * @covers Tribe__Tickets__Admin__Move_Tickets::get_possible_matches
	 */
	public function test_posts_are_sorted_alphabetically_by_title(): void {
		// Create posts with deliberately out-of-order titles.
		$post_z = $this->factory->post->create([
			'post_type' => 'post',
			'post_title' => 'Zebra Event',
		]);

		$post_a = $this->factory->post->create([
			'post_type' => 'post',
			'post_title' => 'Aardvark Event',
		]);

		$post_m = $this->factory->post->create([
			'post_type' => 'post',
			'post_title' => 'Moose Event',
		]);

		// The post IDs should be in ascending order (post_z, post_a, post_m)
		// But the titles should sort as: Aardvark, Moose, Zebra

		// Use reflection to access the protected method.
		$move_tickets = new Tribe__Tickets__Admin__Move_Tickets();
		$reflection = new \ReflectionClass($move_tickets);
		$method = $reflection->getMethod('get_possible_matches');
		$method->setAccessible(true);
		
		$posts = $method->invoke($move_tickets, [
			'post_type' => 'post',
			'search_terms' => 'Event',
		]);

		// Get the keys (titles) in the order they appear in the returned array.
		$titles_in_order = array_keys($posts);

		// The first title should be "Aardvark Event".
		$this->assertStringContainsString('Aardvark', $titles_in_order[0], 'First title should contain "Aardvark"');

		// The second title should be "Moose Event".
		$this->assertStringContainsString('Moose', $titles_in_order[1], 'Second title should contain "Moose"');

		// The third title should be "Zebra Event".
		$this->assertStringContainsString('Zebra', $titles_in_order[2], 'Third title should contain "Zebra"');

		// Also check that the values (post IDs) correspond to the correct titles.
		$post_ids_in_order = array_values($posts);
		$this->assertEquals($post_a, $post_ids_in_order[0], 'First post ID should be "Aardvark Event" post');
		$this->assertEquals($post_m, $post_ids_in_order[1], 'Second post ID should be "Moose Event" post');
		$this->assertEquals($post_z, $post_ids_in_order[2], 'Third post ID should be "Zebra Event" post');
	}
}