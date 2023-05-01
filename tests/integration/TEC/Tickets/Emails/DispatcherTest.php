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
		$this->assertFalse( $dispatcher->can_send(), 'Cannot send email when the dispatcher was not configured.' );
		$this->assertFalse( $dispatcher->send(), 'Email not sent email when the dispatcher was not configured.' );
		$this->assertFalse( $dispatcher->was_used(), 'Dispatcher should not be marked as used if it fail the can_send() check.' );
	}

	/**
	 * @test
	 */
	public function should_still_mark_as_used_when_wp_mail_fails(): void {
		$email = new Dummy_Email;
		$dispatcher = Dispatcher::from_email( $email );
		$dispatcher->set_to( 'mock@example.com' );
		$dispatcher->set_subject( 'Mock Subject' );
		$dispatcher->set_content( 'Mock Content' );

		add_filter( 'pre_wp_mail', '__return_false' );

		$this->assertTrue( $dispatcher->can_send(), 'Can send email when the dispatcher was configured.' );
		$this->assertFalse( $dispatcher->send(), 'Email not sent when we are filtering `pre_wp_mail`.' );
		$this->assertTrue( $dispatcher->was_used(), 'Dispatcher should be marked as used if it fail to wp_mail().' );

		remove_filter( 'pre_wp_mail', '__return_false' );
	}

	/**
	 * @test
	 */
	public function should_after_marked_as_used_email_cannot_be_send(): void {
		$email = new Dummy_Email;
		$dispatcher = Dispatcher::from_email( $email );
		$dispatcher->set_to( 'mock@example.com' );
		$dispatcher->set_subject( 'Mock Subject' );
		$dispatcher->set_content( 'Mock Content' );

		add_filter( 'pre_wp_mail', '__return_false' );

		$this->assertTrue( $dispatcher->can_send(), 'Can send email when the dispatcher was configured.' );
		$this->assertFalse( $dispatcher->send(), 'Email not sent when we are filtering `pre_wp_mail`.' );
		$this->assertTrue( $dispatcher->was_used(), 'Dispatcher should be marked as used if it fail to wp_mail().' );

		remove_filter( 'pre_wp_mail', '__return_false' );
		add_filter( 'pre_wp_mail', '__return_true' );

		$this->assertFalse( $dispatcher->can_send(), 'Dispatcher should not be able to send once marked as used.' );
		$this->assertFalse( $dispatcher->send(), 'Dispatcher should not have sent once it was marked as used.' );

		remove_filter( 'pre_wp_mail', '__return_true' );
	}

	/**
	 * @test
	 */
	public function should_not_allow_sending_twice(): void {
		$email = new Dummy_Email;
		$dispatcher = Dispatcher::from_email( $email );
		$dispatcher->set_to( 'mock@example.com' );
		$dispatcher->set_subject( 'Mock Subject' );
		$dispatcher->set_content( 'Mock Content' );

		add_filter( 'pre_wp_mail', '__return_true' );

		$this->assertTrue( $dispatcher->can_send(), 'Can send email when the dispatcher was configured.' );
		$this->assertTrue( $dispatcher->send(), 'Email sent when we are filtering `pre_wp_mail`.' );
		$this->assertTrue( $dispatcher->was_used(), 'Dispatcher should be marked as used.' );


		$this->assertFalse( $dispatcher->can_send(), 'Dispatcher should not be able to send once marked as used.' );
		$this->assertFalse( $dispatcher->send(), 'Dispatcher should not have sent once it was marked as used.' );

		remove_filter( 'pre_wp_mail', '__return_true' );
	}

	/**
	 * @test
	 */
	public function should_allow_sending_twice_once_dangerously_marked_as_not_used(): void {
		$email = new Dummy_Email;
		$dispatcher = Dispatcher::from_email( $email );
		$dispatcher->set_to( 'mock@example.com' );
		$dispatcher->set_subject( 'Mock Subject' );
		$dispatcher->set_content( 'Mock Content' );

		add_filter( 'pre_wp_mail', '__return_true' );

		$this->assertTrue( $dispatcher->can_send(), 'Can send email when the dispatcher was configured.' );
		$this->assertTrue( $dispatcher->send(), 'Email sent when we are filtering `pre_wp_mail`.' );
		$this->assertTrue( $dispatcher->was_used(), 'Dispatcher should be marked as used.' );

		$dispatcher->dangerously_mark_as_not_used();
		$this->assertFalse( $dispatcher->was_used(), 'Dispatcher should not be marked as used after reset.' );

		$this->assertTrue( $dispatcher->can_send(), 'Can send email when the dispatcher was configured.' );
		$this->assertTrue( $dispatcher->send(), 'Email sent when we are filtering `pre_wp_mail`.' );
		$this->assertTrue( $dispatcher->was_used(), 'Dispatcher should be marked as used.' );

		remove_filter( 'pre_wp_mail', '__return_true' );
	}
}
