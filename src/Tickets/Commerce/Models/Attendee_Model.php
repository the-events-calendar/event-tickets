<?php
/**
 * Models an Tickets Commerce Attendee.
 *
 * @since    TBD
 *
 * @package  TEC\Tickets\Commerce\Models
 */

namespace TEC\Tickets\Commerce\Models;

use DateInterval;
use DatePeriod;
use DateTimeZone;
use TEC\Tickets\Commerce\Module;
use Tribe\Models\Post_Types\Base;
use TEC\Tickets\Commerce\Attendee;
use Tribe\Utils\Lazy_Collection;
use Tribe\Utils\Lazy_String;
use Tribe\Utils\Post_Thumbnail;
use Tribe__Date_Utils as Dates;
use Tribe__Utils__Array as Arr;

/**
 * Class Attendee.
 *
 * @since    TBD
 *
 * @package  TEC\Tickets\Commerce\Models
 */
class Attendee_Model extends Base {
	/**
	 * {@inheritDoc}
	 */
	protected function build_properties( $filter ) {
		try {
			$cache_this = $this->get_caching_callback( $filter );

			$post_id = $this->post->ID;

			$post_meta = get_post_meta( $post_id );

			$ticket_id = Arr::get( $post_meta, [ Attendee::$ticket_relation_meta_key, 0 ] );
			$order_id  = Arr::get( $post_meta, [ Attendee::$order_relation_meta_key, 0 ] );
			$event_id  = Arr::get( $post_meta, [ Attendee::$event_relation_meta_key, 0 ] );
			$user_id   = Arr::get( $post_meta, [ Attendee::$user_relation_meta_key, 0 ] );

			$ticket = tec_tc_get_ticket( $ticket_id );
			$order  = tec_tc_get_order( $order_id );

			$is_product_deleted = empty( $ticket ) && ! $ticket instanceof \WP_Post;

			$checked_in           = Arr::get( $post_meta, [ Attendee::$checked_in_meta_key, 0 ] );
			$security             = Arr::get( $post_meta, [ Attendee::$security_code_meta_key, 0 ] );
			$opt_out              = tribe_is_truthy( Arr::get( $post_meta, [ Attendee::$optout_meta_key, 0 ] ) );
			$status               = Arr::get( $post_meta, [ Attendee::$status_meta_key, 0 ] );
			$ticket_sent          = (int) Arr::get( $post_meta, [ Attendee::$ticket_sent_meta_key, 0 ] );
			$deleted_ticket_title = Arr::get( $post_meta, [ Attendee::$deleted_ticket_meta_key, 0 ] );
			$first_name           = Arr::get( $post_meta, [ Attendee::$first_name_meta_key, 0 ] );
			$last_name            = Arr::get( $post_meta, [ Attendee::$last_name_meta_key, 0 ] );
			$full_name            = $first_name . ' ' . $last_name;
			$email                = Arr::get( $post_meta, [ Attendee::$email_meta_key, 0 ] );
			$price_paid           = Arr::get( $post_meta, [ Attendee::$price_paid_meta_key, 0 ] );
			$currency             = Arr::get( $post_meta, [ Attendee::$currency_meta_key, 0 ] );
			$is_subscribed        = tribe_is_truthy( Arr::get( $post_meta, [ Attendee::$subscribed_meta_key, 0 ] ) );

			// Tries to determine an Attendee Unique ID.
			$ticket_unique_id = Arr::get( $post_meta, [ '_unique_id', 0 ] );
			$ticket_unique_id = empty( $ticket_unique_id ) ? $post_id : $ticket_unique_id;

			$ticket_title = ( $is_product_deleted ? $ticket->post_title : $deleted_ticket_title . ' ' . __( '(deleted)', 'event-tickets' ) );

			$is_purchaser = $email === $order->purchaser_email;

			$properties = [
				'optout'        => $opt_out,
				'ticket'        => $ticket_title,
				'attendee_id'   => $post_id,
				'security'      => $security,
				'product_id'    => $ticket_id,
				'check_in'      => $checked_in,
				'order_status'  => $status,
				'user_id'       => $user_id,
				'ticket_sent'   => $ticket_sent,
				'price_paid'    => $price_paid,
				'currency'      => $currency,

				// Fields for Email Tickets.
				'event_id'      => $event_id,
				'ticket_name'   => $ticket_title,
				'holder_name'   => $full_name,
				'holder_email'  => $email,
				'order_id'      => $order_id,
				'ticket_id'     => $ticket_unique_id,
				'qr_ticket_id'  => $post_id,
				'security_code' => $security,

				// Attendee Meta, should be populated later by ET+
				'attendee_meta' => [],

				// Handle initial Attendee flags.
				'is_subscribed' => $is_subscribed,
				'is_purchaser'  => $is_purchaser,
			];
		} catch ( \Exception $e ) {
			return [];
		}

		return $properties;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_cache_slug() {
		return 'tc_attendees';
	}
}
