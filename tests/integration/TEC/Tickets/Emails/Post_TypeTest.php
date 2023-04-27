<?php

namespace TEC\Tickets\Emails;

class Post_TypeTest extends \Codeception\TestCase\WPTestCase {
	private $wp_rewrite_backup;

	public function setUp(): void {
		parent::setUp();
		$this->wp_rewrite_backup = $GLOBALS['wp_rewrite'];
	}

	public function tearDown() {
		$GLOBALS['wp_rewrite'] = $this->wp_rewrite_backup;
		parent::tearDown();
	}

	/**
	 * It should not attempt population of emails post type if rewrite not set up
	 *
	 * @test
	 */
	public function should_not_attempt_population_of_emails_post_type_if_rewrite_not_set_up(): void {
		global $wp_rewrite;
		unset( $wp_rewrite );

		$post_type = new Post_Type();
		$post_type->populate_email_template_posts();

		$emails = tribe( Email_Handler::class )->get_emails();

		foreach ( $emails as $email ) {
			$this->assertNull( $email->get_post() );
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
			$this->assertNotNull( $email->get_post() );
		}
	}
}
