<?php

namespace TEC\Tickets\Flexible_Tickets\Series_Passes;

use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Emails\Admin\Preview_Modal;
use TEC\Tickets\Emails\Email_Handler;
use TEC\Tickets\Flexible_Tickets\Series_Passes\Emails\Series_Pass;

class EmailsTest extends Controller_Test_Case {
	use SnapshotAssertions;

	protected string $controller_class = Emails::class;

	/**
	 * It should register the Series Pass email template among available templates
	 *
	 * @test
	 */
	public function should_register_the_series_pass_email_template_among_available_templates(): void {
		$email_handler = tribe( Email_Handler::class );
		$this->assertNotContains( Series_Pass::class, $email_handler->get_emails() );

		$this->make_controller()->register();

		$this->assertContains( Series_Pass::class, $email_handler->get_emails() );
	}

	public function test_preview_series_pass_email(): void {
		$this->make_controller()->register();

		$id = tribe( Series_Pass::class )->id;
		$preview_modal = tribe( Preview_Modal::class );
		$modal_content = $preview_modal->get_modal_content_ajax( '', [ 'currentEmail' => $id ] );

		$this->assertMatchesHtmlSnapshot( $modal_content );
	}
}
