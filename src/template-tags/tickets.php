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
		if ( null === ( $event = tribe_events_get_event( $event ) ) ) {
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
		if ( null === ( $event = tribe_events_get_event( $event ) ) ) {
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

		if ( null === ( $event = tribe_events_get_event( $event ) ) ) {
			return 0;
		}

		foreach ( Tribe__Tickets__Tickets::get_all_event_tickets( $event->ID ) as $ticket ) {
			$count += $ticket->stock();
		}

		return $count;
	}
}//end if

if ( ! function_exists( 'tribe_events_has_unlimited_stock_tickets' ) ) {
	/**
	 * Returns true if the event contains one or more tickets which are not
	 * subject to any inventory limitations.
	 *
	 * @param null $event
	 *
	 * @return bool
	 */
	function tribe_events_has_unlimited_stock_tickets( $event = null ) {
		if ( null === ( $event = tribe_events_get_event( $event ) ) ) {
			return 0;
		}

		foreach ( Tribe__Tickets__Tickets::get_all_event_tickets( $event->ID ) as $ticket ) {
			if ( Tribe__Tickets__Ticket_Object::UNLIMITED_STOCK === $ticket->stock() ) return true;
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
		$now    = time();
		$start  = strtotime( $ticket->start_date );
		$finish = strtotime( $ticket->end_date );

		// Are we within the applicable date range?
		$has_started = ( empty( $ticket->start_date ) || ( $start && $now > $start ) );
		$not_ended   = ( empty( $ticket->end_date ) || ( $finish && $now < $finish ) );

		// Result
		return ( $has_started && $not_ended );
	}
}//end if

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

