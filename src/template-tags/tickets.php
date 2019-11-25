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
	 * Optionally the post object or ID of a ticketed post can be passed in and, again, the
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
			$ticket_stock    = $ticket->stock();
			$unlimited_stock = $ticket_stock === '';
			$has_stock       = (int) $ticket_stock > 0 || $unlimited_stock;

			if ( ! $stock_is_available && $has_stock ) {
				$stock_is_available = true;
			}

			if ( ! $some_have_soldout && ! $has_stock ) {
				$some_have_soldout = true;
			}
		}

		return $some_have_soldout && $stock_is_available;
	}
}//end if

if ( ! function_exists( 'tribe_events_count_available_tickets' ) ) {

	/**
	 * Counts the total number of tickets still available for sale for a specific event.
	 *
	 * @param null $event
	 *
	 * @return int `0` if no tickets available, `-1` if Unlimited, else integer value.
	 */
	function tribe_events_count_available_tickets( $event = null ) {

		$count = 0;

		if ( null === ( $event = tribe_tickets_parent_post( $event ) ) ) {
			return 0;
		}

		/** @var Tribe__Tickets__Ticket_Object $ticket */
		foreach ( Tribe__Tickets__Tickets::get_all_event_tickets( $event->ID ) as $ticket ) {

			$global_stock_mode = $ticket->global_stock_mode();

			if ( $global_stock_mode === Tribe__Tickets__Global_Stock::GLOBAL_STOCK_MODE ) {
				continue;
			}

			$stock_level = $global_stock_mode === Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE ? $ticket->global_stock_cap : $ticket->stock;

			// If we find an unlimited ticket, just return unlimited (-1) so we don't use -1 or an empty string as a numeric stock and try to do math with it
			if (
				$ticket::UNLIMITED_STOCK === $stock_level
				|| -1 === (int) $stock_level
			) {
				return -1;
			}

			$count += (int) $stock_level; // Explicit cast as a failsafe in case a string slips through
		}

		$global_stock = new Tribe__Tickets__Global_Stock( $event->ID );
		$global_stock = $global_stock->is_enabled() ? $global_stock->get_stock_level() : 0;
		$count += $global_stock;

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

		$html = [];
		$parts = [];

		// If we have tickets or RSVP, but everything is Sold Out then display the Sold Out message
		foreach ( $types as $type => $data ) {
			if ( ! $data['count'] ) {
				continue;
			}

			if ( ! $data['available'] ) {
				$parts[ $type . '-stock' ] = '<span class="tribe-out-of-stock">' . esc_html_x( 'Sold out', 'list view stock sold out', 'event-tickets' ) . '</span>';

				// Only re-apply if we don't have a stock yet
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
					$threshold = Tribe__Settings_Manager::get_option( 'ticket-display-tickets-left-threshold', 0 );

					/**
					 * Overwrites the threshold to display "# tickets left".
					 *
					 * @param int   $threshold Stock threshold to trigger display of "# tickets left"
					 * @param array $data      Ticket data.
					 * @param int   $event_id  Event ID.
					 *
					 * @since 4.10.1
					 */
					$threshold = absint( apply_filters( 'tribe_display_tickets_left_threshold', $threshold, $data, $event_id ) );

					if ( ! $threshold || $stock <= $threshold ) {

						if ( is_numeric( $stock ) ) {
							$number = number_format_i18n( (float) $stock );
						} else {
							$number = $stock;
						}

						if ( 'rsvp' === $type ) {
							$text = _n( '%s spot left', '%s spots left', $stock, 'event-tickets' );
						} else {
							$text = _n( '%s ticket left', '%s tickets left', $stock, 'event-tickets' );
						}

						$stock_html = '<span class="tribe-tickets-left">'
							. esc_html( sprintf( $text, $number ) )
							. '</span>';
					}
				}

				$parts[ $type . '-stock' ] = $html['stock'] = $stock_html;

				if ( 'rsvp' === $type ) {
					$button_label  = sprintf( _x( '%s Now!', 'list view rsvp now ticket button', 'event-tickets' ), tribe_get_rsvp_label_singular( 'list_view_rsvp_now_button' ) );
					$button_anchor = '#rsvp-now';
				} else {
					$button_label  = _x( 'Buy Now!', 'list view buy now ticket button', 'event-tickets' );
					$button_anchor = '#buy-tickets';
				}

				$permalink = get_the_permalink( $event_id );
				$query_string = parse_url( $permalink, PHP_URL_QUERY );
				$query_params = empty( $query_string ) ? [] : (array) explode( '&', $query_string );

				$button = '<form method="get" action="' . esc_url( $permalink . $button_anchor ) . '">';

				// Add any query attribute as a hidden input as the action of the form is GET
				foreach ( $query_params as $param ) {
					$parts = explode( '=', $param );

					// a query string must be 2 parts only a name and a value
					if ( is_array( $parts ) && 2 === count( $parts ) ) {
						list( $name, $value ) = $parts;
						$button .= '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';
					}
				}

				$button	.= '<button type="submit" name="tickets_process" class="tribe-button">' . esc_html( $button_label ) . '</button></form>';

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
			// We also return -1 for stock on unlimited tickets
			if ( -1 === (int) $ticket->stock() ) {
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

		return $ticket->date_in_range( 'now' );
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
		$tickets             = Tribe__Tickets__Tickets::get_all_event_tickets( $event_id );
		$default_provider    = Tribe__Tickets__Tickets::get_event_ticket_provider( $event_id );

		foreach ( $tickets as $ticket ) {
			$ticket_provider = $ticket->get_provider();

			// Skip tickets that are for a different provider than the event provider.
			if ( $default_provider !== $ticket_provider->class_name ) {
				continue;
			}

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
	 * @since 4.10.9 Use customizable ticket name functions.
	 *
	 * @return string
	 */
	function tribe_tickets_get_ticket_stock_message( Tribe__Tickets__Ticket_Object $ticket ) {
		$event        = Tribe__Tickets__Tickets::find_matching_event( $ticket );
		$global_stock = new Tribe__Tickets__Global_Stock( $event->ID );

		$stock     = $ticket->stock();
		$available = $ticket->available();
		$sold      = (int) $ticket->qty_sold();

		/**
		 * Allows filtering the available number that will be displayed.
		 *
		 * @since 4.7
		 *
		 * @param int                           $available
		 * @param Tribe__Tickets__Ticket_Object $ticket
		 * @param int                           $sold
		 * @param int                           $stock
		 */
		$available = apply_filters( 'tribe_tickets_stock_message_available_quantity', $available, $ticket, $sold, $stock );

		$cancelled     = (int) $ticket->qty_cancelled();
		$pending       = (int) $ticket->qty_pending();
		$refunded      = (int) $ticket->qty_refunded();
		$status        = '';
		$status_counts = [];

		$is_global = Tribe__Tickets__Global_Stock::GLOBAL_STOCK_MODE === $ticket->global_stock_mode() && $global_stock->is_enabled();
		$is_capped = Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE === $ticket->global_stock_mode() && $global_stock->is_enabled();
		$stock_cap = $ticket->global_stock_cap();

		$event_cap  = tribe_tickets_get_capacity( $event->ID );
		$ticket_cap = tribe_tickets_get_capacity( $ticket->ID );

		$sold_label = __( 'issued', 'event-tickets' );
		if ( 'Tribe__Tickets__RSVP' === $ticket->provider_class ) {
			$sold_label = sprintf( _x( "%s'd going", 'RSVPs going', 'event-tickets' ), tribe_get_rsvp_label_singular() );
		}

		// Message for how many remain available
		if ( -1 === $available ) {
			$status_counts[] = sprintf( _x( '%1$s available', 'unlimited remaining stock message', 'event-tickets' ), tribe_tickets_get_readable_amount( $available, $global_stock ) );
		} elseif ( $is_global ) {
			$status_counts[] = sprintf( _x( '%1$d available of shared capacity', 'ticket shared capacity message (remaining stock)', 'event-tickets' ), tribe_tickets_get_readable_amount( $available ) );
		} else {
			// It's "own stock". We use the $stock value
			$status_counts[] = sprintf( _x( '%1$d available', 'ticket stock message (remaining stock)', 'event-tickets' ), tribe_tickets_get_readable_amount( $available ) );
		}

		if ( ! empty( $status_counts ) ) {
			//remove empty values and prepare to display if values
			$status_counts = array_diff( $status_counts, array( '' ) );
			if ( array_filter( $status_counts ) ) {
				$status = sprintf( ' (%1$s)', implode( ', ', $status_counts ) );
			}
		}

		$message = sprintf( '%1$d %2$s%3$s', absint( $sold ), esc_html( $sold_label ), esc_html( $status ) );

		return $message;
	}
}

if ( ! function_exists( 'tribe_tickets_resource_url' ) ) {

	/**
	 * Returns or echoes a url to a file in the Event Tickets plugin resources directory
	 *
	 * @param string $resource the filename of the resource
	 * @param bool   $echo     whether or not to echo the url
	 * @param string $root_dir directory to hunt for resource files (src or common)
	 *
	 * @return string
	 * @category Tickets
	 */
	function tribe_tickets_resource_url( $resource, $echo = false, $root_dir = 'src' ) {
		$extension = pathinfo( $resource, PATHINFO_EXTENSION );

		if ( 'src' !== $root_dir ) {
			return tribe_resource_url( $resource, $echo, $root_dir );
		}

		$resources_path = $root_dir . '/resources/';
		switch ( $extension ) {
			case 'css':
				$resource_path = $resources_path . 'css/';
				break;
			case 'js':
				$resource_path = $resources_path . 'js/';
				break;
			case 'scss':
				$resource_path = $resources_path . 'scss/';
				break;
			default:
				$resource_path = $resources_path;
				break;
		}

		$path = $resource_path . $resource;

		$url = plugins_url( Tribe__Tickets__Main::instance()->plugin_dir . $path );

		/**
		 * Filter the ticket resource URL
		 *
		 * @var $url      Resource URL
		 * @var $resource The filename of the resource
		 */
		$url = apply_filters( 'tribe_tickets_resource_url', $url, $resource );

		if ( $echo ) {
			echo esc_url( $url );
		}

		return $url;
	}
}

if ( ! function_exists( 'tribe_tickets_get_template_part' ) ) {

	/**
	 * Includes a template part, similar to the WP get template part, but looks
	 * in the correct directories for Tribe Tickets templates
	 *
	 * @param string      $slug The Base template name
	 * @param null|string $name (optional) if set will try to include `{$slug}-{$name}.php` file
	 * @param array       $data (optional) array of vars to inject into the template part
	 * @param boolean     $echo (optional) Allows the user to print or return the template
	 *
	 * @return string|void It will depend if it's echoing or not
	 * @uses Tribe__Tickets__Templates::get_template_hierarchy
	 *
	 */
	function tribe_tickets_get_template_part( $slug, $name = null, array $data = null, $echo = true ) {

		/**
		 * Fires an Action before echoing the Template
		 *
		 * @param string $slug Slug for this template
		 * @param string $name Template name
		 * @param array  $data The Data that will be used on this template
		 */
		do_action( 'tribe_tickets_pre_get_template_part', $slug, $name, $data );

		// Setup possible parts
		$templates = [];
		if ( isset( $name ) ) {
			$templates[] = $slug . '-' . $name . '.php';
		}
		$templates[] = $slug . '.php';

		/**
		 * Allow users to filter which templates can be included
		 *
		 * @param string $template The Template file(s), which is a relative path from the Folder we are dealing with
		 * @param string $slug     Slug for this template
		 * @param string $name     Template name
		 * @param array  $data     The Data that will be used on this template
		 */
		$templates = apply_filters( 'tribe_tickets_get_template_part_templates', $templates, $slug, $name, $data );

		// Make any provided variables available in the template's symbol table
		if ( is_array( $data ) ) {
			extract( $data );
		}

		$html = null;

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
			 * @param string $file Complete path to include the PHP File
			 * @param string $slug Slug for this template
			 * @param string $name Template name
			 * @param array  $data The Data that will be used on this template
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
			 *
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
			 *
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
		 * @param string $slug Slug for this template
		 * @param string $name Template name
		 * @param array  $data The Data that will be used on this template
		 */
		do_action( 'tribe_tickets_post_get_template_part', $slug, $name, $data );

		if ( ! $echo ) {
			// Return should come at the end
			return $html;
		}
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
		/** @var Tribe__Tickets__Data_API $data_api */
		$data_api = tribe( 'tickets.data_api' );

		return $data_api->get_event_ids( $id );
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
		/** @var Tribe__Tickets__Data_API $data_api */
		$data_api = tribe( 'tickets.data_api' );

		return $data_api->get_ticket_provider( $id );
	}
}

if ( ! function_exists( 'tribe_tickets_get_attendees' ) ) {

	/**
	 * Get attendee(s) by an id
	 *
	 * @param integer|string $id a rsvp order key, order id, attendee id, ticket id, or event id
	 * @param null $context use 'rsvp_order' to get all rsvp tickets from an order based off the post id and not the order key
	 *
	 * @return array an array of all attendee(s) data including custom attendee meta for a given id
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

if ( ! function_exists( 'tribe_tickets_delete_capacity' ) ) {

	/**
	 * Removes all meta for a given object capacity. Object can be a ticket, or an event/post with tickets.
	 *
	 * Note, you can pass an event/post to this function and it will merrily change the meta values
	 * for the event - not for the tickets!
	 *
	 * @since  4.6.2
	 *
	 * @param int|WP_Post $object WP_Post (or ID of post) We are trying to delete capacity from.
	 *
	 * @return int|false
	 */
	function tribe_tickets_delete_capacity( $object ) {

		if ( ! $object instanceof WP_Post ) {
			$object = get_post( $object );
		}

		if ( ! $object instanceof WP_Post ) {
			return false;
		}

		/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );

		$deleted = delete_post_meta( $object->ID, $tickets_handler->key_capacity );

		if ( ! $deleted ) {
			return $deleted;
		}

		// We only apply these when we are talking about event-like posts
		if ( tribe_tickets_post_type_enabled( $object->post_type ) ) {
			$shared_cap_object = new Tribe__Tickets__Global_Stock( $object->ID );
			$shared_cap_object->disable();

			// This is mostly to make sure
			delete_post_meta( $object->ID, Tribe__Tickets__Global_Stock::GLOBAL_STOCK_LEVEL );
			delete_post_meta( $object->ID, Tribe__Tickets__Global_Stock::TICKET_STOCK_MODE );
			delete_post_meta( $object->ID, Tribe__Tickets__Global_Stock::TICKET_STOCK_CAP );
		}

		return $deleted;
	}
}

if ( ! function_exists( 'tribe_tickets_update_capacity' ) ) {

	/**
	 * Updates a given Object Capacity
	 *
	 * Note, you can pass an event/post to this function and it will merrily change the meta values
	 * for the event - not for the tickets!
	 *
	 * @since  4.6.2
	 *
	 * @param int|WP_Post|Tribe__Tickets__Ticket_Object $object  Post We are trying to save capacity
	 * @param int                                       $capacty What we are trying to update the capacity to.
	 *
	 * @return int|false
	 */
	function tribe_tickets_update_capacity( $object, $capacity ) {
		if ( ! is_numeric( $capacity ) ) {
			return false;
		}

		if ( ! $object instanceof WP_Post ) {
			$object = get_post( $object );
		}

		if ( ! $object instanceof WP_Post ) {
			return false;
		}

		// Do the actual Updating of the Meta value

		/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );

		return update_post_meta( $object->ID, $tickets_handler->key_capacity, $capacity );
	}
}

if ( ! function_exists( 'tribe_tickets_get_capacity' ) ) {

	/**
	 * Returns the capacity for a given Post
	 *
	 * Note while we can send a post/event we do not store capacity on events
	 * so the return values will always be null.
	 *
	 * @since  4.6
	 *
	 * @param int|WP_Post $post Post we are trying to fetch capacity for.
	 *
	 * @return int|null
	 */
	function tribe_tickets_get_capacity( $post ) {
		// When not dealing with a Instance of Post try to set it up
		if ( ! $post instanceof WP_Post ) {
			$post = get_post( $post );
		}

		// Bail when it's not a post or ID is 0
		if ( ! $post instanceof WP_Post || 0 === $post->ID ) {
			return null;
		}

		$event_types = Tribe__Tickets__Main::instance()->post_types();

		/**
		 * @var Tribe__Tickets__Tickets_Handler $tickets_handler
		 * @var Tribe__Tickets__Version $version
		 */
		$tickets_handler = tribe( 'tickets.handler' );
		$version         = tribe( 'tickets.version' );

		$key = $tickets_handler->key_capacity;

		// When we have a legacy ticket we migrate it
		if (
			! in_array( $post->post_type, $event_types )
			&& $version->is_legacy( $post->ID )
		) {
			$legacy_capacity = $tickets_handler->filter_capacity_support( null, $post->ID, $key );

			// Cast as integer as it might be returned as numeric string on some cases
			return (int) $legacy_capacity;
		}

		// Defaults to the ticket ID
		$post_id = $post->ID;

		// Return Null for when we don't have the Capacity Data
		if ( ! metadata_exists( 'post', $post->ID, $key ) ) {
			$mode         = get_post_meta( $post->ID, Tribe__Tickets__Global_Stock::TICKET_STOCK_MODE, true );
			$shared_modes = [
				Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE,
				Tribe__Tickets__Global_Stock::GLOBAL_STOCK_MODE,
			];

			// When we are in a Ticket Post Type update where we get the value from Event
			if (
				! in_array( $post->post_type, $event_types )
				&& in_array( $mode, $shared_modes )
			) {
				$event_id = tribe_tickets_get_event_ids( $post->ID );

				// It will return an array of Events
				if ( ! empty( $event_id ) ) {
					$post_id = current( $event_id );
				}
			} else {
				return null;
			}
		}

		// Fetch the value
		$value = get_post_meta( $post_id, $key, true );

		// When dealing with an empty string we assume it's unlimited
		if ( '' === $value ) {
			$value = -1;
		}

		return (int) $value;
	}
}

if ( ! function_exists( 'tribe_tickets_get_readable_amount' ) ) {

	/**
	 * Turns a Stock, Remaining, or Capacity number into a human-readable format.
	 *
	 * @since  4.6
	 * @since  4.10.11 Run number through formatting, such as commas to separate thousands.
	 *
	 * @param string|int $number  Which you are trying to convert.
	 * @param string     $mode    Mode this post is on.
	 * @param bool       $display Whether or not to echo.
	 *
	 * @return string
	 */
	function tribe_tickets_get_readable_amount( $number, $mode = 'own', $display = false ) {
		$html = [];

		$show_parens = Tribe__Tickets__Global_Stock::GLOBAL_STOCK_MODE === $mode || Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE === $mode;
		if ( $show_parens ) {
			$html[] = '(';
		}

		if (
			-1 === (int) $number
			|| Tribe__Tickets__Ticket_Object::UNLIMITED_STOCK === $number
		) {
			/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
			$tickets_handler = tribe( 'tickets.handler' );

			$html[] = esc_html( $tickets_handler->unlimited_term );
		} else {
			if ( is_numeric( $number ) ) {
				$html[] = number_format_i18n( (float) $number );
			} else {
				$html[] = (string) $number; // might be "Unlimited"
			}
		}

		if ( $show_parens ) {
			$html[] = ')';
		}

		$html = esc_html( implode( '', $html ) );

		if ( true === $display ) {
			echo $html;
		}

		return $html;
	}
}

if ( ! function_exists( 'tribe_tickets_ticket_in_wc_membership_for_user' ) ) {

	/**
	 * Checks if the specified user (defaults to currently-logged-in user) belongs to any active
	 * WooCommerce Membership plans, *and* if the specified ticket (by ticket ID) has any active
	 * member discounts applied to it. It may not be the user's membership plan specifically, so this
	 * template tag *may* produce some false positives.
	 *
	 * @since 4.7.3
	 *
	 * @param int $ticket_id
	 * @param int $user_id
	 *
	 * @return boolean
	 */
	function tribe_tickets_ticket_in_wc_membership_for_user( $ticket_id, $user_id = 0 ) {

		if (
			! function_exists( 'wc_memberships_get_user_active_memberships' )
			|| ! function_exists( 'wc_memberships_product_has_member_discount' )
		) {
			return false;
		}

		$user_id = 0 ? get_current_user_id() : $user_id;

		$user_is_member             = wc_memberships_get_user_active_memberships( $user_id );
		$ticket_has_member_discount = wc_memberships_product_has_member_discount( $ticket_id );

		return $user_is_member && $ticket_has_member_discount;
	}
}

if ( ! function_exists( 'tribe_tickets' ) ) {

	/**
	 * Builds and returns the correct ticket repository.
	 *
	 * @since 4.8
	 *
	 * @param string $repository The slug of the repository to build/return.
	 *
	 * @return Tribe__Repository__Interface
	 */
	function tribe_tickets( $repository = 'default' ) {
		$map = [
			'default'        => 'tickets.ticket-repository',
			'rsvp'           => 'tickets.ticket-repository.rsvp',
			'tribe-commerce' => 'tickets.ticket-repository.commerce',
			'restv1'         => 'tickets.rest-v1.ticket-repository',
		];

		/**
		 * Filters the map relating ticket repository slugs to service container bindings.
		 *
		 * @since 4.8
		 *
		 * @param array  $map        A map in the shape [ <repository_slug> => <service_name> ]
		 * @param string $repository The currently requested implementation.
		 */
		$map = apply_filters( 'tribe_tickets_ticket_repository_map', $map, $repository );

		return tribe( Tribe__Utils__Array::get( $map, $repository, $map['default'] ) );
	}
}

if ( ! function_exists( 'tribe_attendees' ) ) {

	/**
	 * Builds and returns the correct attendee repository.
	 *
	 * @since 4.8
	 *
	 * @param string $repository The slug of the repository to build/return.
	 *
	 * @return Tribe__Repository__Interface
	 */
	function tribe_attendees( $repository = 'default' ) {
		$map = [
			'default'        => 'tickets.attendee-repository',
			'rsvp'           => 'tickets.attendee-repository.rsvp',
			'tribe-commerce' => 'tickets.attendee-repository.commerce',
			'restv1'         => 'tickets.rest-v1.attendee-repository',
		];

		/**
		 * Filters the map relating attendee repository slugs to service container bindings.
		 *
		 * @since 4.8
		 *
		 * @param array  $map        A map in the shape [ <repository_slug> => <service_name> ]
		 * @param string $repository The currently requested implementation.
		 */
		$map = apply_filters( 'tribe_tickets_attendee_repository_map', $map, $repository );

		return tribe( Tribe__Utils__Array::get( $map, $repository, $map['default'] ) );
	}
}

if ( ! function_exists( 'tribe_get_rsvp_label_singular' ) ) {

	/**
	 * Get the singular version of the RSVP label. May also be used as a verb.
	 *
	 * @since 4.10.9
	 *
	 * @param string $context Allows passing additional context to this function's filter, e.g. 'verb' or 'template.php'.
	 *
	 * @return string
	 */
	function tribe_get_rsvp_label_singular( $context = '' ) {
		/**
		 * Allows customization of the singular version of the RSVP label.
		 *
		 * @since 4.10.9
		 *
		 * @param string $label   The singular version of the RSVP label. Defaults to "RSVP".
		 * @param string $context The context in which this string is filtered, e.g. 'verb' or 'template.php'.
		 */
		return apply_filters( 'tribe_get_rsvp_label_singular', _x( 'RSVP', 'singular label for RSVP', 'event-tickets' ), $context );
	}
}

if ( ! function_exists( 'tribe_get_rsvp_label_singular_lowercase' ) ) {

	/**
	 * Get the lowercase singular version of the RSVP label. May also be used as a verb.
	 *
	 * @since 4.10.9
	 *
	 * @param string $context Allows passing additional context to this function's filter, e.g. 'verb' or 'template.php'.
	 *
	 * @return string
	 */
	function tribe_get_rsvp_label_singular_lowercase( $context = '' ) {
		/**
		 * Allows customization of the lowercase singular version of the RSVP label.
		 *
		 * @since 4.10.9
		 *
		 * @param string $label   The lowercase singular version of the RSVP label. Defaults to "rsvp".
		 * @param string $context The context in which this string is filtered, e.g. 'verb' or 'template.php'.
		 */
		return apply_filters( 'tribe_get_rsvp_label_singular_lowercase', _x( 'rsvp', 'lowercase singular label for RSVP', 'event-tickets' ), $context );
	}
}

if ( ! function_exists( 'tribe_get_rsvp_label_plural' ) ) {

	/**
	 * Get the plural version of the RSVP label. May also be used as a verb.
	 *
	 * @since 4.10.9
	 *
	 * @param string $context Allows passing additional context to this function's filter, e.g. 'verb' or 'template.php'.
	 *
	 * @return string
	 */
	function tribe_get_rsvp_label_plural( $context = '' ) {
		/**
		 * Allows customization of the plural version of the RSVP label.
		 *
		 * @since 4.10.9
		 *
		 * @param string $label   The plural version of the RSVP label, defaults to "RSVPs".
		 * @param string $context The context in which this string is filtered, e.g. 'verb' or 'template.php'.
		 */
		return apply_filters( 'tribe_get_rsvp_label_plural', _x( 'RSVPs', 'plural label for RSVPs', 'event-tickets' ), $context );
	}
}

if ( ! function_exists( 'tribe_get_rsvp_label_plural_lowercase' ) ) {

	/**
	 * Get the lowercase plural version of the RSVP label. May also be used as a verb.
	 *
	 * @since 4.10.9
	 *
	 * @param string $context Allows passing additional context to this function's filter, e.g. 'verb' or 'template.php'.
	 *
	 * @return string
	 */
	function tribe_get_rsvp_label_plural_lowercase( $context = '' ) {
		/**
		 * Allows customization of the lowercase plural version of the RSVP label.
		 *
		 * @since 4.10.9
		 *
		 * @param string $label   The lowercase plural version of the RSVP label, defaults to "rsvps".
		 * @param string $context The context in which this string is filtered, e.g. 'verb' or 'template.php'.
		 */
		return apply_filters( 'tribe_get_rsvp_label_plural_lowercase', _x( 'rsvps', 'lowercase plural label for RSVPs', 'event-tickets' ), $context );
	}
}

if ( ! function_exists( 'tribe_get_ticket_label_singular' ) ) {

	/**
	 * Get the singular version of the Ticket label. May also be used as a verb.
	 *
	 * @since 4.10.9
	 *
	 * @param string $context Allows passing additional context to this function's filter, e.g. 'verb' or 'template.php'.
	 *
	 * @return string
	 */
	function tribe_get_ticket_label_singular( $context = '' ) {
		/**
		 * Allows customization of the singular version of the Ticket label.
		 *
		 * @since 4.10.9
		 *
		 * @param string $label   The singular version of the Ticket label, defaults to "Ticket".
		 * @param string $context The context in which this string is filtered, e.g. 'verb' or 'template.php'.
		 */
		return apply_filters( 'tribe_get_ticket_label_singular', _x( 'Ticket', 'singular label for Ticket', 'event-tickets' ), $context );
	}
}

if ( ! function_exists( 'tribe_get_ticket_label_singular_lowercase' ) ) {

	/**
	 * Get the lowercase singular version of the Ticket label. May also be used as a verb.
	 *
	 * @since 4.10.9
	 *
	 * @param string $context Allows passing additional context to this function's filter, e.g. 'verb' or 'template.php'.
	 *
	 * @return string
	 */
	function tribe_get_ticket_label_singular_lowercase( $context = '' ) {
		/**
		 * Allows customization of the lowercase singular version of the Ticket label.
		 *
		 * @since 4.10.9
		 *
		 * @param string $label   The lowercase singular version of the Ticket label, defaults to "ticket".
		 * @param string $context The context in which this string is filtered, e.g. 'verb' or 'template.php'.
		 */
		return apply_filters( 'tribe_get_ticket_label_singular_lowercase', _x( 'ticket', 'lowercase singular label for Ticket', 'event-tickets' ), $context );
	}
}

if ( ! function_exists( 'tribe_get_ticket_label_plural' ) ) {

	/**
	 * Get the plural version of the Ticket label. May also be used as a verb.
	 *
	 * @since 4.10.9
	 *
	 * @param string $context Allows passing additional context to this function's filter, e.g. 'verb' or 'template.php'.
	 *
	 * @return string
	 */
	function tribe_get_ticket_label_plural( $context = '' ) {
		/**
		 * Allows customization of the plural version of the Ticket label.
		 *
		 * @since 4.10.9
		 *
		 * @param string $label   The plural version of the Ticket label, defaults to "Tickets".
		 * @param string $context The context in which this string is filtered, e.g. 'verb' or 'template.php'.
		 */
		return apply_filters( 'tribe_get_ticket_label_plural', _x( 'Tickets', 'plural label for Tickets', 'event-tickets' ), $context );
	}
}

if ( ! function_exists( 'tribe_get_ticket_label_plural_lowercase' ) ) {

	/**
	 * Get the lowercase plural version of the Ticket label. May also be used as a verb.
	 *
	 * @since 4.10.9
	 *
	 * @param string $context Allows passing additional context to this function's filter, e.g. 'verb' or 'template.php'.
	 *
	 * @return string
	 */
	function tribe_get_ticket_label_plural_lowercase( $context = '' ) {
		/**
		 * Allows customization of the lowercase plural version of the Ticket label.
		 *
		 * @since 4.10.9
		 *
		 * @param string $label   The lowercase plural version of the Ticket label, defaults to "tickets".
		 * @param string $context The context in which this string is filtered, e.g. 'verb' or 'template.php'.
		 */
		return apply_filters( 'tribe_get_ticket_label_plural_lowercase', _x( 'tickets', 'lowercase plural label for Tickets', 'event-tickets' ), $context );
	}
}

if ( ! function_exists( 'function tribe_tickets_is_event_page' ) ) {
	/**
	 * Allows us to test a post ID to see if it is an event page.
	 *
	 * @since TBD
	 *
	 * @param int|WP_Post|null $post The post (or its ID) we're testing. Default is global post.
	 * @return boolean
	 */
	function tribe_tickets_is_event_page( $post = null ) {
		// Tribe__Events__Main must exist.
		if ( ! class_exists( 'Tribe__Events__Main' ) ) {
			return false;
		}

		// Must be the correct post type.
		if ( Tribe__Events__Main::POSTTYPE !== get_post_type( $post ) ) {
			return false;
		}

		return  true;
	}
}
