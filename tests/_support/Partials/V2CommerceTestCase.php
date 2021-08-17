<?php

namespace Tribe\Tickets\Test\Partials;

use Tribe\Tickets\Test\Partials\V2TestCase;

/**
 * Class V2CommerceTestCase for snapshot testing.
 * @package Tribe\Tickets\Test\Partials
 */
abstract class V2CommerceTestCase extends V2TestCase {

	public function setUp() {
		// before
		parent::setUp();

		if ( ! defined( 'TEC_TICKETS_COMMERCE' ) ) {
			define( 'TEC_TICKETS_COMMERCE', true );
		}

	}

	/**
	 * ET Template class instance.
	 *
	 * @return Tribe__Tickets__Editor__Template
	*/
	public function template_class() {
		$this->template = new \Tribe__Template();
		$this->template->set_template_origin( \Tribe__Tickets__Main::instance() );
		$this->template->set_template_folder( 'src/views/v2/commerce' );
		$this->template->set_template_context_extract( true );
		$this->template->set_template_folder_lookup( true );

		return $this->template;
	}

}
