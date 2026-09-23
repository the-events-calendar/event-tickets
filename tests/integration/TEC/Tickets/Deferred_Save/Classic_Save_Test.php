<?php

namespace TEC\Tickets\Deferred_Save;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Module;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;

class Classic_Save_Test extends WPTestCase {
	use Ticket_Maker;
	use With_Tickets_Commerce;

	private array $deferred_posts = [];

	public function setUp(): void {
		parent::setUp();
		add_filter(
			'tec_tickets_deferred_save_enabled',
			function ( bool $enabled, int $post_id ): bool {
				return in_array( $post_id, $this->deferred_posts, true ) ? true : $enabled;
			},
			10,
			2
		);
	}

	public function tearDown(): void {
		$_POST                = [];
		$this->deferred_posts = [];
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	protected function log_in_as_admin(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	protected function create_deferred_post(): int {
		$post_id                = static::factory()->post->create();
		$this->deferred_posts[] = $post_id;

		return $post_id;
	}

	protected function ticket_data( string $name, array $overrides = [] ): array {
		return array_merge(
			[
				'ticket_name'     => $name,
				'ticket_price'    => '10',
				'ticket_provider' => Module::class,
				'tribe-ticket'    => [ 'mode' => 'own', 'capacity' => '25' ],
			],
			$overrides
		);
	}

	/**
	 * Sets the request the way WordPress hands it to `save_post`: slashed, as `wp_magic_quotes()` leaves `$_POST`.
	 */
	protected function post_payload( array $payload, bool $with_nonce = true ): void {
		$post = [ 'tec_tickets' => $payload ];
		if ( $with_nonce ) {
			$post[ Classic_Save::NONCE_FIELD ] = wp_create_nonce( Classic_Save::NONCE_ACTION );
		}
		$_POST = wp_slash( $post );
	}

	protected function ticket_names( int $post_id ): array {
		return array_map( 'get_the_title', tribe_tickets()->where( 'event', $post_id )->get_ids() );
	}

	/**
	 * @test
	 */
	public function it_saves_the_payload_when_a_deferred_post_is_saved(): void {
		$this->log_in_as_admin();
		$post_id = $this->create_deferred_post();
		$this->post_payload( [ 'create' => [ $this->ticket_data( 'From the form' ) ] ] );

		wp_update_post( [ 'ID' => $post_id, 'post_title' => 'Saved' ] );

		$this->assertSame( [ 'From the form' ], $this->ticket_names( $post_id ) );
	}

	/**
	 * @test
	 */
	public function it_does_nothing_without_a_valid_nonce(): void {
		$this->log_in_as_admin();
		$post_id = $this->create_deferred_post();

		$this->post_payload( [ 'create' => [ $this->ticket_data( 'No nonce' ) ] ], false );
		wp_update_post( [ 'ID' => $post_id, 'post_title' => 'Saved' ] );
		$this->assertSame( [], $this->ticket_names( $post_id ) );

		$this->post_payload( [ 'create' => [ $this->ticket_data( 'Bad nonce' ) ] ], false );
		$_POST[ Classic_Save::NONCE_FIELD ] = 'not-a-nonce';
		wp_update_post( [ 'ID' => $post_id, 'post_title' => 'Saved again' ] );
		$this->assertSame( [], $this->ticket_names( $post_id ) );
	}

	/**
	 * @test
	 */
	public function it_does_nothing_on_an_autosave_or_a_revision(): void {
		$this->log_in_as_admin();
		$post_id = $this->create_deferred_post();
		$this->post_payload( [ 'create' => [ $this->ticket_data( 'Autosaved' ) ] ] );

		$_POST['post_ID'] = $post_id;
		wp_create_post_autosave(
			[
				'post_ID'      => $post_id,
				'post_type'    => 'post',
				'post_title'   => 'Autosave title',
				'post_content' => 'Autosave content',
				'post_excerpt' => '',
			]
		);
		wp_save_post_revision( $post_id );

		$this->assertSame( [], $this->ticket_names( $post_id ) );
	}

	/**
	 * @test
	 */
	public function it_does_nothing_for_a_post_that_does_not_use_deferred_save(): void {
		$this->log_in_as_admin();
		$post_id = static::factory()->post->create();
		$this->post_payload( [ 'create' => [ $this->ticket_data( 'Not deferred' ) ] ] );

		wp_update_post( [ 'ID' => $post_id, 'post_title' => 'Saved' ] );

		$this->assertSame( [], $this->ticket_names( $post_id ) );
	}

	/**
	 * @test
	 */
	public function it_does_nothing_during_a_rest_request(): void {
		$this->log_in_as_admin();
		$post_id = $this->create_deferred_post();
		$this->post_payload( [ 'create' => [ $this->ticket_data( 'Via REST form body' ) ] ] );

		add_filter( 'wp_is_rest_endpoint', '__return_true' );
		wp_update_post( [ 'ID' => $post_id, 'post_title' => 'Saved' ] );
		remove_filter( 'wp_is_rest_endpoint', '__return_true' );

		$this->assertSame( [], $this->ticket_names( $post_id ) );
	}

	/**
	 * @test
	 */
	public function it_attaches_a_ticket_to_a_post_published_for_the_first_time(): void {
		$this->log_in_as_admin();
		$post_id                = static::factory()->post->create( [ 'post_status' => 'auto-draft' ] );
		$this->deferred_posts[] = $post_id;
		$this->post_payload( [ 'create' => [ $this->ticket_data( 'First publish' ) ] ] );

		wp_update_post( [ 'ID' => $post_id, 'post_status' => 'publish', 'post_title' => 'New post' ] );

		$this->assertSame( [ 'First publish' ], $this->ticket_names( $post_id ) );
	}

	/**
	 * @test
	 */
	public function the_order_saved_by_the_form_survives_an_update_without_a_menu_order(): void {
		$this->log_in_as_admin();
		// A page: the ticket order is saved by a hook attached per ticketable type at boot, which `post` is not.
		$post_id                = static::factory()->post->create( [ 'post_type' => 'page' ] );
		$this->deferred_posts[] = $post_id;
		$ticket_id = $this->create_tc_ticket( $post_id, 10 );
		$this->post_payload( [ 'update' => [ $ticket_id => [ 'ticket_name' => 'Reordered' ] ] ] );
		$_POST['tribe-tickets'] = [ 'list' => [ $ticket_id => [ 'order' => 4 ] ] ];

		wp_update_post( [ 'ID' => $post_id, 'post_title' => 'Saved' ] );

		$this->assertSame( 'Reordered', get_the_title( $ticket_id ) );
		$this->assertSame( 4, get_post( $ticket_id )->menu_order );
	}

	/**
	 * @test
	 */
	public function the_nonce_field_renders_the_expected_input(): void {
		$html = Classic_Save::nonce_field();

		$this->assertStringContainsString( 'name="' . Classic_Save::NONCE_FIELD . '"', $html );
		$this->assertStringContainsString( 'type="hidden"', $html );
		$this->assertTrue( (bool) wp_verify_nonce( $this->extract_nonce( $html ), Classic_Save::NONCE_ACTION ) );
	}

	protected function extract_nonce( string $html ): string {
		preg_match( '/value="([^"]+)"/', $html, $m );

		return $m[1] ?? '';
	}
}
