<?php

use TEC\Tickets\Admin\Panel_Data;
use TEC\Tickets\Admin\Panels_Data\Ticket_Panel_Data;
use TEC\Tickets\Event;

/**
 *    Class in charge of registering and displaying
 *  the tickets metabox in the event edit screen.
 *  Metabox will only be added if there's a
 *     Tickets Pro provider (child of TribeTickets)
 *     available.
 */
class Tribe__Tickets__Metabox {

	/**
	 * Configure all action and filters user by this Class
	 *
	 * @return void
	 */
	public function hook() {
		add_action( 'add_meta_boxes', array( $this, 'configure' ) );

		add_action( 'tribe_events_tickets_bottom_right', array( $this, 'get_ticket_controls' ), 10, 2 );

		add_action( 'wp_ajax_tribe-ticket-panels', array( $this, 'ajax_panels' ) );

		add_action( 'wp_ajax_tribe-ticket-add', array( $this, 'ajax_ticket_add' ) );
		add_action( 'wp_ajax_tribe-ticket-edit', array( $this, 'ajax_ticket_edit' ) );
		add_action( 'wp_ajax_tribe-ticket-delete', array( $this, 'ajax_ticket_delete' ) );
		add_action( 'wp_ajax_tribe-ticket-duplicate', array( $this, 'ajax_ticket_duplicate' ) );

		add_action( 'wp_ajax_tribe-ticket-checkin', array( $this, 'ajax_attendee_checkin' ) );
		add_action( 'wp_ajax_tribe-ticket-uncheckin', array( $this, 'ajax_attendee_uncheckin' ) );
	}

	/**
	 * Configures the Tickets Editor into a Post Type
	 *
	 * @since 4.6.2
	 *
	 * @param string $post_type Which post type we are trying to configure
	 *
	 * @return void
	 */
	public function configure( $post_type = null ) {
		$modules = Tribe__Tickets__Tickets::modules();
		if ( empty( $modules ) ) {
			return;
		}

		if ( ! in_array( $post_type, Tribe__Tickets__Main::instance()->post_types() ) ) {
			return;
		}

		add_meta_box(
			'tribetickets',
			esc_html( tribe_get_ticket_label_plural( 'meta_box_title' ) ),
			array( $this, 'render' ),
			$post_type,
			'normal',
			'high',
			array(
				'__back_compat_meta_box' => true,
			)
		);

		// If we get here means that we will need Thickbox
		add_thickbox();
	}

	/**
	 * Render the actual Metabox
	 *
	 * @since 4.6.2
	 *
	 * @param int|WP_Post   $post_id  Which post we are dealing with by ID or post object.
	 *
	 * @return string|bool
	 */
	public function render( $post_id ) {
		$modules = Tribe__Tickets__Tickets::modules();

		if ( empty( $modules ) ) {
			return false;
		}

		$original_id = $post_id instanceof WP_Post ? $post_id->ID : (int) $post_id;
		$post_id = Event::filter_event_id( $original_id, 'tickets-metabox-render' );

		$post = get_post( $post_id );

		// Prepare all the variables required.
		$start_date = date( 'Y-m-d H:00:00' );
		$end_date   = date( 'Y-m-d H:00:00' );
		$start_time = Tribe__Date_Utils::time_only( $start_date, false );
		$end_time   = Tribe__Date_Utils::time_only( $start_date, false );

		$show_global_stock = Tribe__Tickets__Tickets::global_stock_available();
		$tickets           = Tribe__Tickets__Tickets::get_event_tickets( $post->ID );
		$global_stock      = new Tribe__Tickets__Global_Stock( $post->ID );

		/** @var Tribe__Tickets__Admin__Views $admin_views */
		$admin_views = tribe( 'tickets.admin.views' );

		$context = get_defined_vars();

		// Add the data required by each panel to render correctly.
		$context = array_merge( $context, ( new Ticket_Panel_Data( $post->ID ) )->to_array() );

		$context['panels'] = $this->get_panels( $post );

		return $admin_views->template( [ 'editor', 'metabox' ], $context );
	}

	/**
	 * Refreshes panels after ajax calls that change data
	 *
	 * @since 4.6.2
	 * @since 5.26.4 Add user permission check.
	 *
	 * @return string html content of the panels
	 */
	public function ajax_panels() {
		$post_id = absint( tribe_get_request_var( 'post_id', 0 ) );

		// Didn't get a post id to work with - bail.
		if ( ! $post_id ) {
			wp_send_json_error( esc_html__( 'Invalid Post ID', 'event-tickets' ) );
		}

		// Check user permissions for this post - bail if not authorized.
		if ( ! user_can( get_current_user_id(), 'edit_post', $post_id ) ) {
			wp_send_json_error( esc_html__( 'You do not have permission to access this content.', 'event-tickets' ) );
		}

		// Get the post object and set global $post for templates
		global $post;
		$post = get_post( $post_id );
		if ( ! $post ) {
			wp_send_json_error( esc_html__( 'Invalid Post ID', 'event-tickets' ) );
		}

		// Overwrites for a few templates that use get_the_ID() and get_post()
		$data = wp_parse_args( tribe_get_request_var( array( 'data' ), array() ), array() );
		$ticket_type = $data['ticket_type'] ?? 'default';
		$notice = tribe_get_request_var( 'tribe-notice', false );

		$data = Tribe__Utils__Array::get( $data, array( 'tribe-tickets' ), null );

		/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );

		// Save if the info was passed
		if ( ! empty( $data ) ) {
			$tickets_handler->save_order( $post->ID, isset( $data['list'] ) ? $data['list'] : null );
			$tickets_handler->save_form_settings( $post->ID, isset( $data['settings'] ) ? $data['settings'] : null );
		}

		$return = $this->get_panels( $post, null, $ticket_type );

		$return['notice'] = $this->notice( $notice );

		/**
		 * Allows filtering the data by other plugins/ecommerce solutions©
		 *
		 * @since 4.6
		 *
		 * @param array the return data
		 * @param int the post/event id
		 */
		$return = apply_filters( 'tribe_tickets_ajax_refresh_tables', $return, $post->ID );

		wp_send_json_success( $return );
	}

	/**
	 * Get the Panels for a given post.
	 *
	 * @since 4.6.2
	 * @since 5.24.1 Make the ticket type optional for PHP 8+ compatibility.
	 *
	 * @param int|WP_Post $post        The post object or ID the tickets are for.
	 * @param int|null    $ticket_id   The ID of the ticket to render the panels for, or `null` if rendering for a new
	 *                                 ticket.
	 * @param string|null $ticket_type The ticket type to render the panels for.
	 *
	 * @return array<string,string> A map from panel name to panel HTML content.
	 */
	public function get_panels( $post, $ticket_id = null, ?string $ticket_type = null ) {
		if ( ! $post instanceof WP_Post ) {
			$post = get_post( $post );
		}

		// Bail on invalid post.
		if ( ! $post instanceof WP_Post ) {
			return [];
		}

		// Try to work out the ticket type if it's not provided.
		if ( empty( $ticket_type ) && $ticket_id ) {
			$ticket_type = get_post_meta( $ticket_id, '_type', true );
		}
		$ticket_type = $ticket_type ?: 'default';

		// Overwrites for a few templates that use get_the_ID() and get_post()
		$GLOBALS['post'] = $post;

		// Let's create tickets list markup to return
		$tickets = Tribe__Tickets__Tickets::get_event_tickets( $post->ID );

		/** @var Tribe__Tickets__Admin__Views $admin_views */
		$admin_views = tribe( 'tickets.admin.views' );

		/**
		 * Fire action before the panels are rendered.
		 *
		 * @since 5.8.0
		 *
		 * @param int|WP_Post $post        The post object or ID context of the panel rendering.
		 * @param int|null    $ticket_id   The ID of the ticket being rendered, `null` if a new ticket.
		 * @param string      $ticket_type The ticket type being rendered, `default` if not specified.
		 */
		do_action( 'tec_tickets_panels_before', $post, $ticket_id, $ticket_type );

		$common_panel_data = ( new Ticket_Panel_Data( $post->ID, $ticket_id ) )->to_array();
		$panels = [
			'list'     => $admin_views->template(
				'editor/panel/list',
				[
					'post_id' => $post->ID,
					'tickets' => $tickets,
				],
				false
			),
			'ticket'   => $admin_views->template(
				'editor/panel/ticket',
				array_merge(
					$common_panel_data,
					[ 'ticket_type' => $ticket_type ]
				),
				false
			),
			'settings' => $admin_views->template(
				'editor/panel/settings',
				array_merge(
					$common_panel_data,
					[ 'post_id' => $post->ID ]
				),
				false
			),
		];

		/**
		 * Fire action after the panels are rendered.
		 *
		 * @since 5.8.0
		 *
		 * @param int|WP_Post $post        The post object or ID context of the panel rendering.
		 * @param int|null    $ticket_id   The ID of the ticket being rendered, `null` if a new ticket.
		 * @param string      $ticket_type The ticket type being rendered, `default` if not specified.
		 */
		do_action( 'tec_tickets_panels_after', $post, $ticket_id, $ticket_type );

		/**
		 * Filters the panels data.
		 *
		 * @since 5.27.0
		 *
		 * @param array       $panels      The panels data.
		 * @param int|WP_Post $post        The post object or ID context of the panel rendering.
		 * @param int|null    $ticket_id   The ID of the ticket being rendered, `null` if a new ticket.
		 * @param string      $ticket_type The ticket type being rendered, `default` if not specified.
		 *
		 * @return array The panels data.
		 */
		return (array) apply_filters( 'tec_tickets_panels', $panels, $post, $ticket_id, $ticket_type );
	}

	/**
	 * Sanitizes the data for the new/edit ticket ajax call, and calls the child save_ticket function.
	 *
	 * @since 4.6.2
	 * @since 4.10.9 Use customizable ticket name functions.
	 * @since 5.5.7 Added optional parameter to return values instead of echoing directly.
	 *
	 * @param bool $return_value Optional, flags whether to JSON output directly or return results.
	 *
	 * @return void|WP_Error|array The results depending on $return_value param, WP_Error if something went wrong.
	 */
	public function ajax_ticket_add( $return_value = false ) {
		$return_value = (bool) $return_value;
		$post_id      = absint( tribe_get_request_var( 'post_id', 0 ) );
		$post_id      = Event::filter_event_id( $post_id );

		if ( ! $post_id ) {
			$failed_ticket_output = esc_html__( 'Invalid parent Post', 'event-tickets' );
			if ( $return_value ) {
				return new WP_Error(
					'bad_request',
					$failed_ticket_output,
					[ 'status' => 400 ]
				);
			}
			wp_send_json_error( $failed_ticket_output );
		}

		/**
		 * This is needed because a provider can implement a dynamic set of fields.
		 * Each provider is responsible for sanitizing these values.
		 */
		$data = wp_parse_args( tribe_get_request_var( array( 'data' ), array() ), array() );

		/**
		 * The ticket type might not be defined, read it from the ticket, if possible.
		 */
		$ticket_type = tribe_get_request_var( 'ticket_type', null );
		if ( ! $ticket_type && isset( $data['ticket_id'] ) ) {
			$ticket_type = get_post_meta( $data['ticket_id'], '_type', true );
		}
		$ticket_type = sanitize_text_field( $ticket_type ?: 'default' );

		if ( ! $this->has_permission( $post_id, $_POST, 'add_ticket_nonce' ) ) {
			$failed_ticket_output = esc_html(
				/* Translators:  %1$s - singular ticket term. */
				sprintf( __( 'Failed to add the %1$s. Refresh the page to try again.', 'event-tickets' ),
					tribe_get_ticket_label_singular( 'ajax_ticket_add_error' ) )
			);
			if ( $return_value ) {
				return new WP_Error(
					'bad_request',
					$failed_ticket_output,
					[ 'status' => 400 ]
				);
			}
			wp_send_json_error( $failed_ticket_output );
		}

		if ( ! isset( $data['ticket_provider'] ) || ! $this->module_is_valid( $data['ticket_provider'] ) ) {
			$failed_ticket_output = esc_html__( 'Commerce Provider invalid', 'event-tickets' );
			if ( $return_value ) {
				return new WP_Error(
					'bad_request',
					$failed_ticket_output,
					[ 'status' => 400 ]
				);
			}
			wp_send_json_error( $failed_ticket_output );
		}

		// Get the Provider
		$module = call_user_func( [ $data['ticket_provider'], 'get_instance' ] );

		if ( ! $module instanceof Tribe__Tickets__Tickets ) {
			return new WP_Error(
				'bad_request',
				__( 'Commerce Module invalid', 'event-tickets' ),
				[ 'status' => 400 ]
			);
		}

		// If we have a ticket type, set it.
		$data['ticket_type'] = $ticket_type;

		// Do the actual adding
		$ticket_id = $module->ticket_add( $post_id, $data );

		$failed_ticket_output = esc_html(
		/* Translators: %1$s - Singular ticket term. */
			sprintf( __( 'Failed to add the %1$s', 'event-tickets' ),
				tribe_get_ticket_label_singular( 'ajax_ticket_add_error' ) )
		);

		// Successful?
		if ( $ticket_id ) {

			try {
				/**
				 * Fire action when a ticket has been added
				 *
				 * @since 4.6.2
				 * @since 5.8.0 Added $ticket_id and $data parameters.
				 *
				 * @param int $post_id ID of parent "event" post
				 * @param int $ticket_id ID of ticket post
				 * @param array $data <string,mixed> Array of ticket data
				 */
				do_action( 'tribe_tickets_ticket_added', $post_id, $ticket_id, $data );
			} catch ( Exception $e ) {
				// Something went wrong while executing the actions, let's log the error.
				wp_send_json_error( $failed_ticket_output );
			}
		} else {
			if ( $return_value ) {
				return new WP_Error(
					'bad_request',
					$failed_ticket_output,
					[ 'status' => 400 ]
				);
			}
			wp_send_json_error( $failed_ticket_output );
		}

		$return           = $this->get_panels( $post_id );
		$return['notice'] = $this->notice( 'ticket-add' );

		/**
		 * Filters the return data for ticket add
		 *
		 * @param array $return  Array of data to return to the ajax call
		 * @param int   $post_id ID of parent "event" post
		 */
		$return = apply_filters( 'event_tickets_ajax_ticket_add_data', $return, $post_id );

		if ( $return_value ) {
			return $return;
		}

		wp_send_json_success( $return );
	}

	/**
	 * Returns the data from a single ticket to populate the edit form.
	 *
	 * @since 4.6.2
	 * @since 4.10.9 Use customizable ticket name functions.
	 * @since 4.12.3 Update detecting ticket provider to account for possibly inactive provider. Remove unused vars.
	 * @since 5.5.7 Added optional parameter to return values instead of echoing directly.
	 *
	 * @param bool $return_value Optional, flags whether to JSON output directly or return results.
	 *
	 * @return void|WP_Error|array The results depending on $return_value param, WP_Error if something went wrong.
	 */
	public function ajax_ticket_edit( $return_value = false ) {
		$return_value = (bool) $return_value;
		$post_id      = absint( tribe_get_request_var( 'post_id', 0 ) );
		$post_id      = Event::filter_event_id( $post_id );

		if ( ! $post_id ) {
			$output = esc_html__( 'Invalid parent Post', 'event-tickets' );
			if ( $return_value ) {
				return new WP_Error(
					'bad_request',
					$output,
					[ 'status' => 400 ]
				);
			}
			wp_send_json_error( $output );
		}

		$ticket_id = absint( tribe_get_request_var( 'ticket_id', 0 ) );

		if ( ! $ticket_id ) {
			/* Translators: %1$s - singular ticket term. */
			$output = esc_html( sprintf( __( 'Invalid %1$s', 'event-tickets' ), tribe_get_ticket_label_singular( 'ajax_ticket_edit_error' ) ) );
			if ( $return_value ) {
				return new WP_Error(
					'bad_request',
					$output,
					[ 'status' => 400 ]
				);
			}
			wp_send_json_error( $output );
		}

		if ( ! $this->has_permission( $post_id, $_POST, 'edit_ticket_nonce' ) ) {
			/* Translators: %1$s - singular ticket term. */
			$output = esc_html( sprintf( __( 'Failed to edit the %1$s. Refresh the page to try again.', 'event-tickets' ), tribe_get_ticket_label_singular( 'ajax_ticket_edit_error' ) ) );
			if ( $return_value ) {
				return new WP_Error(
					'bad_request',
					$output,
					[ 'status' => 400 ]
				);
			}
			wp_send_json_error( $output );
		}

		$provider = tribe_tickets_get_ticket_provider( $ticket_id );

		if ( empty( $provider ) ) {
			$output = esc_html__( 'Commerce Module invalid', 'event-tickets' );
			if ( $return_value ) {
				return new WP_Error(
					'bad_request',
					$output,
					[ 'status' => 400 ]
				);
			}
			wp_send_json_error( $output );
		}

		$return = $this->get_panels( $post_id, $ticket_id );

		/**
		 * Provides an opportunity for final adjustments to the data used to populate
		 * the edit-ticket form.
		 *
		 * @param array $return    Data for the JSON response
		 * @param int   $post_id   Post ID
		 * @param int   $ticket_id Ticket ID
		 */
		$return = (array) apply_filters( 'tribe_events_tickets_ajax_ticket_edit', $return, $post_id, $ticket_id );

		if ( $return_value ) {
			return $return;
		}

		wp_send_json_success( $return );
	}

	/**
	 * Sanitizes the data for the delete ticket ajax call, and calls the child delete_ticket
	 * function.
	 *
	 * @since 4.6.2
	 * @since 5.5.7 Added optional parameter to return values instead of echoing directly.
	 *
	 * @param bool $return_value Optional, flags whether to JSON output directly or return results.
	 *
	 * @return void|WP_Error|array The results depending on $return_value param, WP_Error if something went wrong.
	 */
	public function ajax_ticket_delete( $return_value = false ) {
		$return_value = (bool) $return_value;
		$post_id      = absint( tribe_get_request_var( 'post_id', 0 ) );
		$post_id      = Event::filter_event_id( $post_id );

		if ( ! $post_id ) {
			$output = esc_html__( 'Invalid parent Post', 'event-tickets' );
			if ( $return_value ) {
				return new WP_Error(
					'bad_request',
					$output,
					[ 'status' => 400 ]
				);
			}
			wp_send_json_error( $output );
			return;
		}

		$ticket_id = absint( tribe_get_request_var( 'ticket_id', 0 ) );

		if ( ! $ticket_id ) {
			/* Translators: %1$s - singular ticket term */
			$output = esc_html( sprintf( __( 'Invalid %1$s', 'event-tickets' ), tribe_get_ticket_label_singular( 'ajax_ticket_delete_error' ) ) );
			if ( $return_value ) {
				return new WP_Error(
					'bad_request',
					$output,
					[ 'status' => 400 ]
				);
			}
			wp_send_json_error( $output );
			return;
		}

		if ( ! $this->has_permission( $post_id, $_POST, 'remove_ticket_nonce' ) ) {
			/* Translators: %1$s - singular ticket term */
			$output = esc_html( sprintf( __( 'Failed to delete the %1$s. Refresh the page to try again.', 'event-tickets' ), tribe_get_ticket_label_singular( 'ajax_ticket_delete_error' ) ) );
			if ( $return_value ) {
				return new WP_Error(
					'bad_request',
					$output,
					[ 'status' => 400 ]
				);
			}
			wp_send_json_error( $output );
			return;
		}

		$provider = tribe_tickets_get_ticket_provider( $ticket_id );

		if ( empty( $provider ) ) {
			$output = esc_html__( 'Commerce Module invalid', 'event-tickets' );
			if ( $return_value ) {
				return new WP_Error(
					'bad_request',
					$output,
					[ 'status' => 400 ]
				);
			}
			wp_send_json_error( $output );
			return;
		}

		// Pass the control to the child object
		$return = $provider->delete_ticket( $post_id, $ticket_id );

		// Successfully deleted?
		if ( $return ) {
			$return           = $this->get_panels( $post_id );
			$return['notice'] = $this->notice( 'ticket-delete' );

			/**
			 * Fire action when a ticket has been deleted
			 *
			 * @param int $post_id ID of parent "event" post
			 */
			do_action( 'tribe_tickets_ticket_deleted', $post_id );
		}

		if ( $return_value ) {
			return $return;
		}
		wp_send_json_success( $return );
	}

	/**
	 * Sanitizes the data for the duplicate ticket ajax call, then duplicates the ticket and meta.
	 *
	 * @since 5.2.3.
	 * @since 5.5.7 Added optional parameter to return values instead of echoing directly.
	 *
	 * @param bool $return_value Optional, flags whether to JSON output directly or return results.
	 *
	 * @return void|WP_Error|array The results depending on $return_value param, WP_Error if something went wrong.
	 */
	public function ajax_ticket_duplicate( $return_value = false ) {
		$return_value = (bool) $return_value;
		$post_id      = absint( tribe_get_request_var( 'post_id', 0 ) );
		$post_id      = Event::filter_event_id( $post_id );

		if ( ! $post_id ) {
			$output = esc_html__( 'Invalid parent Post', 'event-tickets' );
			if ( $return_value ) {
				return new WP_Error(
					'bad_request',
					$output,
					[ 'status' => 400 ]
				);
			}
			wp_send_json_error( $output );
		}

		$ticket_id = absint( tribe_get_request_var( 'ticket_id', 0 ) );

		if ( ! $ticket_id ) {
			$output = esc_html( sprintf(
			// Translators: %s: dynamic "ticket" text.
				__( 'Invalid %s', 'event-tickets' ),
				tribe_get_ticket_label_singular( 'ajax_ticket_duplicate_error' )
			) );
			if ( $return_value ) {
				return new WP_Error(
					'bad_request',
					$output,
					[ 'status' => 400 ]
				);
			}
			wp_send_json_error( $output );
		}

		if ( ! $this->has_permission( $post_id, $_POST, 'duplicate_ticket_nonce' ) ) {
			$output = esc_html( sprintf(
			// Translators: %s: dynamic "ticket" text.
				__( 'Failed to duplicate the %s. Refresh the page to try again.', 'event-tickets' ),
				tribe_get_ticket_label_singular( 'ajax_ticket_duplicate_error' )
			) );
			if ( $return_value ) {
				return new WP_Error(
					'bad_request',
					$output,
					[ 'status' => 400 ]
				);
			}
			wp_send_json_error( $output );
		}

		$provider = tribe_tickets_get_ticket_provider( $ticket_id );

		if ( empty( $provider ) || ! $provider instanceof Tribe__Tickets__Tickets ) {
			return new WP_Error(
				'bad_request',
				__( 'Commerce Module invalid', 'event-tickets' ),
				[ 'status' => 400 ]
			);
		}

		$duplicate_ticket_id = $provider->duplicate_ticket( $post_id, $ticket_id );

		// Successful?
		if ( $duplicate_ticket_id ) {
			/**
			 * Fire action when a ticket has been added.
			 *
			 * @since 5.2.3
			 * @since 5.16.0 Added the `$ticket_id` and `$data` parameters.
			 *
			 * @param int $post_id ID of parent "event" post.
			 * @param int $ticket_id ID of ticket post
			 * @param array<string,mixed> $data The ticket data, empty in this case.
			 */
			do_action( 'tribe_tickets_ticket_added', $post_id, $ticket_id, [] );
		} else {
			/* Translators: %1$s - singular ticket term */
			$output = esc_html( sprintf( __( 'Failed to duplicate the %1$s', 'event-tickets' ), tribe_get_ticket_label_singular( 'ajax_ticket_duplicate_error' ) ) );
			if ( $return_value ) {
				return new WP_Error(
					'bad_request',
					$output,
					[ 'status' => 400 ]
				);
			}
			wp_send_json_error( $output );
		}

		$return           = $this->get_panels( $post_id );
		$return['notice'] = $this->notice( 'ticket-duplicate' );

		/**
		 * Filters the return data for ticket duplicate.
		 *
		 * @since 5.2.3
		 *
		 * @param array $return  Array of data to return to the ajax call.
		 * @param int   $post_id ID of parent "event" post.
		 */
		$return = apply_filters( 'event_tickets_ajax_ticket_duplicate_data', $return, $post_id );

		if ( $return_value ) {
			return $return;
		}

		wp_send_json_success( $return );
	}

	/**
	 * Handles the check-in ajax call, and calls the checkin method.
	 *
	 * @since 4.6.2
	 * @since 4.12.3 Use new helper method to account for possibly inactive ticket provider.
	 */
	public function ajax_attendee_checkin() {
		$event_id    = Tribe__Utils__Array::get( $_POST, 'event_ID', false );
		$attendee_id = Tribe__Utils__Array::get( $_POST, 'attendee_id', false );

		if ( empty( $attendee_id ) ) {
			wp_send_json_error( __( 'The attendee ID is missing from the request parameters.', 'event-tickets' ) );
		}

		$provider = Tribe__Utils__Array::get( $_POST, 'provider', false );

		$provider = Tribe__Tickets__Tickets::get_ticket_provider_instance( $provider );

		if ( empty( $provider ) ) {
			wp_send_json_error( esc_html__( 'Commerce Module invalid', 'event-tickets' ) );
		}

		if (
			empty( $_POST['nonce'] )
			|| ! wp_verify_nonce( $_POST['nonce'], 'checkin' )
			|| ! $this->user_can( 'edit_posts', $attendee_id )
		) {
			wp_send_json_error( "Cheatin' huh?" );
		}

		// Pass the control to the child object
		$did_checkin = $provider->checkin( $attendee_id, false, $event_id );

		$provider->clear_attendees_cache( $event_id );

		$data = [ 'did_checkin' => $did_checkin ];

		/**
		 * Filters the data to return when an attendee is checked in.
		 *
		 * @since 5.8.2
		 *
		 * @param array{did_checkin: bool} $data        The data to return.
		 * @param int                      $attendee_id The ID of the attendee that was checked in.
		 */
		$data = apply_filters( 'tec_tickets_attendee_manual_checkin_success_data', $data, $attendee_id );

		wp_send_json_success( $data );
	}

	/**
	 * Handles the check-in ajax call, and calls the uncheckin method.
	 *
	 * @since 4.6.2
	 * @since 4.12.3 Use new helper method to account for possibly inactive ticket provider.
	 */
	public function ajax_attendee_uncheckin() {
		$event_id    = Tribe__Utils__Array::get( $_POST, 'event_ID', false );
		$attendee_id = Tribe__Utils__Array::get( $_POST, 'attendee_id', false );

		if ( empty( $attendee_id ) ) {
			wp_send_json_error( __( 'The attendee ID is missing from the request parameters.', 'event-tickets' ) );
		}

		$provider = Tribe__Utils__Array::get( $_POST, 'provider', false );

		$provider = Tribe__Tickets__Tickets::get_ticket_provider_instance( $provider );

		if ( empty( $provider ) ) {
			wp_send_json_error( esc_html__( 'Commerce Module invalid', 'event-tickets' ) );
		}

		if (
			empty( $_POST['nonce'] )
			|| ! wp_verify_nonce( $_POST['nonce'], 'uncheckin' )
			|| ! $this->user_can( 'edit_posts', $attendee_id )
		) {
			wp_send_json_error( "Cheatin' huh?" );
		}

		// Pass the control to the child object
		$did_uncheckin = $provider->uncheckin( $attendee_id );

		$provider->clear_attendees_cache( $event_id );

		$data = [ 'did_uncheckin' => $did_uncheckin ];

		/**
		 * Filters the data to return when an attendee is unchecked in.
		 *
		 * @since 5.8.3
		 *
		 * @param array{did_uncheckin: bool} $data        The data to return.
		 * @param int                        $attendee_id The ID of the attendee that was checked in.
		 */
		$data = apply_filters( 'tec_tickets_attendee_manual_uncheckin_success_data', $data, $attendee_id );

		wp_send_json_success( $data );
	}

	/**
	 * Get the controls (move, delete) as a string.
	 *
	 * @since 4.6.2
	 *
	 * @param int     $post_id
	 * @param int     $ticket_id
	 * @param boolean $echo
	 *
	 * @return string
	 */
	public function get_ticket_controls( $post_id, $ticket_id = 0, $echo = true ) {
		$provider = tribe_tickets_get_ticket_provider( $ticket_id );

		if ( empty( $provider ) ) {
			return '';
		}

		if ( empty( $ticket_id ) ) {
			return '';
		}

		$ticket = $provider->get_ticket( $post_id, $ticket_id );

		if ( empty( $ticket ) ) {
			return '';
		}

		$controls = [];

		if ( tribe_is_truthy( tribe_get_request_var( 'is_admin', true ) ) ) {
			$controls[] = $provider->get_ticket_move_link( $post_id, $ticket );
		}
		$controls[] = $provider->get_ticket_delete_link( $ticket );

		$html = join( ' | ', $controls );

		if ( $echo ) {
			echo $html;
		}

		return $html;
	}

	/**
	 * test if the nonce is correct and the current user has the correct permissions
	 *
	 * @since 4.6.2
	 *
	 * @param WP_Post|int $post
	 * @param array       $data
	 * @param string      $nonce_action
	 *
	 * @return boolean
	 */
	public function has_permission( $post, $data, $nonce_action ) {

		if ( ! $post instanceof WP_Post ) {
			if ( ! is_numeric( $post ) ) {
				return false;
			}

			$post = get_post( $post );
		}

		if ( empty( $data['nonce'] ) || ! wp_verify_nonce( $data['nonce'], $nonce_action ) ) {
			return false;
		}

		return current_user_can( 'edit_event_tickets' )
				|| current_user_can( get_post_type_object( $post->post_type )->cap->edit_others_posts )
				|| current_user_can( 'edit_post', $post->ID );
	}

	/**
	 * Tests if the user has the specified capability in relation to whatever post type
	 * the attendee object relates to.
	 *
	 * For example, if the attendee was generated for a ticket set up in relation to a
	 * post of the `banana` type, the generic capability "edit_posts" will be mapped to
	 * "edit_bananas" or whatever is appropriate.
	 *
	 * @internal for internal plugin use only (in spite of having public visibility)
	 *
	 * @since 4.6.2
	 *
	 * @see    tribe( 'tickets.attendees' )->user_can
	 *
	 * @param string $generic_cap
	 * @param int    $attendee_id
	 *
	 * @return boolean
	 */
	public function user_can( $generic_cap, $attendee_id ) {
		/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );

		$connections = $tickets_handler->get_object_connections( $attendee_id );

		if ( ! $connections->event ) {
			return false;
		}

		/** @var Tribe__Tickets__Attendees $tickets_attendees */
		$tickets_attendees = tribe( 'tickets.attendees' );

		return $tickets_attendees->user_can( $generic_cap, $connections->event );
	}

	/**
	 * Returns whether a class name is a valid active module/provider.
	 *
	 * @since 4.6.2
	 *
	 * @param string  $module  class name of module
	 *
	 * @return bool
	 */
	public function module_is_valid( $module ) {
		return array_key_exists( $module, Tribe__Tickets__Tickets::modules() );
	}

	/**
	 * Returns the markup for a notice in the admin
	 *
	 * @since 4.6.2
	 *
	 * @param string $msg Text for the notice
	 *
	 * @return string Notice with markup
	 */
	protected function notice( $msg ) {
		return sprintf( '<div class="wrap"><div class="updated"><p>%s</p></div></div>', $msg );
	}

	/**
	 * Decimal Character Asset Localization (used on Community Tickets)
	 *
	 * @todo   We need to deprecate this
	 *
	 * @return void
	 */
	public static function localize_decimal_character() {
		$locale  = localeconv();
		$decimal = isset( $locale['decimal_point'] ) ? $locale['decimal_point'] : '.';

		/**
		 * Filter the decimal point character used in the price
		 */
		$decimal = apply_filters( 'tribe_event_ticket_decimal_point', $decimal );

		wp_localize_script( 'event-tickets-js', 'price_format', array(
			'decimal' => $decimal,
			'decimal_error' => __( 'Please enter in without thousand separators and currency symbols.', 'event-tickets' ),
		) );
	}

	/************************
	 *                      *
	 *  Deprecated Methods  *
	 *                      *
	 ************************/
	// @codingStandardsIgnoreStart

	/**
	 * Refreshes panel settings after canceling saving
	 *
	 * @deprecated 4.6.2
	 * @since 4.6
	 *
	 * @return string html content of the panel settings
	 */
	public function ajax_refresh_settings() {

	}

	/**
	 * @deprecated 4.6.2
	 *
	 * @return void
	 */
	public function ajax_handler_save_settings() {

	}

	/**
	 * Registers the tickets metabox if there's at least
	 * one Tribe Tickets module (provider) enabled
	 *
	 * @deprecated 4.6.2
	 *
	 * @param string $post_type The post type.
	 */
	public static function maybe_add_meta_box( $post_type ) {
		tribe( 'tickets.metabox' )->configure( $post_type );
	}

	/**
	 * Loads the content of the tickets metabox if there's at
	 * least one Tribe Tickets module (provider) enabled
	 *
	 * @deprecated 4.6.2
	 *
	 * @param int $post_id The Post ID of the event.
	 */
	public static function do_modules_metaboxes( $post_id ) {
		tribe( 'tickets.metabox' )->render( $post_id );
	}

	/**
	 * Enqueue the tickets metabox JS and CSS
	 *
	 * @deprecated 4.6
	 *
	 * @param string $unused_hook The hook of the current screen.
	 */
	public static function add_admin_scripts( $unused_hook ) {
		_deprecated_function( __METHOD__, '4.6', 'Tribe__Tickets__Assets::admin_enqueue_scripts' );
	}
	// @codingStandardsIgnoreEnd
}
