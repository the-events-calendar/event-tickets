<?php

namespace Tribe\Tests\Tickets\PageObjects\Frontend\Classic;

class View_Event {
	/**
	 * @var \AcceptanceTester;
	 */
	protected $I;

	public function __construct( \AcceptanceTester $I ) {
		$this->I = $I;
	}

	public function setRSVPQuantity( int $quantity ) {
		$this->I->fillField( '.tribe-tickets-quantity', $quantity );
	}

	public function sendRSVPConfirmationTo( string $name, string $email, bool $is_going ) {
		$this->I->fillField( '#tribe-tickets-full-name', $name );
		$this->I->fillField( '#tribe-tickets-email', $email );
		$this->I->selectOption( 'select[name="attendee[order_status]"]', $is_going ? 'yes' : 'no' );
	}

	public function clickConfirmRSVP() {
		$this->I->click( 'button[type="submit"][name="tickets_process"]' );
		$this->I->waitForJqueryAjax();
	}

	public function seeRSVPHasBeenReceived() {
		$this->I->canSeeInCurrentUrl( 'rsvp_sent=1' );
		$this->I->see( 'Your RSVP has been received!', '.tribe-rsvp-message-success'  );
	}
}