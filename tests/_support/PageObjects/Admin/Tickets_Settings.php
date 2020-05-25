<?php

namespace Tribe\Tests\Tickets\PageObjects\Admin;

class Tickets_Settings {
	/**
	 * @var \AcceptanceTester;
	 */
	protected $I;

	public function __construct( \AcceptanceTester $I ) {
		$this->I = $I;
	}

	public function amOnTicketsSettings() {
		$this->I->amOnAdminPage("admin.php?page=tribe-common&tab=event-tickets");
	}

	public function setTicketablePostTypes( array $post_types ) {
		foreach ($post_types as $post_type) {
			$element = "#tribe-field-ticket-enabled-post-types input[type='checkbox'][value='$post_type']";
			$this->I->canSeeElementInDOM( $element );
			$this->I->checkOption( $element );
		}
	}

	public function save() {
		$this->I->click( "#tribeSaveSettings" );
	}
}