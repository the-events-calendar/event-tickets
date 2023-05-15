<?php

namespace TEC\Tickets\Emails;

use TEC\Tickets\Tests\Emails\Email\Dummy_Email;

/**
 * Class DispatcherTest
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Emails
 */
class DispatcherTest extends \Codeception\TestCase\WPTestCase {
	/**
	 * @test
	 */
	public function should_not_send_when_not_configured(): void {
		$dispatcher = new Dispatcher();
		$this->assertFalse( $dispatcher->send(), 'Email not sent email when the dispatcher was not configured.' );
	}

	/**
	 * @test
	 */
	public function should_still_mark_as_used_when_wp_mail_fails(): void {
		$email = new Dummy_Email;
		$dispatcher = Dispatcher::from_email( $email );

		add_filter( 'pre_wp_mail', '__return_false' );

		$this->assertFalse( $dispatcher->send(), 'Email not sent when we are filtering `pre_wp_mail`.' );

		remove_filter( 'pre_wp_mail', '__return_false' );
	}

	/**
	 * @test
	 */
	public function should_after_marked_as_used_email_cannot_be_send(): void {
		$email = new Dummy_Email;
		$dispatcher = Dispatcher::from_email( $email );

		add_filter( 'pre_wp_mail', '__return_false' );

		$this->assertFalse( $dispatcher->send(), 'Email not sent when we are filtering `pre_wp_mail`.' );

		remove_filter( 'pre_wp_mail', '__return_false' );
		add_filter( 'pre_wp_mail', '__return_true' );

		$this->assertFalse( $dispatcher->send(), 'Dispatcher should not have sent once it was marked as used.' );

		remove_filter( 'pre_wp_mail', '__return_true' );
	}

	/**
	 * @test
	 */
	public function should_not_allow_sending_twice(): void {
		$email = new Dummy_Email;
		$dispatcher = Dispatcher::from_email( $email );

		add_filter( 'pre_wp_mail', '__return_true' );

		$this->assertTrue( $dispatcher->send(), 'Email sent when we are filtering `pre_wp_mail`.' );

		$this->assertFalse( $dispatcher->send(), 'Dispatcher should not have sent once it was marked as used.' );

		remove_filter( 'pre_wp_mail', '__return_true' );
	}

	/**
	 * @test
	 */
	public function should_allow_sending_twice_once_dangerously_marked_as_not_used(): void {
		$email = new Dummy_Email;
		$dispatcher = Dispatcher::from_email( $email );

		add_filter( 'pre_wp_mail', '__return_true' );

		$this->assertTrue( $dispatcher->send(), 'Email sent when we are filtering `pre_wp_mail`.' );

		$dispatcher->dangerously_mark_as_not_used();

		$this->assertTrue( $dispatcher->send(), 'Email sent when we are filtering `pre_wp_mail`.' );

		remove_filter( 'pre_wp_mail', '__return_true' );
	}
}
