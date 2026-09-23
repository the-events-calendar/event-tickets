<?php

namespace TEC\Tickets\Deferred_Save\Classic;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Deferred_Save\Result;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;

class Notices_Test extends WPTestCase {
	use Ticket_Maker;
	use With_Tickets_Commerce;

	public function tearDown(): void {
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	protected function render_admin_notices(): string {
		set_current_screen( 'post' );
		ob_start();
		do_action( 'admin_notices' );

		return (string) ob_get_clean();
	}

	/**
	 * @test
	 */
	public function it_remembers_the_errors_for_the_user_and_shows_them_once_on_the_next_edit_screen(): void {
		$user_id = static::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );
		$post_id   = static::factory()->post->create( [ 'post_title' => 'The saved post' ] );
		$ticket_id = $this->create_tc_ticket( $post_id, 10, [ 'ticket_name' => 'Early bird' ] );
		$result    = ( new Result() )
			->with_error( 'update', $ticket_id, 'Ticket could not be saved.' )
			->with_error( 'create', 1, 'The ticket provider is missing or not active.' );

		do_action( 'tec_tickets_deferred_save_classic_committed', $result, $post_id );

		$html = $this->render_admin_notices();
		$this->assertStringContainsString( 'notice-error', $html );
		$this->assertStringContainsString( 'Early bird', $html );
		$this->assertStringContainsString( 'Ticket could not be saved.', $html );
		$this->assertStringContainsString( 'The ticket provider is missing or not active.', $html );
		$this->assertStringContainsString( 'The saved post', $html );
		$this->assertRegExp( '/[Nn]ew ticket.*2/', $html, 'A create error is named by its 1-based position.' );

		$this->assertSame( '', $this->render_admin_notices(), 'The notice shows once.' );
	}

	/**
	 * @test
	 */
	public function it_stores_nothing_for_a_result_without_errors(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$post_id = static::factory()->post->create();

		do_action( 'tec_tickets_deferred_save_classic_committed', ( new Result() )->with_created( 0, 123 ), $post_id );

		$this->assertSame( '', $this->render_admin_notices() );
	}

	/**
	 * @test
	 */
	public function the_notice_belongs_to_the_user_who_saved(): void {
		$saver  = static::factory()->user->create( [ 'role' => 'administrator' ] );
		$other  = static::factory()->user->create( [ 'role' => 'administrator' ] );
		$post_id = static::factory()->post->create();
		wp_set_current_user( $saver );
		do_action( 'tec_tickets_deferred_save_classic_committed', ( new Result() )->with_error( null, null, 'You are not allowed to edit the tickets of this post.' ), $post_id );

		wp_set_current_user( $other );
		$this->assertSame( '', $this->render_admin_notices() );

		wp_set_current_user( $saver );
		$this->assertStringContainsString( 'You are not allowed to edit the tickets of this post.', $this->render_admin_notices() );
	}

	/**
	 * @test
	 */
	public function it_never_names_a_post_the_payload_pointed_at_that_is_not_a_ticket_on_the_saved_post(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$post_id        = static::factory()->post->create();
		$private_id     = static::factory()->post->create( [ 'post_status' => 'private', 'post_title' => 'Secret launch plan' ] );
		$other_post_id  = static::factory()->post->create();
		$foreign_ticket = $this->create_tc_ticket( $other_post_id, 10, [ 'ticket_name' => 'Foreign ticket name' ] );
		$result         = ( new Result() )
			->with_error( 'delete', $private_id, 'Ticket does not belong to this post.' )
			->with_error( 'update', $foreign_ticket, 'Ticket does not belong to this post.' );

		do_action( 'tec_tickets_deferred_save_classic_committed', $result, $post_id );
		$html = $this->render_admin_notices();

		$this->assertStringNotContainsString( 'Secret launch plan', $html );
		$this->assertStringNotContainsString( 'Foreign ticket name', $html );
		$this->assertStringContainsString( 'Ticket ' . $private_id, $html );
		$this->assertStringContainsString( 'Ticket ' . $foreign_ticket, $html );
	}

	/**
	 * @test
	 */
	public function output_is_escaped(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$post_id = static::factory()->post->create( [ 'post_title' => 'Post <script>alert(1)</script>' ] );

		do_action( 'tec_tickets_deferred_save_classic_committed', ( new Result() )->with_error( 'create', 0, '<img src=x onerror=alert(1)>' ), $post_id );

		$html = $this->render_admin_notices();
		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringNotContainsString( '<img', $html );
	}
}
