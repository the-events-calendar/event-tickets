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
	const POSTTYPE = 'tec_te_template';

	/**
	 * Meta key to determine whether or not this post is enabled.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $enabled_meta_key = '_tec_te_template_enabled';

	/**
	 * Meta key to determine email recipient.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $recipient_meta_key = '_tec_te_template_recipient';

	/**
	 * Meta key for subject line.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $subject_meta_key = '_tec_te_template_subject';

	/**
	 * Meta key for template ID for searching and creating templates.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $id_meta_key = '_tec_te_template_id';

	/**
	 * Register this Class post type into WP.
	 *
	 * @since TBD
	 */
	public function register_post_type() {
		$post_type_args = [
			'label'           => __( 'Message Template', 'event-tickets' ),
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
		$post_type_args = apply_filters( 'tec_te_template_post_type_args', $post_type_args );

		register_post_type( self::POSTTYPE, $post_type_args );
	}

	/**
	 * Get the required message templates.
	 * 
	 * @since TBD
	 *
	 * @return array Array of required templates.
	 */
	public function get_required_templates() {
		
		/**
		 * Filter the array of required message templates.
		 *
		 * @since TBD
		 *
		 * @param array $required_templates Array of required templates.
		 */
		return apply_filters( 'tec_te_required_message_templates', [
			'ticket-email' => [
				'post_title'      => __( 'Ticket Email', 'event-tickets' ),
				'post_content'    => '',
				'post_status'     => 'publish',
				'meta_input'      => [
					self::$id_meta_key        => 'ticket-email',
					self::$enabled_meta_key   => 1,
					self::$recipient_meta_key => 'purchaser',
					self::$subject_meta_key   => __( 'Here\'s Your Tickets!', 'event-tickets' ),
				],
			],
			'rsvp-email' => [
				'post_title'      => __( 'RSVP Email', 'event-tickets' ),
				'post_content'    => '',
				'post_status'     => 'publish',
				'meta_input'      => [
					self::$id_meta_key        => 'rsvp-email',
					self::$enabled_meta_key   => 1,
					self::$recipient_meta_key => 'attendee',
					self::$subject_meta_key   => __( 'RSVP Confirmation', 'event-tickets' ),
				],
			],
			'order-notification' => [
				'post_title'      => __( 'Order Notification', 'event-tickets' ),
				'post_content'    => '',
				'post_status'     => 'publish',
				'meta_input'      => [
					self::$id_meta_key        => 'order-notification',
					self::$enabled_meta_key   => 1,
					self::$recipient_meta_key => 'site-admin',
					self::$subject_meta_key   => __( 'New Order Notification', 'event-tickets' ),
				],
			],
			'order-failure' => [
				'post_title'      => __( 'Order Failure', 'event-tickets' ),
				'post_content'    => '',
				'post_status'     => 'publish',
				'meta_input'      => [
					self::$id_meta_key        => 'order-failure',
					self::$enabled_meta_key   => 1,
					self::$recipient_meta_key => 'site-admin',
					self::$subject_meta_key   => __( 'Order Failure Notice', 'event-tickets' ),
				],
			],
		] );
	}

	/**
	 * Create required message templates.
	 * 
	 * @since TBD
	 */
	public function create_required_templates() {
		$req_templates = $this->get_required_templates();
		foreach ( $req_templates as $template_id => $template ) {
			$this->maybe_create_template( $template );
		}
	}

	/**
	 * Create message templates if it doesn't exist.
	 * 
	 * @since TBD
	 * 
	 * @return int|\WP_Error
	 */
	public function maybe_create_template( $template ) {
		// If template ID not set, bail.
		if ( empty( $template['meta_input'] ) || empty( $template['meta_input'][ self::$id_meta_key ] ) ) {
			return;
		}

		// Search to see if template already exists.
		$search_args = [
			'post_type' => static::POSTTYPE,
			'meta_key'  => static::$id_meta_key,
			'meta_val'  => $template['meta_input'][ self::$id_meta_key ],
		];
		$query = new \WP_Query( $search_args );
		// If template already exists, bail.
		if ( $query->have_posts() ) {
			return;
		}
		return wp_insert_post( $template );
	}
}