<?php
/**
 * Tickets Emails Handler.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails;

/**
 * Class Email_Handler.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Emails
 */
class Email_Handler extends \tad_DI52_ServiceProvider {

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
	 * @var Email_Abstract[]
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
		foreach ( $this->get_emails() as $email_class ) {
			// Spawn the new instance.
			$email = new $email_class;

			// Register as a singleton for internal ease of use.
			$this->container->singleton( $email_class, $email );

			// Collect this particular status instance in this class.
			$this->register_email( $email );
		}

		$this->maybe_populate_tec_tickets_emails_post_type();

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
	 * Gets the registered emails.
	 *
	 * @since TBD
	 *
	 * @return Email_Abstract[]
	 */
	public function get_emails() {
		$emails = array_merge( $this->emails, $this->get_default_emails() );
		/**
		 * Filter the array of email classes that will be used.
		 *
		 * @since TBD
		 *
		 * @param array $emails Array of email classes.
		 */
		return apply_filters( 'tec_tickets_emails_registered_emails', $emails );
	}

	/**
	 * Gets the default emails.
	 *
	 * @since TBD
	 *
	 * @return Email_Abstract[]
	 */
	public function get_default_emails() {
		/**
		 * Filter the array of default emails.
		 *
		 * @since TBD
		 *
		 * @param array $emails Array of default email classes.
		 */
		return apply_filters( 'tec_tickets_emails_default_emails', $this->default_emails );
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
	 * Populate the Tickets Emails post type with the system emails.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function maybe_populate_tec_tickets_emails_post_type() {
		$emails = $this->get_emails();

		// iterate on emails, check if exists by slug and create if not.
		foreach ( $emails as $email_class ) {
			$email = tribe( $email_class );
			if ( empty( $email->get_post() ) ) {
				$this->create_tec_tickets_emails_post_type( $email );
			}
		}
	}

	/**
	 * Create system email.
	 *
	 * @since TBD
	 *
	 * @param Email_Abstract $email The email.
	 *
	 * @return void
	 */
	public function create_tec_tickets_emails_post_type( $email ) {
		$args = [
			'post_name'   => $email->id,
			'post_title'  => $email->get_title(),
			'post_status' => 'publish',
			'post_type'   => static::POSTTYPE,
			'meta_input'  => [
				'email_recipient' => $email->recipient,
				'email_template'  => $email->template,
				'email_version'   => $email->version,
			],
		];
		wp_insert_post( $args );
	}

	/**
	 * Add per email setting fields.
	 *
	 * @param array $fields The fields.
	 *
	 * @return array $fields
	 */
	public function add_settings_per_email( array $fields ): array {
		if ( ! tribe( Admin\Emails_Tab::class )->is_on_tab() ) {
			return $fields;
		}

		$emails = $this->get_emails();

		foreach ( $emails as $email ) {
			// if ( ! tribe( Admin\Emails_Tab::class )->is_on_section( $email->id ) ) { // @todo @codingmusician: We need to implement the section logic for emails tab.
			//	continue;
			// }
			// $fields = array_merge( $fields, $email->get_settings() );
		}

		return $fields;
	}

}
