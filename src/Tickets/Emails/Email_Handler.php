<?php
/**
 * Tickets Emails Handler.
 *
 * @since 5.5.9
 *
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails;

use Tribe__Tickets__Main;
use WP_Error;

/**
 * Class Email_Handler.
 *
 * @since 5.5.9
 *
 * @package TEC\Tickets\Emails
 */
class Email_Handler extends \tad_DI52_ServiceProvider {

	/**
	 * Event Tickets Emails post type.
	 *
	 * @since 5.5.9
	 *
	 * @var string
	 */
	const POSTTYPE = 'tec_tickets_emails';

	/**
	 * Registered emails.
	 *
	 * @since 5.5.9
	 *
	 * @var Email_Abstract[]
	 */
	protected $emails = [];

	/**
	 * Emails
	 *
	 * @since 5.5.9
	 *
	 * @var string[]
	 */
	protected $default_emails = [
		\TEC\Tickets\Emails\Email\Ticket::class,
		\TEC\Tickets\Emails\Email\RSVP::class,
		\TEC\Tickets\Emails\Email\RSVP_Not_Going::class,
		\TEC\Tickets\Emails\Email\Purchase_Receipt::class,
		\TEC\Tickets\Emails\Email\Failed_Order::class,
		\TEC\Tickets\Emails\Email\Completed_Order::class,
	];

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 5.5.9
	 */
	public function register() {
		foreach ( $this->get_default_emails() as $email_class ) {
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
	 * @since 5.5.9
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
	 * @since 5.5.9
	 *
	 * @return Email_Abstract[]
	 */
	public function get_emails() {
		/**
		 * Filter the array of email classes that will be used.
		 *
		 * @since 5.5.9
		 *
		 * @param array $emails Array of email classes.
		 */
		return apply_filters( 'tec_tickets_emails_registered_emails', $this->emails );
	}

	/**
	 * Gets the default emails.
	 *
	 * @since 5.5.9
	 *
	 * @return Email_Abstract[]
	 */
	public function get_default_emails() {
		/**
		 * Filter the array of default emails.
		 *
		 * @since 5.5.9
		 *
		 * @param array $emails Array of default email classes.
		 */
		return apply_filters( 'tec_tickets_emails_default_emails', $this->default_emails );
	}

	/**
	 * Register post type.
	 *
	 * @since 5.5.9
	 */
	public function register_post_type() {
		$post_type_args = [
			'label'           => __( 'Event Tickets Emails', 'event-tickets' ),
			'public'          => false,
			'show_ui'         => false,
			'show_in_menu'    => false,
			'query_var'       => false,
			'rewrite'         => false,
			'capability_type' => 'page',
			'has_archive'     => false,
			'hierarchical'    => false,
		];

		/**
		 * Filter the arguments that craft the order post type.
		 *
		 * @see   register_post_type
		 * @since 5.5.9
		 *
		 * @param array $post_type_args Post type arguments, passed to register_post_type()
		 */
		$post_type_args = apply_filters( 'tec_tickets_emails_post_type_args', $post_type_args );

		register_post_type( static::POSTTYPE, $post_type_args );
	}

	/**
	 * Populate the Tickets Emails post type with the system emails.
	 *
	 * @since 5.5.9
	 *
	 * @return int The number of emails created.
	 */
	public function maybe_populate_tec_tickets_emails_post_type(): int {
		global $wp_rewrite;

		if ( ! ( isset( $wp_rewrite ) && $wp_rewrite instanceof \WP_Rewrite ) ) {
			// The global rewrite object is not available, bail. It's required to create the post type.
			return false;
		}

		$emails  = $this->get_emails();
		$created = 0;

		// iterate on emails, check if exists by slug and create if not.
		foreach ( $emails as $email_class ) {
			$email = tribe( $email_class );
			if ( empty( $email->get_post() ) ) {
				$created += $this->create_tec_tickets_emails_post_type( $email );
			}
		}

		return $created;
	}

	/**
	 * Create system email.
	 *
	 * @since 5.5.9
	 *
	 * @param Email_Abstract $email The email.
	 *
	 * @return int The number of emails created.
	 */
	public function create_tec_tickets_emails_post_type( $email ): int {
		$args     = [
			'post_name'   => $email->get_id(),
			'post_title'  => $email->get_title(),
			'post_status' => 'publish',
			'post_type'   => static::POSTTYPE,
			'meta_input'  => [
				'email_to'       => $email->to,
				'email_template' => $email->template,
				'email_version'  => Tribe__Tickets__Main::VERSION,
			],
		];
		$inserted = wp_insert_post( $args );

		if ( $inserted instanceof WP_Error ) {
			do_action( 'tribe_log', 'error', 'Error creating email post.', [
				'class'      => __CLASS__,
				'post_title' => $email->get_title(),
				'error'      => $inserted->get_error_message(),
			] );
		}

		return $inserted instanceof WP_Error ? 0 : 1;
	}

	/**
	 * Get email by ID.
	 *
	 * @since 5.5.9
	 *
	 * @param string $id ID of email.
	 *
	 * @return Email_Abstract|boolean Email object or false if it does not exist.
	 */
	public function get_email_by_id( $id ) {
		$emails = $this->get_emails();

		foreach ( $emails as $email ) {
			if ( $email->get_id() === $id ) {
				return $email;
			}
		}
		return false;
	}
}
