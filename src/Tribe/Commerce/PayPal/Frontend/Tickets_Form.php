<?php

/**
 * Class Tribe__Tickets__Commerce__PayPal__Frontend__Tickets_Form
 *
 * @since TBD
 */
class Tribe__Tickets__Commerce__PayPal__Frontend__Tickets_Form {

	/**
	 * Whether the form has rendered already or not
	 *
	 * @var bool
	 */
	protected $has_rendered = false;

	/**
	 * @var Tribe__Tickets__Commerce__PayPal__Main
	 */
	protected $main;

	/**
	 * Tribe__Tickets__Commerce__PayPal__Frontend__Tickets_Form constructor.
	 *
	 * @since TBD
	 *
	 * @param \Tribe__Tickets__Commerce__PayPal__Main $main
	 */
	public function __construct( Tribe__Tickets__Commerce__PayPal__Main $main ) {
		$this->main = $main;
	}

	/**
	 * Modifies the passed content to inject the front-end tickets form.
	 *
	 * @since TBD
	 *
	 * @param string $content The post content
	 */
	public function render( $content ) {
		if ( $this->has_rendered || ! $this->main->is_active() ) {
			return $content;
		}

		$post = $GLOBALS['post'];

		// For recurring events (child instances only), default to loading tickets for the parent event
		if ( ! empty( $post->post_parent ) && function_exists( 'tribe_is_recurring_event' ) && tribe_is_recurring_event( $post->ID ) ) {
			$post = get_post( $post->post_parent );
		}

		$tickets = $this->main->get_tickets( $post->ID );

		if ( empty( $tickets ) ) {
			return;
		}

		Tribe__Tickets__Tickets::add_frontend_stock_data( $tickets );

		$ticket_sent = empty( $_GET['tpp_sent'] ) ? false : true;

		if ( $ticket_sent ) {
			$this->main->add_message( __( 'Your PayPal Ticket has been received! Check your email for your PayPal Ticket confirmation.', 'event-tickets' ), 'success' );
		}

		$ticket_error = empty( $_GET['tpp_error'] ) ? false : (int) $_GET['tpp_error'];

		if ( $ticket_error ) {
			$this->main->add_message( Tribe__Tickets__Commerce__PayPal__Errors::error_code_to_message( $ticket_error ), 'error' );
		}

		$must_login = ! is_user_logged_in() && $this->main->login_required();
		$can_login  = true;

		include $this->main->getTemplateHierarchy( 'tickets/tpp' );

		// It's only done when it's included
		$this->has_rendered = true;
	}
}