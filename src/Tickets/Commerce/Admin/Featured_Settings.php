<?php

namespace TEC\Tickets\Commerce\Admin;

use \tad_DI52_ServiceProvider;
use TEC\Tickets\Commerce\Checkout;
use TEC\Tickets\Commerce\Success;
use \Tribe__Settings;
use \Tribe__Main;
use \Tribe__Template;

/**
 * Featured Settings for TC Payments Tab.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Admin
 */
class Featured_Settings {
    
    /**
	 * Stores the instance of the template engine that we will use for rendering the elements.
	 *
	 * @since TBD
	 *
	 * @var Tribe__Template
	 */
	protected $template;
    
    /**
	 * Gets the template instance used to setup the rendering html.
	 *
	 * @since TBD
	 *
	 * @return Tribe__Template
	 */
	public function get_template() {
		if ( empty( $this->template ) ) {
			$this->template = new \Tribe__Template();
			$this->template->set_template_origin( \Tribe__Tickets__Main::instance() );
			$this->template->set_template_folder( 'src/admin-views' );
			$this->template->set_template_context_extract( true );
			$this->template->set_template_folder_lookup( true );
		}

		return $this->template;
	}
    
    /**
	 * Returns html of the featured settings block.
	 *
	 * @since TBD
	 *
	 * @return Tribe__Template
	 */
    public function get_html( $context = [], $echo = false ) {
        $defaults = [
            'title'            => '',
            'description'      => '',
            'links'            => [],
            'content_template' => '',
            'classes'          => [],
        ];
        $template = $this->get_template();
        return $template->template( 'settings/featured', array_merge( $defaults, $context ), $echo );
    }
}