<?php

namespace TEC\Tickets\RSVP\V2;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;
use Tribe\Tests\Traits\With_Uopz;
use Tribe__Tickets__Global_Stock as Global_Stock;
use WP_Post;

class Classic_Editor_Save_Test extends WPTestCase {
	use Ticket_Maker;
	use With_Uopz;

	/**
	 * @var Classic_Editor
	 */
	private Classic_Editor $classic_editor;

	/**
	 * @var array<string,mixed>
	 */
	private array $original_post;

	protected function setUp(): void {
		parent::setUp();

		$this->classic_editor = tribe( Classic_Editor::class );
		$this->original_post    = $_POST;
	}

	protected function tearDown(): void {
		$_POST = $this->original_post;

		parent::tearDown();
	}

	/**
	 * @return array<string,mixed>
	 */
	private function get_base_post_data(): array {
		return [
			'ticket_type'             => Constants::TC_RSVP_TYPE,
			'tec_tickets_rsvp_enable' => 'on',
			'ticket_provider'         => Module::class,
			'rsvp_start_date'         => '2026-06-05',
			'rsvp_start_time'         => '09:00:00',
			'rsvp_end_date'           => '2026-06-10',
			'rsvp_end_time'           => '17:00:00',
		];
	}

	private function save_post_with_data( int $post_id, array $post_data ): void {
		$post = get_post( $post_id );
		$this->assertInstanceOf( WP_Post::class, $post );

		$this->classic_editor->process_rsvp_post_save( $post_id, $post_data );
	}

	private function get_rsvp_ticket_id_for_event( int $post_id ): ?int {
		$ticket_id = tribe( 'tickets.ticket-repository.rsvp' )
			->where( 'event', $post_id )
			->first_id();

		return $ticket_id ? (int) $ticket_id : null;
	}

	/**
	 * @test
	 */
	public function it_should_create_rsvp_on_post_save_when_enabled(): void {
		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

		$this->save_post_with_data(
			$post_id,
			array_merge(
				$this->get_base_post_data(),
				[
					'rsvp_limit'     => '50',
					'show_not_going' => '1',
				]
			)
		);

		$ticket_id = $this->get_rsvp_ticket_id_for_event( $post_id );

		$this->assertNotNull( $ticket_id );
		$this->assertSame( Constants::TC_RSVP_TYPE, get_post_meta( $ticket_id, Ticket::$type_meta_key, true ) );
		$this->assertSame( '50', get_post_meta( $ticket_id, tribe( 'tickets.handler' )->key_capacity, true ) );
		$this->assertSame( '1', get_post_meta( $ticket_id, Constants::SHOW_NOT_GOING_META_KEY, true ) );
	}

	/**
	 * @test
	 */
	public function it_should_update_rsvp_on_post_save(): void {
		$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$rsvp_id   = $this->create_tc_rsvp_ticket(
			$post_id,
			[
				'tribe-ticket' => [
					'mode'     => 'own',
					'capacity' => 25,
				],
			]
		);

		$this->save_post_with_data(
			$post_id,
			array_merge(
				$this->get_base_post_data(),
				[
					'rsvp_id'        => (string) $rsvp_id,
					'rsvp_limit'     => '100',
					'show_not_going' => '1',
				]
			)
		);

		$this->assertSame( $rsvp_id, $this->get_rsvp_ticket_id_for_event( $post_id ) );
		$this->assertSame( '100', get_post_meta( $rsvp_id, tribe( 'tickets.handler' )->key_capacity, true ) );
		$this->assertSame( '1', get_post_meta( $rsvp_id, Constants::SHOW_NOT_GOING_META_KEY, true ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_create_rsvp_when_enable_is_unchecked(): void {
		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

		$post_data = $this->get_base_post_data();
		unset( $post_data['tec_tickets_rsvp_enable'] );

		$this->save_post_with_data( $post_id, $post_data );

		$this->assertNull( $this->get_rsvp_ticket_id_for_event( $post_id ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_save_when_ticket_type_is_not_tc_rsvp(): void {
		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

		$this->save_post_with_data(
			$post_id,
			array_merge(
				$this->get_base_post_data(),
				[
					'ticket_type' => 'default',
					'rsvp_limit'  => '50',
				]
			)
		);

		$this->assertNull( $this->get_rsvp_ticket_id_for_event( $post_id ) );
	}

	/**
	 * @test
	 */
	public function it_should_save_unlimited_capacity_when_limit_is_empty(): void {
		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

		$this->save_post_with_data(
			$post_id,
			array_merge(
				$this->get_base_post_data(),
				[
					'rsvp_limit' => '',
				]
			)
		);

		$ticket_id = $this->get_rsvp_ticket_id_for_event( $post_id );

		$this->assertNotNull( $ticket_id );
		$this->assertSame( '-1', get_post_meta( $ticket_id, tribe( 'tickets.handler' )->key_capacity, true ) );
		$this->assertSame( 'no', get_post_meta( $ticket_id, '_manage_stock', true ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_save_on_autosave(): void {
		if ( ! defined( 'DOING_AUTOSAVE' ) ) {
			define( 'DOING_AUTOSAVE', true );
		}

		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$post    = get_post( $post_id );

		$this->assertInstanceOf( WP_Post::class, $post );

		$_POST = array_merge(
			$this->get_base_post_data(),
			[
				'rsvp_limit' => '50',
			]
		);

		$this->classic_editor->save_rsvp_on_post_save( $post_id, $post );

		$this->assertNull( $this->get_rsvp_ticket_id_for_event( $post_id ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_save_without_edit_post_capability(): void {
		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$user_id = static::factory()->user->create( [ 'role' => 'subscriber' ] );
		$post    = get_post( $post_id );

		wp_set_current_user( $user_id );

		$this->assertInstanceOf( WP_Post::class, $post );

		$_POST = array_merge(
			$this->get_base_post_data(),
			[
				'rsvp_limit' => '50',
			]
		);

		$this->classic_editor->save_rsvp_on_post_save( $post_id, $post );

		$this->assertNull( $this->get_rsvp_ticket_id_for_event( $post_id ) );
	}

	/**
	 * @test
	 */
	public function it_should_map_post_data_to_ticket_add_format(): void {
		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

		$data = $this->classic_editor->map_post_to_ticket_data(
			[
				'rsvp_id'         => '123',
				'rsvp_limit'      => '75',
				'rsvp_start_date' => '2026-06-05',
				'rsvp_start_time' => '09:00:00',
				'rsvp_end_date'   => '2026-06-10',
				'rsvp_end_time'   => '17:00:00',
				'show_not_going'  => '1',
				'ticket_provider' => Module::class,
			],
			$post_id
		);

		$this->assertSame( 123, $data['ticket_id'] );
		$this->assertSame( 'RSVP', $data['ticket_name'] );
		$this->assertSame( Constants::TC_RSVP_TYPE, $data['ticket_type'] );
		$this->assertTrue( $data['show_not_going'] );
		$this->assertSame( Global_Stock::OWN_STOCK_MODE, $data['tribe-ticket']['mode'] );
		$this->assertSame( 75, $data['tribe-ticket']['capacity'] );
	}
}
