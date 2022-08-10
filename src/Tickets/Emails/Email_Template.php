<?php

namespace TEC\Tickets\Emails;

/**
 * Class Email_Template
 *
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Emails
 */
class Email_Template {

	/**
	 * Tickets Email Template Post Type slug.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const POSTTYPE = 'tec_tickets_email_template';

	/**
	 * Meta key to determine whether or not this post is enabled.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $user_relation_meta_key = '_tribe_tickets_email_template_enabled';

	/**
	 * Register this Class post type into WP.
	 *
	 * @since TBD
	 */
	public function register_post_type() {
		$post_type_args = [
			'label'           => __( 'Email Template', 'event-tickets' ),
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
		 * Filter the arguments that craft the email templates post type.
		 *
		 * @see   register_post_type
		 *
		 * @since TBD
		 *
		 * @param array $post_type_args Post type arguments, passed to register_post_type()
		 */
		$post_type_args = apply_filters( 'tec_tickets_email_template_post_type_args', $post_type_args );

		register_post_type( static::POSTTYPE, $post_type_args );
	}

}