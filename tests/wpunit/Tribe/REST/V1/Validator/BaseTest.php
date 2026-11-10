<?php

namespace Tribe\Tickets\REST\V1\Validator;

use Codeception\TestCase\WPTestCase;
use Tribe__Tickets__REST__V1__Validator__Base as Validator;
use WP_REST_Request;

class BaseTest extends WPTestCase {

	/**
	 * @test
	 * @covers Tribe__Tickets__REST__V1__Validator__Base::should_see_ticket
	 */
	public function it_should_hide_tickets_of_a_draft_from_a_visitor(): void {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'draft' ] );
		wp_set_current_user( 0 );

		$this->assertFalse( $this->make_instance()->should_see_ticket( $post_id, new WP_REST_Request() ) );
	}

	/**
	 * @test
	 * @covers Tribe__Tickets__REST__V1__Validator__Base::should_see_ticket
	 */
	public function it_should_hide_tickets_of_a_draft_from_an_author_who_cannot_edit_it(): void {
		$owner   = $this->factory()->user->create( [ 'role' => 'author' ] );
		$post_id = $this->factory()->post->create( [ 'post_status' => 'draft', 'post_author' => $owner ] );
		wp_set_current_user( $this->factory()->user->create( [ 'role' => 'author' ] ) );

		$this->assertFalse( $this->make_instance()->should_see_ticket( $post_id, new WP_REST_Request() ) );
	}

	/**
	 * @test
	 * @covers Tribe__Tickets__REST__V1__Validator__Base::should_see_ticket
	 */
	public function it_should_show_tickets_of_a_draft_to_its_author(): void {
		$author  = $this->factory()->user->create( [ 'role' => 'author' ] );
		$post_id = $this->factory()->post->create( [ 'post_status' => 'draft', 'post_author' => $author ] );
		wp_set_current_user( $author );

		$this->assertTrue( $this->make_instance()->should_see_ticket( $post_id, new WP_REST_Request() ) );
	}

	/**
	 * @test
	 * @covers Tribe__Tickets__REST__V1__Validator__Base::should_see_ticket
	 */
	public function it_should_show_tickets_of_a_private_post_to_an_editor(): void {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'private' ] );
		wp_set_current_user( $this->factory()->user->create( [ 'role' => 'editor' ] ) );

		$this->assertTrue( $this->make_instance()->should_see_ticket( $post_id, new WP_REST_Request() ) );
	}

	/**
	 * @test
	 * @covers Tribe__Tickets__REST__V1__Validator__Base::should_see_ticket
	 */
	public function it_should_show_tickets_of_a_published_post_to_a_visitor(): void {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );
		wp_set_current_user( 0 );

		$this->assertTrue( $this->make_instance()->should_see_ticket( $post_id, new WP_REST_Request() ) );
	}

	/**
	 * @return Validator The validator under test.
	 */
	private function make_instance(): Validator {
		return new Validator();
	}
}
