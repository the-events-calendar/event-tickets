<?php
/**
 * Allow including of Event Tickets Wallet Plus Template.
 *
 * @since   1.0.0
 *
 * @pacakge TEC\Controller
 */

namespace TEC\Tickets\Seating;

/**
 * Class Template
 *
 * @since   1.0.0
 *
 * @package TEC\Tickets_Wallet_Plus
 */
class Template extends \Tribe__Template {

	/**
	 * Template constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->set_template_origin( tribe( Controller::class ) );
		$this->set_template_folder( 'src/views' );

		// Setup to look for theme files.
		$this->set_template_folder_lookup( true );

		// Configures this templating class extract variables.
		$this->set_template_context_extract( true );
	}
}
