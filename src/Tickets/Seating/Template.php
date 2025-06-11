<?php
/**
 * Allow including of Event Tickets Wallet Plus Template.
 *
 * @since 1.0.0
 *
 * @pacakge TEC\Controller
 */

namespace TEC\Tickets\Seating;

use Tribe__Template as Base_Template;
use Tribe__Tickets__Main as Tickets;

/**
 * Class Template
 *
 * @since 1.0.0
 *
 * @package TEC\Tickets_Wallet_Plus
 */
class Template extends Base_Template {
	/**
	 * Template constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->set_template_origin( tribe( Tickets::instance() ) );
		$this->set_template_folder( 'src/views/seating' );

		// Setup to look for theme files.
		$this->set_template_folder_lookup( true );

		// Configures this templating class extract variables.
		$this->set_template_context_extract( true );
	}
}
