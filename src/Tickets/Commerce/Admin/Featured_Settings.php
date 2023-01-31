<?php

namespace TEC\Tickets\Commerce\Admin;

use \Tribe__Template;

/**
 * Featured Settings for TC Payments Tab.
 *
 * @since 5.3.0
 *
 * @package TEC\Tickets\Commerce\Admin
 */
class Featured_Settings {

	/**
	 * Stores the instance of the template engine that we will use for rendering the elements.
	 *
	 * @since 5.3.0
	 *
	 * @var Tribe__Template
	 */
	protected $template;

	/**
	 * Gets the template instance used to setup the rendering html.
	 *
	 * @since 5.3.0
	 *
	 * @return Tribe__Template
	 */
	public function get_template() {
		if ( empty( $this->template ) ) {
			$this->template = new \Tribe__Template();
			$this->template->set_template_origin( \Tribe__Tickets__Main::instance() );
			$this->template->set_template_folder( 'src/admin-views/settings/featured' );
			$this->template->set_template_context_extract( true );
		}

		return $this->template;
	}

	/**
	 * Returns html of the featured settings block.
	 *
	 * @since 5.3.0
	 *
	 * @param array   $context Context of template.
	 * @param boolean $echo    Whether or not to output the HTML or just return it.
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

		return $template->template( 'container', wp_parse_args( $context, $defaults ), $echo );
	}
}
