<?php
/**
 * Handles registering and setup for the Tickets Emails settings tab.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails;

use Tribe__Template;
use Tribe__Tickets__Main;

/**
 * Class Email_Template
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Emails
 */
class Email_Template {

	private bool $preview = false;

	private Array $context_data = [];

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
			$this->template->set_template_folder( 'src/admin-views/settings/emails' );
			$this->template->set_template_context_extract( true );
		}

		return $this->template;
	}

	/**
	 * Returns the email template HTML.
	 *
	 * @return string The HTML of the template.
	 */
	public function get_html( $context = [] ) {
		$template = $this->get_template();
		$context  = wp_parse_args( $context, $this->get_context() );

		return $template->template( 'email-template', $context, false );
	}

	/**
	 * Prints the email template HTML.
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
	 * @param Array $data
	 *
	 * @return void
	 */
	public function set_data( Array $data ) {
		$this->context_data = $data;
	}

	/**
	 * Returns the template context array and creates sample data if preview.
	 *
	 * @since TBD
	 *
	 * @return Array Template context array.
	 */
	public function get_context() {
		$context = [
			'preview'                => $this->preview,
			'header_image_url'       => tribe_get_option( Settings::$option_header_image_url, '' ),
			'header_image_alignment' => tribe_get_option( Settings::$option_header_image_alignment, 'left' ),
			'header_bg_color'        => tribe_get_option( Settings::$option_header_bg_color, '#ffffff' ),
			'ticket_bg_color'        => tribe_get_option( Settings::$option_ticket_bg_color, '#007363' ),
			'footer_content'         => tribe_get_option( Settings::$option_footer_content, '' ),
			'footer_credit'          => tribe_get_option( Settings::$option_footer_credit, true ),
		];
		$context['header_text_color'] = $this->get_contrast_color( $context['header_bg_color'] );
		$context['ticket_text_color'] = $this->get_contrast_color( $context['ticket_bg_color'] );

		if ( $this->preview ) {
			$current_user = wp_get_current_user();
			$this->context_data = [
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

		return array_merge( $context, $this->context_data );
		// return apply_filters( 'tec_tickets_emails_email_template_context', $context );
	}

	/**
	 * Returns contrasting color (light or dark) based on input color.
	 *
	 * @since TBD
	 *
	 * @param string $hexColor 6-character hexidecimal color code, including hash.
	 *
	 * @return string Contrasting 6-character hexidecimal color code.
	 */
	private function get_contrast_color( $hexColor ) {
		// hexColor RGB
		$R1 = hexdec(substr($hexColor, 1, 2));
		$G1 = hexdec(substr($hexColor, 3, 2));
		$B1 = hexdec(substr($hexColor, 5, 2));

		// Black RGB
		$blackColor = "#000000";
		$R2BlackColor = hexdec(substr($blackColor, 1, 2));
		$G2BlackColor = hexdec(substr($blackColor, 3, 2));
		$B2BlackColor = hexdec(substr($blackColor, 5, 2));

		 // Calc contrast ratio
		 $L1 = 0.2126 * pow($R1 / 255, 2.2) +
			   0.7152 * pow($G1 / 255, 2.2) +
			   0.0722 * pow($B1 / 255, 2.2);

		$L2 = 0.2126 * pow($R2BlackColor / 255, 2.2) +
			  0.7152 * pow($G2BlackColor / 255, 2.2) +
			  0.0722 * pow($B2BlackColor / 255, 2.2);

		$contrastRatio = 0;
		if ($L1 > $L2) {
			$contrastRatio = (int)(($L1 + 0.05) / ($L2 + 0.05));
		} else {
			$contrastRatio = (int)(($L2 + 0.05) / ($L1 + 0.05));
		}

		// If contrast is more than 5, return black color
		if ($contrastRatio > 5) {
			return '#000000';
		} else {
			// if not, return white color.
			return '#FFFFFF';
		}
	}
}