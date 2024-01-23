<?php
/**
 * Handles the templating for the Flexible Tickets, administration side.
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets\Templates\Flexible_Tickets;
 */

namespace TEC\Tickets\Flexible_Tickets\Templates;

use Tribe__Template as Base_Template;
use Tribe__Tickets__Main as ET;

/**
 * Class Admin_Templates.
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */
class Admin_Views extends Base_Template {
	/**
	 * Template constructor.
	 *
	 * Sets the correct paths for templates for event status.
	 *
	 * @since 5.8.0
	 */
	public function __construct() {
		$this->set_template_origin( ET::class );
		$this->set_template_folder( 'src/admin-views/flexible-tickets' );

		// We specifically don't want to look up template files here.
		$this->set_template_folder_lookup( false );

		// Configures this templating class extract variables.
		$this->set_template_context_extract( true );
	}
}