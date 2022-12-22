<?php
/**
 * Tickets Emails template object to configure and display the email template.
 *
 * @since TBD
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
 * @since   TBD
 *
 * @package TEC\Tickets\Emails
 */
class Email_Template {

	/**
	 * Whether or not this is for a template preview.
	 * 
	 * @since TBD
	 *
	 * @var boolean
	 */
	private bool $preview = false;

	/**
	 * Holds context array that will be applied to the template.
	 * 
	 * @since TBD
	 *
	 * @var array
	 */
	private array $context_data = [];
	
	/**
	 * Gets the template instance used to setup the rendering html.
	 *
	 * @since TBD
	 *
	 * @return Tribe__Template The template object.
	 */
	public function get_template(): Tribe__Template {
		if ( empty( $this->template ) ) {
			$this->template = new Tribe__Template();
			$this->template->set_template_origin( Tribe__Tickets__Main::instance() );
			// @todo Move template folder into `src/views/v2` before TE release.
			$this->template->set_template_folder( 'src/admin-views/settings/emails' );
			$this->template->set_template_context_extract( true );
		}

		return $this->template;
	}

	/**
	 * Returns the email template HTML.
	 *
	 * @since TBD
	 *
	 * @return string The HTML of the template.
	 */
	public function get_html() {
		$template = $this->get_template();
		return $template->template( 'email-template', $this->get_context(), false );
	}

	/**
	 * Prints the email template HTML.
	 *
	 * @since TBD
	 *
	 * @return void.
	 */
	public function render() {
		echo $this->get_html();
	}

	/**
	 * Sets whether or not this will be a template preview.
	 *
	 * @since TBD
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
	 * @since TBD
	 *
	 * @return boolean Whether or not this is a template preview.
	 */
	public function is_preview() {
		return $this->preview;
	}

	/**
	 * Sets the data for the template context.
	 *
	 * @since TBD
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
	 * @since TBD
	 *
	 * @return array Template context array.
	 */
	public function get_context() {
		$context = [
			'preview'                => $this->preview,
			'header_image_url'       => tribe_get_option( Settings::$option_header_image_url, '' ),
			'header_image_alignment' => tribe_get_option( Settings::$option_header_image_alignment, 'left' ),
			'header_bg_color'        => tribe_get_option( Settings::$option_header_bg_color, '#ffffff' ),
			'ticket_bg_color'        => tribe_get_option( Settings::$option_ticket_bg_color, '#ffffff' ),
			'footer_content'         => tribe_get_option( Settings::$option_footer_content, '' ),
			'footer_credit'          => tribe_get_option( Settings::$option_footer_credit, true ),
		];
		$context['header_text_color'] = Tribe__Utils__Color::get_contrast_color( $context['header_bg_color'] );
		$context['ticket_text_color'] = Tribe__Utils__Color::get_contrast_color( $context['ticket_bg_color'] );

		if ( $this->preview ) {
			$this->context_data = $this->get_preview_context_array();
		}
		
		$this->context_data = apply_filters( 'tec_tickets_emails_email_template_context', array_merge( $context, $this->context_data ) );

		/**
		 * Allow filtering the contxt array before sending to the email template.
		 *
		 * @since TBD
		 *
		 * @param array Context array for email template.
		 */
		return apply_filters( 'tec_tickets_emails_email_template_context', $this->context_data );
	}

	/**
	 * Get the context data in the case of a template preview.
	 *
	 * @since TBD
	 *
	 * @return array Context data.
	 */
	private function get_preview_context_array() {
		$current_user = wp_get_current_user();
		return [
			'recipient_first_name' => $current_user->first_name,
			'recipient_last_name'  => $current_user->last_name,
			'date_string'          => esc_html__( 'September 22 @ 7:00 pm - 11:00 pm', 'event-tickets' ),
			'qr_url'               => esc_url( plugins_url( '/event-tickets/src/resources/images/example-qr.png' ) ),
			'ticket_name'          => esc_html__( 'General Admission', 'event-tickets' ),
			'ticket_id'            => '17e4a14cec',
			'event_title'          => esc_html__( 'Rebirth Brass Band', 'event-tickets' ),
			'event_image_url'      => esc_url( plugins_url( '/event-tickets/src/resources/images/example-event-image.png' ) ),
			'event_venue'          => [
				'name'     => esc_html__( 'Saturn', 'event-tickets' ),
				'address1' => esc_html__( '200 41st Street South', 'event-tickets' ),
				'address2' => esc_html__( 'Birmingham, AL, 35222', 'event-tickets' ),
				'phone'    => esc_html__( '(987) 654-3210', 'event-tickets' ),
				'website'    => esc_url( get_site_url() ),
			],
			'event_description' => '<h4>Additional Information</h4><p>Age Restriction: 18+<br>Door Time: 8:00PM<br>Event Time: 9:00PM</p>',
		];
	}
}