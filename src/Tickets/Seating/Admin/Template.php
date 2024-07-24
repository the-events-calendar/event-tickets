<?php
/**
 * A template class dedicated to the admin area.
 *
 * @since   TBD
 *
 * @package TEC\Controller\Admin;
 */

namespace TEC\Tickets\Seating\Admin;

use Tribe__Tickets__Main as Tickets;

/**
 * Class Template.
 *
 * @since   TBD
 *
 * @package TEC\Controller\Admin;
 */
class Template extends \Tribe__Template {
	/**
	 * Template constructor.
	 *
	 * @since TBD
	 */
	public function __construct() {
		$this->set_template_origin( tribe( Tickets::instance() ) );
		$this->set_template_folder( 'src/admin-views/seating' );
		$this->set_template_folder_lookup( false );
		$this->set_template_context_extract( true );
	}
}
