<?php

namespace TEC\Tickets\Emails;

use WP_Error;
use WP_Post_Type;

/**
 * Class Post_Type
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Emails
 */
class Post_Type {
	/**
	 * Event Tickets Emails post type.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const SLUG = 'tec_tickets_emails';

	/**
	 * Register post type.
	 *
	 * @since TBD
	 *
	 * @return WP_Post_Type|WP_Error The registered post type object on success,
	 *                               WP_Error object on failure.
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

		return register_post_type( static::SLUG, $post_type_args );
	}

	/**
	 * Populate the Tickets Emails post type with the system emails.
	 *
	 * @since TBD
	 */
	public function populate_email_template_posts(): void {
		if ( ! did_action( 'init' ) ) {
			// The global rewrite object is not available, bail. It's required to create the post type.
			return;
		}

		$emails  = tribe( Email_Handler::class )->get_emails();

		// iterate on emails, check if exists by slug and create if not.
		foreach ( $emails as $email ) {
			$email->create_template_post();
		}
	}
}