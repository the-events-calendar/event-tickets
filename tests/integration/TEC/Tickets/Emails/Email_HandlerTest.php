<?php

namespace TEC\Tickets\Emails;

use TEC\Common\Monolog\Logger;
use Tribe\Tests\Traits\With_Log_Recording;
use Tribe\Tests\Traits\With_Uopz;

class Email_HandlerTest extends \Codeception\TestCase\WPTestCase {
	use With_Uopz;
	use With_Log_Recording;

	private $wp_rewrite_backup;

	public function setUp(): void {
		parent::setUp();
		$this->wp_rewrite_backup = $GLOBALS['wp_rewrite'];
		$this->log_recorder_start();
		$this->log_recorder_reset();
	}

	public function tearDown() {
		$GLOBALS['wp_rewrite'] = $this->wp_rewrite_backup;
		$this->log_recorder_stop();
		$this->log_recorder_reset();
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

		$handler = new Email_Handler( tribe() );

		$this->assertEquals( 0, $handler->maybe_populate_tec_tickets_emails_post_type() );
	}

	/**
	 * It should create tickets emails posts correctly
	 *
	 * @test
	 */
	public function should_create_tickets_emails_posts_correctly(): void {
		$handler = new Email_Handler( tribe() );

		// Filter the emails to return the default ones.
		$defaul_emails = $handler->get_default_emails();
		add_filter( 'tec_ticketk_emails_registered_emails', static fn() => $defaul_emails );

		$this->assertEquals( count( $defaul_emails ), $handler->maybe_populate_tec_tickets_emails_post_type() );
	}

	/**
	 * It should report 0 if there are no emails to create
	 *
	 * @test
	 */
	public function should_report_0_if_there_are_no_emails_to_create(): void {
		$handler = new Email_Handler( tribe() );

		// Filter the emails to return an empty array: no email to create.
		$defaul_emails = $handler->get_default_emails();
		add_filter( 'tec_ticketk_emails_registered_emails', '__return_empty_array' );

		$this->assertEquals( 0, $handler->maybe_populate_tec_tickets_emails_post_type() );
	}

	/**
	 * It should report correct count and log if email cannot be created
	 *
	 * @test
	 */
	public function should_report_correct_count_and_log_if_email_cannot_be_created(): void {
		// Fail the insertion of the 3rd email.
		$calls = 0;
		$this->set_fn_return( 'wp_insert_post', static function ( array $args ) use ( &$calls ) {
			if ( $calls ++ === 3 ) {
				return new \WP_Error( 'For reasons' );
			}

			return wp_insert_post( $args );
		}, true );
		$handler = new Email_Handler( tribe() );

		// Filter the emails to return an empty array: no email to create.
		$defaul_emails = $handler->get_default_emails();
		add_filter( 'tec_ticketk_emails_registered_emails', static fn() => $defaul_emails );

		$this->assertEquals( count( $defaul_emails ) - 1, $handler->maybe_populate_tec_tickets_emails_post_type() );
		$this->assertCount( 1, $this->get_log_records() );
		$log_record = $this->get_log_record( 0 );
		$this->assertEquals( Logger::ERROR, $log_record['level'] );
		$this->assertEquals( 'Error creating email post.', $log_record['message'] );
	}
}
