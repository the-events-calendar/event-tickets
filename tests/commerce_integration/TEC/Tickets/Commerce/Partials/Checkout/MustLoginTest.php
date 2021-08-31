<?php
namespace TEC\Tickets\Commerce\Partials\Checkout;

use Tribe\Tickets\Test\Partials\V2CommerceTestCase;

class MustLoginTest extends V2CommerceTestCase {

	public $partial_path = 'checkout/must-login';

	/**
	 * Get all the default args required for this template
	 *
	 * @return array
	 */
	public function get_default_args() {
		$args = [
			'must_login'   => true,
			'items'        => [ 'Ticket 1', 'Ticket 2' ],
			'login_url'    => 'https://wordpress.test/wp-login.php',
			'register_url' => 'https://wordpress.test/wp-login.php?action=register',
		];

		return $args;
	}

	/**
	 * @test
	 */
	public function test_should_render_must_login() {
		$args   = $this->get_default_args();
		$html   = $this->template_class()->template( $this->partial_path, $args, false );
		$driver = $this->get_html_output_driver();

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * @test
	 */
	public function test_should_render_empty_if_must_login_false() {
		$args   = $this->get_default_args();
		$args['must_login'] = false;
		$html   = $this->template_class()->template( $this->partial_path, $args, false );
		$driver = $this->get_html_output_driver();

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * @test
	 */
	public function test_should_render_empty_if_no_tickets() {
		$args   = $this->get_default_args();
		$args['items'] = [];
		$html   = $this->template_class()->template( $this->partial_path, $args, false );
		$driver = $this->get_html_output_driver();

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * @test
	 */
	public function test_should_render_must_login_and_register() {
		update_option( 'users_can_register', true );
		$args   = $this->get_default_args();
		$html   = $this->template_class()->template( $this->partial_path, $args, false );
		$driver = $this->get_html_output_driver();

		$this->assertMatchesSnapshot( $html, $driver );
	}
}
