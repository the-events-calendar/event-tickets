<?php
/**
 * Hydrates the template context of the ticket panel.
 *
 * @since   5.8.0
 *
 * @package TEC\Tickets\Admin\Panels_Data;
 */

namespace TEC\Tickets\Admin\Panels_Data;

use Tribe__Date_Utils as Dates;
use Tribe__Events__Main as TEC;
use Tribe__Tickets__Tickets as Tickets;

/**
 * Class Ticket_Panel_Data.
 *
 * @since   5.8.0
 *
 * @package TEC\Tickets\Admin\Panels_Data;
 */
class Ticket_Panel_Data {
	/**
	 * The ID of the post the Ticket is being edited on.
	 *
	 * @since 5.8.0
	 *
	 * @var int
	 */
	private int $post_id;

	/**
	 * The post ID of the Ticket being edited, or `null` if a new Ticket is being created.
	 *
	 * @since 5.8.0
	 *
	 * @var int|null
	 */
	private ?int $ticket_id;

	public function __construct( int $post_id, ?int $ticket_id = null ) {
		$this->post_id   = $post_id;
		$this->ticket_id = $ticket_id;
	}

	/**
	 * Dumps the data to array format.
	 *
	 * @since 5.8.0
	 * @since 5.18.0 Removed start and end date help text.
	 *
	 * @return array<string,mixed> The data in array format.
	 */
	public function to_array(): array {
		$ticket_id         = $this->ticket_id;
		$is_admin          = tribe_is_truthy( tribe_get_request_var( 'is_admin', is_admin() ) );
		$provider_class    = Tickets::get_event_ticket_provider( $this->post_id ) ?: '';
		$provider          = Tickets::get_ticket_provider_instance( $provider_class );
		$ticket            = null;
		$ticket_start_date = null;
		$ticket_end_date   = null;

		$datepicker_format = Dates::datepicker_formats( Dates::get_datepicker_format_index() );

		$default_module = Tickets::get_default_module();

		if ( $ticket_id === null ) {
			if ( ! $is_admin ) {
				$provider_class = $default_module;
			}
		} else {
			$provider = tribe_tickets_get_ticket_provider( $this->ticket_id );

			if ( ! empty( $provider ) ) {
				$provider_class = $provider->class_name;

				$ticket = $provider->get_ticket( $this->post_id, $this->ticket_id );
			}

			if ( ! empty( $ticket->start_date ) ) {
				$ticket_start_date = Dates::date_only( $ticket->start_date, false, $datepicker_format );
			} else {
				$ticket_start_date = null;
			}

			if ( ! empty( $ticket->end_date ) ) {
				$ticket_end_date = Dates::date_only( $ticket->end_date, false, $datepicker_format );
			} else {
				$ticket_end_date = null;
			}
		}

		$modules = Tickets::modules();

		$timepicker_step = 30;
		if ( class_exists( TEC::class ) ) {
			$timepicker_step = (int) tribe( 'tec.admin.event-meta-box' )->get_timepicker_step( 'start' );
		}

		$rsvp_required_type_error_message = sprintf(
		// Translators: %s: dynamic 'RSVP' text.
			_x(
				'%s type is a required field',
				'admin edit ticket panel error',
				'event-tickets'
			),
			tribe_get_rsvp_label_singular( 'admin_edit_ticket_panel_error' )
		);

		$ticket_start_date_aria_label = sprintf(
		// Translators: %s: dynamic 'Ticket' text.
			_x(
				'%s start date',
				'input start time ARIA label',
				'event-tickets'
			),
			tribe_get_ticket_label_singular( 'input_start_time_aria_label' )
		);

		$ticket_end_date_aria_label = sprintf(
		// Translators: %s: dynamic 'Ticket' text.
			_x(
				'%s end date',
				'input end time ARIA label',
				'event-tickets'
			),
			tribe_get_ticket_label_singular( 'input_end_time_aria_label' )
		);

		$ticket_form_save_text = sprintf(
		// Translators: %s: dynamic 'tickets' text.
			_x(
				'Save %s',
				'meta box ticket form button text',
				'event-tickets'
			),
			tribe_get_ticket_label_singular_lowercase( 'meta_box_ticket_form_button_text' )
		);

		$rsvp_form_save_text = sprintf(
		// Translators: %s: dynamic 'RSVP' text.
			_x(
				'Save %s',
				'RSVP form save value',
				'event-tickets'
			),
			tribe_get_rsvp_label_singular( 'form_save_value' )
		);

		$multiple_providers_notice = sprintf(
			__( 'It looks like you have multiple ecommerce plugins active. We recommend running only one at a time. However, if you need to run multiple, please select which one to use to sell %s for this event.', 'event-tickets' ),
			tribe_get_ticket_label_plural_lowercase( 'multiple_providers' )
		);
		$multiple_providers_notice .= '<em>' . sprintf(
				__( 'Note: adjusting this setting will only impact new %1$s. Existing %1$s will not change. We highly recommend that all %1$s for one event use the same ecommerce plugin.', 'event-tickets' ),
				tribe_get_ticket_label_plural_lowercase( 'multiple_providers' )
			) . '</em>';

		$active_providers = tribe( 'tickets.editor.configuration' )->get_providers();

		$data = [
			'default_module_class'             => $default_module,
			'ticket_end_date'                  => $ticket_end_date,
			'modules'                          => $modules,
			'post_id'                          => $this->post_id,
			'provider'                         => $provider,
			'provider_class'                   => $provider_class,
			'rsvp_form_save_text'              => $rsvp_form_save_text,
			'rsvp_required_type_error_message' => $rsvp_required_type_error_message,
			'ticket_start_date'                => $ticket_start_date,
			'start_date_errors'                => [
				'is-required'         => __( 'Start sale date cannot be empty.', 'event-tickets' ),
				'is-less-or-equal-to' => __( 'Start sale date cannot be greater than End Sale date', 'event-tickets' ),
			],
			'ticket'                           => $ticket,
			'ticket_description'               => $ticket->description ?? '',
			'ticket_end_date_aria_label'       => $ticket_end_date_aria_label,
			'ticket_end_time'                  => $ticket->end_time ?? '',
			'ticket_form_save_text'            => $ticket_form_save_text,
			'ticket_id'                        => $ticket_id,
			'ticket_name'                      => $ticket->name ?? '',
			'ticket_start_date_aria_label'     => $ticket_start_date_aria_label,
			'ticket_start_time'                => $ticket->start_time ?? '',
			'timepicker_round'                 => '00:00:00',
			'timepicker_step'                  => $timepicker_step,
			'multiple_providers_notice'        => $multiple_providers_notice,
			'active_providers'                 => $active_providers,
		];

		/**
		 * Filters the data for the ticket panel.
		 *
		 * @since 5.8.0
		 *
		 * @param array $data      The data for the ticket panel.
		 * @param int   $post_id   The ID of the post being edited.
		 * @param int   $ticket_id The ID of the ticket being edited.
		 */
		$data = apply_filters( 'tec_tickets_ticket_panel_data', $data, $this->post_id, $this->ticket_id );

		return $data;
	}
}
