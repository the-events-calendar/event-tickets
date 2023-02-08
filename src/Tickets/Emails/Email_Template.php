<?php
/**
 * Tickets Emails template object to configure and display the email template.
 *
 * @since 5.5.7
 *
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails;

use Tribe__Template;
use Tribe__Tickets__Main;
use Tribe__Utils__Color;

/**
 * Class Email_Template
 *
 * @since   5.5.7
 *
 * @package TEC\Tickets\Emails
 */
class Email_Template {

	/**
	 * Whether or not this is for a template preview.
	 *
	 * @since 5.5.7
	 *
	 * @var boolean
	 */
	private bool $preview = false;

	/**
	 * Holds context array that will be applied to the template.
	 *
	 * @since 5.5.7
	 *
	 * @var array
	 */
	private array $context_data = [];

	/**
	 * Variable to hold template object.
	 *
	 * @var null|Tribe__Template
	 */
	private $template;

	/**
	 * Gets the template instance used to setup the rendering html.
	 *
	 * @since 5.5.7
	 *
	 * @return Tribe__Template The template object.
	 */
	public function get_template(): Tribe__Template {
		if ( empty( $this->template ) ) {
			$this->template = new Tribe__Template();
			$this->template->set_template_origin( Tribe__Tickets__Main::instance() );
			$this->template->set_template_folder( 'src/views/v2/emails' );
			$this->template->set_template_context_extract( true );
		}

		return $this->template;
	}

	/**
	 * Returns the email template HTML.
	 *
	 * @since 5.5.7
	 *
	 * @return string The HTML of the template.
	 */
	public function get_html( $context = [], $email = 'template' ) {
		$template = $this->get_template();
		$context  = wp_parse_args( $context, $this->get_context( $email ) );

		return $template->template( $email, $context, false );
	}

	/**
	 * Prints the email template HTML.
	 *
	 * @since 5.5.7
	 *
	 * @return void.
	 */
	public function render() {
		echo $this->get_html();
	}

	/**
	 * Sets whether or not this will be a template preview.
	 *
	 * @since 5.5.7
	 *
	 * @param boolean $is_preview
	 *
	 * @return void
	 */
	public function set_preview( $is_preview = false ) {
		$this->preview = $is_preview;
	}

	/**
	 * Is this a template preview?
	 *
	 * @since 5.5.7
	 *
	 * @return boolean Whether or not this is a template preview.
	 */
	public function is_preview() {
		return $this->preview;
	}

	/**
	 * Sets the data for the template context.
	 *
	 * @since 5.5.7
	 *
	 * @param array $data
	 *
	 * @return void
	 */
	public function set_data( array $data ) {
		$this->context_data = $data;
	}

	/**
	 * Returns the template context array and creates sample data if preview.
	 *
	 * @since 5.5.7
	 *
	 * @return array Template context array.
	 */
	public function get_context( $email = '' ) {
		$context = [
			'email'                  => $email,
			'preview'                => $this->preview,
			'title'                  => esc_html__( 'Ticket Email', 'event-tickets' ),
			'header_image_url'       => tribe_get_option( Admin\Settings::$option_header_image_url, '' ),
			'header_image_alignment' => tribe_get_option( Admin\Settings::$option_header_image_alignment, 'left' ),
			'header_bg_color'        => tribe_get_option( Admin\Settings::$option_header_bg_color, '#ffffff' ),
			'ticket_bg_color'        => tribe_get_option( Admin\Settings::$option_ticket_bg_color, '#007363' ),
			'footer_content'         => tribe_get_option( Admin\Settings::$option_footer_content, '' ),
			'footer_credit'          => true,
			'web_view_url'           => tribe( Web_View::class )->get_url(),
		];
		$context['header_text_color'] = Tribe__Utils__Color::get_contrast_color( $context['header_bg_color'] );
		$context['ticket_text_color'] = Tribe__Utils__Color::get_contrast_color( $context['ticket_bg_color'] );

		if ( $this->preview ) {
			$this->context_data = $this->get_preview_context_array();
		}

		$this->context_data = wp_parse_args( $this->context_data, $context );

		/**
		 * Allow filtering the context array before sending to the email template.
		 *
		 * @since 5.5.7
		 *
		 * @param array Context array for email template.
		 */
		return apply_filters( 'tec_tickets_emails_email_template_context', $this->context_data );
	}

	/**
	 * Get the context data in the case of a template preview.
	 *
	 * @since 5.5.7
	 *
	 * @return array Context data.
	 */
	private function get_preview_context_array() {
		$current_user = wp_get_current_user();
		$title        = empty( $current_user->first_name ) ?
		__( 'Here\'s your ticket!', 'event-tickets' ) :
		sprintf(
			// Translators: %s - First name of email recipient.
			__( 'Here\'s your ticket, %s!', 'event-tickets' ),
			$current_user->first_name
		);

		return [
			'title'   => $title,
			'tickets' => [
				[
					'ticket_id'         => '1234',
					'ticket_name'       => esc_html__( 'General Admission', 'event-tickets' ),
					'holder_name'       => $current_user->first_name . ' ' . $current_user->last_name,
					'holder_first_name' => $current_user->first_name,
					'holder_last_name'  => $current_user->last_name,
					'security_code'     => '17e4a14cec',
					// @todo @juanfra @codingmusician @rafsuntaskin: These should come from TEC.
					'event' => [
						'title'          => esc_html__( 'Rebirth Brass Band', 'event-tickets' ),
						'description'    => '<h4>Additional Information</h4><p>Age Restriction: 18+<br>Door Time: 8:00PM<br>Event Time: 9:00PM</p>',
						'date'           => esc_html__( 'September 22 @ 7:00 pm - 11:00 pm', 'event-tickets' ),
						'image_url'      => esc_url( plugins_url( '/event-tickets/src/resources/images/example-event-image.png' ) ),
						'venue'          => [
							'name'       => esc_html__( 'Saturn', 'event-tickets' ),
							'address1'   => esc_html__( '200 41st Street South', 'event-tickets' ),
							'address2'   => esc_html__( 'Birmingham, AL, 35222', 'event-tickets' ),
							'phone'      => esc_html__( '(987) 654-3210', 'event-tickets' ),
							'website'    => esc_url( get_site_url() ),
						]
					],

				],
			]
		];
	}
}
