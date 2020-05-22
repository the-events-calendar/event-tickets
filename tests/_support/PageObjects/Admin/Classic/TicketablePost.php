<?php

namespace Tribe\Tests\Tickets\PageObjects\Admin\Classic;

use Tribe\Tickets\Test\Factories\PageObjectParams\RSVP;
use DateTime;

class TicketablePost {
	/**
	 * @var \AcceptanceTester;
	 */
	protected $I;

	public function __construct( \AcceptanceTester $I ) {
		$this->I = $I;
	}

	public function setTitle( string $title ) {
		$this->I->fillField( "#title", $title );
	}

	public function addNewRSVP(
		string $type,
		string $capacity,
		string $description,
		DateTime $start,
		DateTime $end
	) {
		$this->I->waitForElement( "#rsvp_form_toggle" );
		$this->I->click( "#rsvp_form_toggle" );
		$this->I->click( "#tribe_panel_edit .accordion .accordion-header" );
		$this->I->fillField( "#ticket_name", $type );
		$this->I->fillField( "#Tribe__Tickets__RSVP_capacity", $capacity );
		$this->I->fillField( "#ticket_description", $description );

		$this->I->fillField( "#ticket_start_date", $start->format("m/d/Y") );
		$this->I->fillField( "#ticket_start_time", $start->format("h:ia") );

		$this->I->fillField( "#ticket_end_date", $end->format("m/d/Y") );
		$this->I->fillField( "#ticket_end_time", $end->format("h:ia") );

		$this->I->click( '#rsvp_form_save' );
		$this->I->waitForJqueryAjax();
	}

	public function publish() {
		$this->I->click( "#publish" );
		$this->I->waitForJqueryAjax();
	}

	public function goToPublished() {
		$this->I->click( "#sample-permalink a" );
	}
}