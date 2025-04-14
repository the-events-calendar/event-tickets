<?php
/**
 * Template handler for the onboarding wizard.
 *
 * @since TBD
 */

namespace TEC\Tickets\Admin\Onboarding;

use Tribe__Template;
use Tribe__Tickets__Main as ET;

/**
 * Class Template
 *
 * @since TBD
 *
 * @package TEC\Tickets\Admin\Onboarding
 */
class Template extends Tribe__Template {
	/**
	 * Template constructor.
	 *
	 * Sets the correct paths for templates for event status.
	 *
	 * @since TBD
	 */
	public function __construct() {
		$this->set_template_origin( ET::class );
		$this->set_template_folder( 'src/Tickets/Admin/Onboarding/views/' );

		// We specifically don't want to look up template files here.
		$this->set_template_folder_lookup( false );

		// Configures this templating class extract variables.
		$this->set_template_context_extract( true );
	}
}
