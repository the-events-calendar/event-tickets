<?php

namespace TEC\Tickets\Emails;
use Tribe\Tests\Traits\With_Uopz;

class Post_TypeTest extends \Codeception\TestCase\WPTestCase {
	use With_Uopz;

	/**
	 * It should not attempt population of emails post type if rewrite not set up
	 *
	 * @test
	 */
	public function should_not_attempt_population_of_emails_post_type_if_rewrite_not_set_up(): void {
		// Mark did action as false so that it prevents from creating the new posts.
		$this->set_fn_return( 'did_action', false );

		$post_type = new Post_Type();
		$post_type->populate_email_template_posts();

		$emails = tribe( Email_Handler::class )->get_emails();

		foreach ( $emails as $email ) {
			$this->assertNull( $email->get_post(), 'Should not have created a Post for this Email.' );
		}
	}

	/**
	 * It should create tickets emails posts correctly
	 *
	 * @test
	 */
	public function should_create_tickets_emails_posts_correctly(): void {
		$post_type = new Post_Type();
		$post_type->populate_email_template_posts();

		$emails = tribe( Email_Handler::class )->get_emails();

		foreach ( $emails as $email ) {
			$this->assertNotNull( $email->get_post(), 'Should have created a Post for this Email.' );
		}
	}
}
