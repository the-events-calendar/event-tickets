<?php
/**
 * Tickets Emails Handler.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails;

use \tad_DI52_ServiceProvider;

/**
 * Class Email_Handler.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Emails
 */
class Email_Handler extends tad_DI52_ServiceProvider {

	/**
	 * Event Tickets Emails post type.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const POSTTYPE = 'tec_tickets_emails';

	/**
	 * Registered emails.
	 *
	 * @since TBD
	 *
	 * @var Flag_Action_Interface[] // @todo @juanfra: update docblock.
	 */
	protected $emails = [];

	/**
	 * Emails
	 *
	 * @since TBD
	 *
	 * @var string[]
	 */
	protected $default_emails = [
		\TEC\Tickets\Emails\Email\Ticket::class,
	];

	/**
	 * Binds and sets up implementations.
	 *
	 * @since TBD
	 */
	public function register() {
		foreach ( $this->default_emails as $email_class ) {
			// Spawn the new instance.
			$email = new $email_class;

			var_dump( $email );

			// Register as a singleton for internal ease of use.
			$this->container->singleton( $email_class, $email );

			// Collect this particular status instance in this class.
			$this->register_email( $email );
		}

		$this->container->singleton( static::class, $this );
	}

	/**
	 * Register a given email into the Handler, and hook the handling to WP.
	 *
	 * @since TBD
	 *
	 * @param Email_Abstract $email Which email we are registering.
	 */
	public function register_email( Email_Abstract $email ) {
		$this->emails[] = $email;
		$email->hook();
	}

	/**
	 * Gets the emails registered.
	 *
	 * @since TBD
	 *
	 * @return Email_Abstract[]
	 */
	public function get_all() {
		return $this->emails;
	}

	/**
	 * Register post type.
	 *
	 * @since TBD
	 */
	public function register_post_type() {
		$post_type_args = [
			'label'           => __( 'Event Tickets Emails', 'event-tickets' ),
			'public'          => false,
			'show_ui'         => false,
			'show_in_menu'    => false,
			'query_var'       => false,
			'rewrite'         => false,
			'capability_type' => 'post',
			'has_archive'     => false,
			'hierarchical'    => false,
		];

		/**
		 * Filter the arguments that craft the order post type.
		 *
		 * @see   register_post_type
		 * @since TBD
		 *
		 * @param array $post_type_args Post type arguments, passed to register_post_type()
		 */
		$post_type_args = apply_filters( 'tec_tickets_emails_post_type_args', $post_type_args );

		register_post_type( static::POSTTYPE, $post_type_args );
	}

	/**
	 * Create system emails.
	 *
	 * @return void
	 */
	public function create_emails() {

		// $emails = apply_filters();
		// @todo @codingmusician: create posts for static::POSTTYPE.
	}


}
