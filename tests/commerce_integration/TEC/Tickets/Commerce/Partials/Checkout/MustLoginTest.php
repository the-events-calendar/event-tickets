<?php
namespace TEC\Tickets\Commerce\Partials\Checkout;

use Tribe\Tickets\Test\Testcases\TicketsCommerceSnapshotTestCase;

class MustLoginTest extends TicketsCommerceSnapshotTestCase {

	protected $partial_path = 'checkout/must-login';

	public function test_should_render_must_login() {
		$this->assertMatchesHtmlSnapshot( $this->get_partial_html( [
				'must_login'   => true,
				'items'        => [ 'Ticket 1', 'Ticket 2' ],
				'login_url'    => 'https://wordpress.test/wp-login.php',
				'register_url' => 'https://wordpress.test/wp-login.php?action=register',
			]
		) );
	}

	public function test_should_render_empty_if_must_login_false() {
		$this->assertEmpty( $this->get_partial_html( [
				'must_login'   => false,
				'items'        => [ 'Ticket 1', 'Ticket 2' ],
				'login_url'    => 'https://wordpress.test/wp-login.php',
				'register_url' => 'https://wordpress.test/wp-login.php?action=register',
			]
		) );
	}

	public function test_should_render_empty_if_no_tickets() {
		$this->assertEmpty( $this->get_partial_html( [
				'must_login'   => true,
				'items'        => [],
				'login_url'    => 'https://wordpress.test/wp-login.php',
				'register_url' => 'https://wordpress.test/wp-login.php?action=register',
			]
		) );
	}

	public function test_should_render_must_login_and_register() {
		update_option( 'users_can_register', true );

		$this->assertMatchesHtmlSnapshot( $this->get_partial_html( [
				'must_login'   => true,
				'items'        => [ 'Ticket 1', 'Ticket 2' ],
				'login_url'    => 'https://wordpress.test/wp-login.php',
				'register_url' => 'https://wordpress.test/wp-login.php?action=register',
			]
		) );
	}
}
