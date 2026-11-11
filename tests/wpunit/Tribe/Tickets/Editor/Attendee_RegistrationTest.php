<?php

namespace Tribe\Tickets\Editor;

use Codeception\TestCase\WPTestCase;
use Tribe__Tickets__Editor__Attendee_Registration as Attendee_Registration;

class Attendee_RegistrationTest extends WPTestCase {
	private $user_agent;

	private $ob_level;

	public function setUp(): void {
		parent::setUp();

		if ( ! function_exists( 'wp_start_cross_origin_isolation_output_buffer' ) ) {
			$this->markTestSkipped( 'Cross-origin isolation of the block editor needs WordPress 7.1 or later.' );
		}

		$this->user_agent           = $_SERVER['HTTP_USER_AGENT'] ?? null;
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36';
		$this->ob_level             = ob_get_level();
	}

	public function tearDown(): void {
		while ( ob_get_level() > $this->ob_level ) {
			ob_end_clean();
		}

		$_SERVER['HTTP_USER_AGENT'] = $this->user_agent;
		unset( $_GET['page'], $_GET['tribe_events_modal'] );

		parent::tearDown();
	}

	public function isolation_provider(): array {
		return [
			'modal page, uploader'        => [ 'attendee-registration', '1', 'administrator', true, true ],
			'page outside the modal'      => [ 'attendee-registration', null, 'administrator', true, false ],
			'another admin page'          => [ 'another-page', '1', 'administrator', true, false ],
			'user who cannot upload'      => [ 'attendee-registration', '1', 'contributor', true, false ],
			'media processing turned off' => [ 'attendee-registration', '1', 'administrator', false, false ],
		];
	}

	/**
	 * @test
	 * @dataProvider isolation_provider
	 */
	public function it_should_match_the_block_editor_isolation_only_inside_its_modal( $page, $modal, $role, $media_processing, $should_isolate ) {
		$_GET['page'] = $page;
		if ( null !== $modal ) {
			$_GET['tribe_events_modal'] = $modal;
		}
		wp_set_current_user( static::factory()->user->create( [ 'role' => $role ] ) );
		add_filter( 'wp_client_side_media_processing_enabled', $media_processing ? '__return_true' : '__return_false' );

		( new Attendee_Registration() )->admin_init();

		$this->assertSame( $this->ob_level + ( $should_isolate ? 1 : 0 ), ob_get_level() );
	}
}
