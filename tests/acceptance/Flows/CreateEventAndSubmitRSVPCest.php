<?php

use Tribe\Tests\Tickets\PageObjects\Admin\Tickets_Settings;
use Tribe\Tests\Tickets\PageObjects\Admin\Classic\TicketablePost;
use Tribe\Tests\Tickets\PageObjects\Frontend\Classic\View_Event;

class CreateEventAndSubmitRSVPCest {

	public function should_be_able_to_create_an_event_and_checkout(
		\AcceptanceTester $I,
		Tickets_Settings $tickets_settings,
		TicketablePost $ticketable_post,
		View_Event $view_event
	) {
		$I->loginAsAdmin();
		$tickets_settings->amOnTicketsSettings();
		$tickets_settings->setTicketablePostTypes( [ 'page' ] );
		$tickets_settings->save();

		$I->amOnPagesPage();
		$I->click(".page-title-action");
		$ticketable_post->setTitle( "My random event" );
		$ticketable_post->addNewRSVP(
			'My random RSVP',
			100,
			'My random description',
			new DateTime('now'),
			new DateTime('now + 1 day')
		);
		$ticketable_post->publish();
		$ticketable_post->goToPublished();

		$view_event->setRSVPQuantity( 1 );
		$view_event->sendRSVPConfirmationTo( "Foo Bar", "foo@bar.com", true );
		$view_event->clickConfirmRSVP();
		$view_event->seeRSVPHasBeenReceived();
	}

}
