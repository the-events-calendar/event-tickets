<?php

namespace TEC\Tickets\Commerce\Gateways;

class Abstract_Signup implements Signup_Interface {

	/**
	 * Holds the transient key used to store hash passed to PayPal.
	 *
	 * @since TBD moved to Abstract_Signup
	 * @since 5.1.9
	 *
	 * @var string
	 */
	public static $signup_hash_meta_key;

	/**
	 * Holds the transient key used to link PayPal to this site.
	 *
	 * @since TBD moved to Abstract_Signup
	 * @since 5.1.9
	 *
	 * @var string
	 */
	public static $signup_data_meta_key;

	/**
	 * Stores the instance of the template engine that we will use for rendering the page.
	 *
	 * @since TBD moved to Abstract_Signup
	 * @since 5.1.9
	 *
	 * @var \Tribe__Template
	 */
	protected $template;

	/**
	 * @inheritDoc
	 */
	public function get_template() {
		if ( empty( $this->template ) ) {
			$this->template = new \Tribe__Template();
			$this->template->set_template_origin( \Tribe__Tickets__Main::instance() );
			$this->template->set_template_folder( 'src/admin-views/settings/tickets-commerce/paypal' );
			$this->template->set_template_context_extract( true );
		}

		return $this->template;
	}

	/**
	 * @inheritDoc
	 */
	public function get_transient_data() {
		return get_transient( static::$signup_data_meta_key );
	}

	/**
	 * @inheritDoc
	 */
	public function update_transient_data( $value ) {
		return set_transient( static::$signup_data_meta_key, $value, DAY_IN_SECONDS );
	}

	/**
	 * @inheritDoc
	 */
	public function delete_transient_data() {
		return delete_transient( static::$signup_data_meta_key );
	}

	/**
	 * @inheritDoc
	 */
	public function get_link_html() {
		$template_vars = [
			'url'          => $this->generate_url(),
		];

		return $this->get_template()->template( 'signup-link', $template_vars, false );
	}

}