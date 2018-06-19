<?php

namespace Tribe\Tickets\Test\REST\V1;

use Restv1Tester;

class BaseRestCest {

	/**
	 * @var string
	 */
	protected $rest_disable_option = 'et-rest-v1-disabled';
	/**
	 * @var string The site full URL to the homepage.
	 */
	protected $site_url;
	/**
	 * @var string
	 */
	protected $tec_option = 'tribe_events_calendar_options';

	/**
	 * @var string The site full URL to the REST API root.
	 */
	protected $rest_url;

	/**
	 * @var string
	 */
	protected $tickets_url;

	/**
	 * @var string
	 */
	protected $attendees_url;

	/**
	 * @var string
	 */
	protected $documentation_url;

	/**
	 * @var \tad\WPBrowser\Module\WPLoader\FactoryStore
	 */
	protected $factory;

	public function _before( Restv1Tester $I ) {
		$this->site_url          = $I->grabSiteUrl();
		$this->rest_url          = $this->site_url . '/wp-json/tribe/tickets/v1/';
		$this->tickets_url       = $this->rest_url . 'tickets';
		$this->attendees_url       = $this->rest_url . 'attendees';
		$this->documentation_url = $this->rest_url . 'doc';
		$this->factory = $I->factory();

		wp_cache_flush();

		/** @var \Tribe__Tickets__REST__V1__Post_Repository $repository */
		$repository = tribe( 'tickets.rest-v1.repository' );
		$repository->reset_ticket_cache();
	}
}