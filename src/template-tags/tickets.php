<?php
/**
 * Ticketing functions.
 *
 * Helpers to work with and customize ticketing-related features.
 */

// Don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	exit( '-1' );
}

if ( ! function_exists( 'tribe_tickets_parent_post' ) ) {
	/**
	 * Returns the current post object that can have tickets attached to it
	 *
	 * Optionally the post object or ID of a ticket post can be passed in and, again, the
	 * parent (event) post object will be returned if possible
	 *
	 * @param int|WP_Post $data
	 * @return null|WP_Post
	 */
	function tribe_tickets_parent_post( $data ) {
		global $post;

		if ( null === $data ) {
			return $post;
		}

		if (
			$data instanceof WP_Post
			&& tribe_tickets_post_type_enabled( get_post_type( $data ) )
		) {
			return $data;
		}

		if ( is_numeric( $data ) && intval( $data ) === $data ) {
			$data = get_post( $data );

			if (
				null !== $data
				&& tribe_tickets_post_type_enabled( get_post_type( $data ) )
			) {
				return $data;
			}
		}

		return null;
	}
}

if ( ! function_exists( 'tribe_events_has_tickets' ) ) {
	/**
	 * Determines if any tickets exist for the current event (a specific event
	 * may be specified, though, by passing the post ID or post object).
	 *
	 * @param $event
	 *
	 * @return bool
	 */
	function tribe_events_has_tickets( $event = null ) {
		if ( null === ( $event = tribe_tickets_parent_post( $event ) ) ) {
			return false;
		}

		$tickets = Tribe__Tickets__Tickets::get_all_event_tickets( $event->ID );
		return ! empty( $tickets );
	}
}//end if

if ( ! function_exists( 'tribe_events_has_soldout' ) ) {
	/**
	 * Determines if the event has sold out of tickets.
	 *
	 * Note that this will also return true if the event has no tickets
	 * whatsoever, and so it may be best to test with tribe_events_has_tickets()
	 * before using this to avoid ambiguity.
	 *
	 * @param null $event
	 *
	 * @return bool
	 */
	function tribe_events_has_soldout( $event = null ) {
		$has_tickets = tribe_events_has_tickets( $event );
		$no_stock = tribe_events_count_available_tickets( $event ) < 1;
		$unlimited_inventory_items = tribe_events_has_unlimited_stock_tickets( $event );

		return ( $has_tickets && $no_stock && ! $unlimited_inventory_items );
	}
}

if ( ! function_exists( 'tribe_events_partially_soldout' ) ) {
	/**
	 * Indicates if one or more of the tickets available for this event (but not
	 * all) have sold out.
	 *
	 * This is useful to indicate if for example 2 out of three ticket types
	 * have soldout but one still has stock remaining.
	 *
	 * @param null $event
	 *
	 * @return bool
	 */
	function tribe_events_partially_soldout( $event = null ) {
		if ( null === ( $event = tribe_tickets_parent_post( $event ) ) ) {
			return false;
		}

		$stock_is_available = false;
		$some_have_soldout = false;

		foreach ( Tribe__Tickets__Tickets::get_all_event_tickets( $event->ID ) as $ticket ) {
			if ( ! $stock_is_available && 0 < $ticket->stock() ) {
				$stock_is_available = true;
			}

			if ( ! $some_have_soldout && 0 == $ticket->stock() ) {
				$some_have_soldout = true;
			}
		}

		return $some_have_soldout && $stock_is_available;
	}
}//end if

if ( ! function_exists( 'tribe_events_count_available_tickets' ) ) {
	/**
	 * Counts the total number of tickets still available for sale for a
	 * specific event.
	 *
	 * @param null $event
	 *
	 * @return int
	 */
	function tribe_events_count_available_tickets( $event = null ) {
		$count = 0;

		if ( null === ( $event = tribe_tickets_parent_post( $event ) ) ) {
			return 0;
		}

		foreach ( Tribe__Tickets__Tickets::get_all_event_tickets( $event->ID ) as $ticket ) {
			$count += $ticket->stock();
		}

		return $count;
	}
}//end if

if ( ! function_exists( 'tribe_tickets_buy_button' ) ) {

	/**
	 * Echos Remaining Ticket Count and Purchase Buttons for an Event
	 *
	 * @since  4.5
	 *
	 * @param bool $echo Whether or not we should print
	 *
	 * @return string
	 */
	function tribe_tickets_buy_button( $echo = true ) {
		$event_id = get_the_ID();

		// check if there are any tickets on sale
		if ( ! tribe_events_has_tickets_on_sale( $event_id ) ) {
			return null;
		}

		// get an array for ticket and rsvp counts
		$types = Tribe__Tickets__Tickets::get_ticket_counts( $event_id );

		// if no rsvp or tickets return
		if ( ! $types ) {
			return null;
		}

		$html = array();
		$parts = array();

		// If we have tickets or RSVP, but everything is Sold Out then display the Sold Out message
		foreach ( $types as $type => $data ) {
			if ( ! $data['count'] ) {
				continue;
			}

			if ( ! $data['available'] ) {
				$parts[ $type . '-stock' ] = '<span class="tribe-out-of-stock">' . esc_html_x( 'Sold out', 'list view stock sold out', 'event-tickets' ) . '</span>';

				// Only re-aply if we don't have a stock yet
				if ( empty( $html['stock'] ) ) {
					$html['stock'] = $parts[ $type . '-stock' ];
				}
			} else {
				$stock = $data['stock'];
				if ( $data['unlimited'] || ! $data['stock'] ) {
					// if unlimited tickets, tickets with no stock and rsvp, or no tickets and rsvp unlimited - hide the remaining count
					$stock = false;
				}

				$stock_html = '';
				if ( $stock ) {
					$number = number_format_i18n( $stock );
					if ( 'rsvp' === $type ) {
						$text = _n( '%s spot left', '%s spots left', $stock, 'event-tickets' );
					} else {
						$text = _n( '%s ticket left', '%s tickets left', $stock, 'event-tickets' );
					}

					$stock_html = '<span class="tribe-tickets-left">'
						. esc_html( sprintf( $text, $number ) )
						. '</span>';
				}

				$parts[ $type . '-stock' ] = $html['stock'] = $stock_html;

				if ( 'rsvp' === $type ) {
					$button_label  = esc_html_x( 'RSVP Now!', 'list view rsvp now ticket button', 'event-tickets' );
					$button_anchor = '#rsvp-now';
				} else {
					$button_label  = esc_html_x( 'Buy Now!', 'list view buy now ticket button', 'event-tickets' );
					$button_anchor = '#buy-tickets';
				}

				$button = '<form method="get" action="' . esc_url( get_the_permalink( $event_id ) . $button_anchor ) . '">'
					. '<button type="submit" name="tickets_process" class="tribe-button">' . $button_label . '</button>'
					. '</form>';

				$parts[ $type . '-button' ] = $html['button'] = $button;
			}
		}

		/**
		 * Filter the ticket count and purchase button
		 *
		 * @since  4.5
		 *
		 * @param array $html     An array with the final HTML
		 * @param array $parts    An array with all the possible parts of the HTMl button
		 * @param array $types    Ticket and RSVP count array for event
		 * @param int   $event_id Post Event ID
		 */
		$html = apply_filters( 'tribe_tickets_buy_button', $html, $parts, $types, $event_id );
		$html = implode( "\n", $html );

		if ( $echo ) {
			echo $html;
		}

		return $html;
	}
}

if ( ! function_exists( 'tribe_tickets_has_unlimited_stock_tickets' ) ) {
	/**
	 * Returns true if the event contains one or more tickets which are not
	 * subject to any inventory limitations.
	 *
	 * @param null $event
	 *
	 * @return bool
	 */
	function tribe_events_has_unlimited_stock_tickets( $event = null ) {
		if ( null === ( $event = tribe_tickets_parent_post( $event ) ) ) {
			return 0;
		}

		foreach ( Tribe__Tickets__Tickets::get_all_event_tickets( $event->ID ) as $ticket ) {
			// Using equal operator as identical comparison operator causes this to always be false
			if ( Tribe__Tickets__Ticket_Object::UNLIMITED_STOCK === $ticket->stock() ) {
				return true;
			}
		}

		return false;
	}
}//end if

if ( ! function_exists( 'tribe_events_product_is_ticket' ) ) {
	/**
	 * Determines if the product object (or product ID) represents a ticket for
	 * an event.
	 *
	 * @param $product
	 *
	 * @return bool
	 */
	function tribe_events_product_is_ticket( $product ) {
		$matching_event = tribe_events_get_ticket_event( $product );
		return ( false !== $matching_event );
	}
}//end if

if ( ! function_exists( 'tribe_events_get_ticket_event' ) ) {
	/**
	 * Accepts the post object or ID for a product and, if it represents an event
	 * ticket, returns the corresponding event object.
	 *
	 * If this cannot be determined boolean false will be returned instead.
	 *
	 * @param $possible_ticket
	 *
	 * @return bool|WP_Post
	 */
	function tribe_events_get_ticket_event( $possible_ticket ) {
		return Tribe__Tickets__Tickets::find_matching_event( $possible_ticket );
	}
}//end if

if ( ! function_exists( 'tribe_events_ticket_is_on_sale' ) ) {
	/**
	 * Checks if the ticket is on sale (in relation to it's start/end sale dates).
	 *
	 * @param Tribe__Tickets__Ticket_Object $ticket
	 *
	 * @return bool
	 */
	function tribe_events_ticket_is_on_sale( Tribe__Tickets__Ticket_Object $ticket ) {
		// No dates set? Then it's on sale!
		if ( empty( $ticket->start_date ) && empty( $ticket->end_date ) ) {
			return true;
		}

		// Timestamps for comparison purposes
		$now    = current_time( 'timestamp' );
		$start  = strtotime( $ticket->start_date );
		$finish = strtotime( $ticket->end_date );

		// Are we within the applicable date range?
		$has_started = ( empty( $ticket->start_date ) || ( $start && $now > $start ) );
		$not_ended   = ( empty( $ticket->end_date ) || ( $finish && $now < $finish ) );

		// Result
		return ( $has_started && $not_ended );
	}
}//end if

if ( ! function_exists( 'tribe_events_has_tickets_on_sale' ) ) {
	/**
	 * Checks if the event has any tickets on sale
	 *
	 * @param int $event_id
	 *
	 * @return bool
	 */
	function tribe_events_has_tickets_on_sale( $event_id ) {
		$has_tickets_on_sale = false;
		$tickets = Tribe__Tickets__Tickets::get_all_event_tickets( $event_id );
		foreach ( $tickets as $ticket ) {
			$has_tickets_on_sale = ( $has_tickets_on_sale || tribe_events_ticket_is_on_sale( $ticket ) );
		}

		return $has_tickets_on_sale;
	}
}

if ( ! function_exists( 'tribe_tickets_get_ticket_stock_message' ) ) {
	/**
	 * Gets the "tickets sold" message for a given ticket
	 *
	 * @param Tribe__Tickets__Ticket_Object $ticket Ticket to analyze
	 *
	 * @return string
	 */
	function tribe_tickets_get_ticket_stock_message( Tribe__Tickets__Ticket_Object $ticket ) {

		$stock        = $ticket->stock();
		$sold         = $ticket->qty_sold();
		$cancelled    = $ticket->qty_cancelled();
		$pending      = $ticket->qty_pending();
		$event        = Tribe__Tickets__Tickets::find_matching_event( $ticket );
		$global_stock = new Tribe__Tickets__Global_Stock( $event->ID );

		$is_global = Tribe__Tickets__Global_Stock::GLOBAL_STOCK_MODE === $ticket->global_stock_mode();
		$is_capped = Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE === $ticket->global_stock_mode();
		$stock_cap = $ticket->global_stock_cap();

		// If ticket sales are capped, do not suggest that more than the cap amount are available
		if ( $is_capped && $stock > $stock_cap ) {
			$stock = $stock_cap;
		}

		// If it is a global-stock ticket but the global stock level has not yet been set for the event
		// then return something better than just '0' as the available stock
		if ( $is_global && 0 === $stock && ! $global_stock->is_enabled() ) {
			$stock = '<i>' . __( 'global inventory', 'event-tickets' ) . '</i>';
		}

		$sold_label = __( 'Sold', 'event-tickets' );
		if ( 'Tribe__Tickets__RSVP' === $ticket->provider_class ) {
			$sold_label = _x( 'RSVP\'d Going', 'separate going and remain RSVPs', 'event-tickets' );
		}

		// There may not be a fixed inventory - in which case just report the number actually sold so far
		if ( empty( $stock ) && $stock !== 0 ) {
			$message = sprintf( esc_html__( '%s %d', 'event-tickets' ), esc_html( $sold_label ), esc_html( $sold ) );
		} // If we do have a fixed stock then we can provide more information
		else {
			$status = '';

			if ( $is_global && 0 < $stock && $global_stock->is_enabled() ) {
				$status_counts[] = sprintf( _x( '%1$d Remaining of the global stock', 'ticket global stock message (remaining stock)', 'event-tickets' ), (int) $stock );
			} else {
				$status_counts[] = sprintf( _x( '%1$d Remaining', 'ticket stock message (remaining stock)', 'event-tickets' ), (int) $stock );
			}

			$status_counts[] = $pending < 1 ? false : sprintf( _x( '%1$d Awaiting Review', 'ticket stock message (pending stock)', 'event-tickets' ), (int) $pending );

			$status_counts[] = empty( $cancelled ) ? false : sprintf( _x( '%1$d Cancelled', 'ticket stock message (cancelled stock)', 'event-tickets' ), (int) $cancelled );

			//remove empty values and prepare to display if values
			$status_counts = array_diff( $status_counts, array( '' ) );
			if ( array_filter( $status_counts ) ) {
				$status = sprintf( ' (%1$s)', implode( ', ', $status_counts ) );
			}

			$message = sprintf( '%1$d %2$s%3$s', absint( $sold ), esc_html( $sold_label ), esc_html( $status ) );
		}

		return $message;
	}
}//end if

/**
 * Returns or echoes a url to a file in the Event Tickets plugin resources directory
 *
 * @category Tickets
 * @param string $resource the filename of the resource
 * @param bool   $echo     whether or not to echo the url
 * @param string $root_dir directory to hunt for resource files (src or common)
 *
 * @return string
 **/
function tribe_tickets_resource_url( $resource, $echo = false, $root_dir = 'src' ) {
	$extension = pathinfo( $resource, PATHINFO_EXTENSION );

	if ( 'src' !== $root_dir ) {
		return tribe_resource_url( $resource, $echo, $root_dir );
	}

	$resources_path = $root_dir . '/resources/';
	switch ( $extension ) {
		case 'css':
			$resource_path = $resources_path .'css/';
			break;
		case 'js':
			$resource_path = $resources_path .'js/';
			break;
		case 'scss':
			$resource_path = $resources_path .'scss/';
			break;
		default:
			$resource_path = $resources_path;
			break;
	}

	$path = $resource_path . $resource;

	$url  = plugins_url( Tribe__Tickets__Main::instance()->plugin_dir . $path );

	/**
	 * Filter the ticket resource URL
	 *
	 * @var $url Resource URL
	 * @var $resource The filename of the resource
	 */
	$url = apply_filters( 'tribe_tickets_resource_url', $url, $resource );

	if ( $echo ) {
		echo esc_url( $url );
	}

	return $url;
}

/**
 * Includes a template part, similar to the WP get template part, but looks
 * in the correct directories for Tribe Tickets templates
 *
 * @param string      $slug The Base template name
 * @param null|string $name (optional) if set will try to include `{$slug}-{$name}.php` file
 * @param array       $data (optional) array of vars to inject into the template part
 * @param boolean     $echo (optional) Allows the user to print or return the template
 *
 * @uses Tribe__Tickets__Templates::get_template_hierarchy
 *
 * @return string|void It will depend if it's echoing or not
 **/
function tribe_tickets_get_template_part( $slug, $name = null, array $data = null, $echo = true ) {

	/**
	 * Fires an Action before echoing the Template
	 *
	 * @param string $slug     Slug for this template
	 * @param string $name     Template name
	 * @param array  $data     The Data that will be used on this template
	 */
	do_action( 'tribe_tickets_pre_get_template_part', $slug, $name, $data );

	// Setup possible parts
	$templates = array();
	if ( isset( $name ) ) {
		$templates[] = $slug . '-' . $name . '.php';
	}
	$templates[] = $slug . '.php';

	/**
	 * Allow users to filter which templates can be included
	 *
	 * @param string $template The Template file, which is a relative path from the Folder we are dealing with
	 * @param string $slug     Slug for this template
	 * @param string $name     Template name
	 * @param array  $data     The Data that will be used on this template
	 */
	$templates = apply_filters( 'tribe_tickets_get_template_part_templates', $templates, $slug, $name, $data );

	// Make any provided variables available in the template's symbol table
	if ( is_array( $data ) ) {
		extract( $data );
	}

	// loop through templates, return first one found.
	foreach ( $templates as $template ) {
		$file = Tribe__Tickets__Templates::get_template_hierarchy( $template, array( 'disable_view_check' => true ) );

		/**
		 * Allow users to filter which template will be included
		 *
		 * @param string $file     Complete path to include the PHP File
		 * @param string $template The Template file, which is a relative path from the Folder we are dealing with
		 * @param string $slug     Slug for this template
		 * @param string $name     Template name
		 * @param array  $data     The Data that will be used on this template
		 */
		$file = apply_filters( 'tribe_tickets_get_template_part_path', $file, $template, $slug, $name, $data );

		/**
		 * A more Specific Filter that will include the template name
		 *
		 * @param string $file     Complete path to include the PHP File
		 * @param string $slug     Slug for this template
		 * @param string $name     Template name
		 * @param array  $data     The Data that will be used on this template
		 */
		$file = apply_filters( "tribe_tickets_get_template_part_path_{$template}", $file, $slug, $name, $data );

		if ( ! file_exists( $file ) ) {
			continue;
		}

		ob_start();
		/**
		 * Fires an Action before including the template file
		 *
		 * @param string $template The Template file, which is a relative path from the Folder we are dealing with
		 * @param string $file     Complete path to include the PHP File
		 * @param string $slug     Slug for this template
		 * @param string $name     Template name
		 * @param array  $data     The Data that will be used on this template
		 */
		do_action( 'tribe_tickets_before_get_template_part', $template, $file, $slug, $name, $data );
		include( $file );

		/**
		 * Fires an Action After including the template file
		 * @param string $template The Template file, which is a relative path from the Folder we are dealing with
		 * @param string $file     Complete path to include the PHP File
		 * @param string $slug     Slug for this template
		 * @param string $name     Template name
		 * @param array  $data     The Data that will be used on this template
		 */
		do_action( 'tribe_tickets_after_get_template_part', $template, $file, $slug, $name, $data );
		$html = ob_get_clean();

		/**
		 * Allow users to filter the final HTML
		 * @param string $html     The final HTML
		 * @param string $template The Template file, which is a relative path from the Folder we are dealing with
		 * @param string $file     Complete path to include the PHP File
		 * @param string $slug     Slug for this template
		 * @param string $name     Template name
		 * @param array  $data     The Data that will be used on this template
		 */
		$html = apply_filters( 'tribe_tickets_get_template_part_content', $html, $template, $file, $slug, $name, $data );

		if ( $echo ) {
			echo $html;
		}

		break;
	}

	/**
	 * Files an Action after echoing/saving the html Template
	 *
	 * @param string $slug     Slug for this template
	 * @param string $name     Template name
	 * @param array  $data     The Data that will be used on this template
	 */
	do_action( 'tribe_tickets_post_get_template_part', $slug, $name, $data );

	if ( ! $echo ) {
		// Return should come at the end
		return $html;
	}
}

if ( ! function_exists( 'tribe_tickets_post_type_enabled' ) ) {
	/**
	 * Returns whether or not the provided post type allows tickets to be attached
	 *
	 * @param string $post_type
	 * @return boolean
	 */
	function tribe_tickets_post_type_enabled( $post_type ) {
		$post_types = Tribe__Tickets__Main::instance()->post_types();

		return in_array( $post_type, $post_types );
	}
}

if ( ! function_exists( 'tribe_tickets_get_event_ids' ) ) {

	/**
	 * Gets an array of event ids when passing an id
	 *
	 * @param integer|string $id a rsvp order key, order id, attendee id, ticket id, or product id
	 *
	 * @return array
	 */
	function tribe_tickets_get_event_ids( $id ) {
		return tribe( 'tickets.data_api' )->get_event_ids( $id );
	}
}

if ( ! function_exists( 'tribe_tickets_get_ticket_provider' ) ) {

	/**
	 * Gets the ticket provider class when passed an id
	 *
	 * @param integer|string $id a rsvp order key, order id, attendee id, ticket id, or product id
	 *
	 * @return bool|object
	 */
	function tribe_tickets_get_ticket_provider( $id ) {
		return tribe( 'tickets.data_api' )->get_ticket_provider( $id );
	}
}

if ( ! function_exists( 'tribe_tickets_get_attendees' ) ) {

	/**
	 * Get attendee(s) by an id
	 *
	 * @param integer|string $id a rsvp order key, order id, attendee id, ticket id, or event id
	 * @param null $context use 'rsvp_order' to get all rsvp tickets from an order based off the post id and not the order key
	 *
	 * @return array() an array of all attendee(s) data including custom attendee meta for a given id
	 */
	function tribe_tickets_get_attendees( $id, $context = null ) {
		return tribe( 'tickets.data_api' )->get_attendees_by_id( $id, $context );
	}
}

if ( ! function_exists( 'tribe_tickets_has_meta_data' ) ) {

	/**
	 * Return true or false if a given id has meta data
	 *
	 * @param integer|string $id a rsvp order key, order id, attendee id, ticket id, or event id
	 * @param null $context use 'rsvp_order' to get all rsvp tickets from an order based off the post id and not the order key
	 *
	 * @return bool
	 */
	function tribe_tickets_has_meta_data( $id, $context = null ) {
		return tribe( 'tickets.data_api' )->attendees_has_meta_data( $id, $context );
	}
}

if ( ! function_exists( 'tribe_tickets_has_meta_fields' ) ) {

	/**
	 * Return true or false if a given id has meta fields
	 *
	 * @param integer|string $id a rsvp order key, order id, attendee id, ticket id, or event id
	 * @param null $context use 'rsvp_order' to get all rsvp tickets from an order based off the post id and not the order key
	 *
	 * @return bool
	 */
	function tribe_tickets_has_meta_fields( $id, $context = null ) {
		return tribe( 'tickets.data_api' )->ticket_has_meta_fields( $id, $context );
	}
}
