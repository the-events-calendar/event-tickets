<?php
/**
 * Handles the templating for the Flexible Tickets, frontend side.
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Templates;
 */

namespace TEC\Tickets\Flexible_Tickets\Templates;

use Tribe__Template as Base_Template;
use Tribe__Tickets__Main as ET;

/**
 * Class Frontend_Views.
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Templates;
 */
class Frontend_Views extends Base_Template {
	/**
	 * Template constructor.
	 *
	 * Sets the correct paths for templates for event status.
	 *
	 * @since 5.8.0
	 */
	public function __construct() {
		$this->set_template_origin( ET::class );
		$this->set_template_folder( 'src/views/flexible-tickets' );

		// These templates should be overrideable by users.
		$this->set_template_folder_lookup( true );

		// Configures this templating class extract variables.
		$this->set_template_context_extract( true );
	}
}