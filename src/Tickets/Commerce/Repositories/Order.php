<?php

namespace TEC\Tickets\Commerce\Repositories;

use TEC\Tickets\Commerce\Module;
use \Tribe__Repository;
use TEC\Tickets\Commerce\Order as Order_Manager;
use Tribe__Repository__Usage_Error as Usage_Error;

use Tribe__Utils__Array as Arr;
use Tribe__Date_Utils as Dates;

/**
 * Class Order
 *
 * @since TBD
 */
class Order extends Tribe__Repository {
	/**
	 * The unique fragment that will be used to identify this repository filters.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $filter_name = 'tc_orders';

	/**
	 * Key name to use when limiting lists of keys.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $key_name = \TEC\Tickets\Commerce::ABBR;

	/**
	 * {@inheritdoc}
	 */
	public function __construct() {
		parent::__construct();

		// Set the order post type.
		$this->default_args['post_type'] = Order_Manager::POSTTYPE;
		$this->create_args['post_type']  = Order_Manager::POSTTYPE;

		// @todo Currency needs to be fetched properly.
		$this->create_args['currency'] = 'USD';

		// Add event specific aliases.
		$this->update_fields_aliases = array_merge(
			$this->update_fields_aliases,
			[
				'cart_items'           => Order_Manager::$cart_items_meta_key,
				'total_value'          => Order_Manager::$total_value_meta_key,
				'currency'             => Order_Manager::$currency_meta_key,
				'purchaser_full_name'  => Order_Manager::$purchaser_full_name_meta_key,
				'purchaser_first_name' => Order_Manager::$purchaser_first_name_meta_key,
				'purchaser_last_name'  => Order_Manager::$purchaser_last_name_meta_key,
				'purchaser_email'      => Order_Manager::$purchaser_email_meta_key,
			]
		);
	}


	/**
	 * {@inheritDoc}
	 */
	protected function format_item( $id ) {
		$formatted = null === $this->formatter
			? tec_tc_get_order( $id )
			: $this->formatter->format_item( $id );

		/**
		 * Filters a single formatted order result.
		 *
		 * @since TBD
		 *
		 * @param mixed|\WP_Post                $formatted The formatted event result, usually a post object.
		 * @param int                           $id        The formatted post ID.
		 * @param \Tribe__Repository__Interface $this      The current repository object.
		 */
		$formatted = apply_filters( 'tec_tickets_commerce_repository_order_format', $formatted, $id, $this );

		return $formatted;
	}

	/**
	 * {@inheritdoc}
	 */
	public function filter_postarr_for_create( array $postarr ) {
		if ( isset( $postarr['meta_input'] ) ) {
			$postarr = $this->filter_meta_input( $postarr );
		}

		return parent::filter_postarr_for_create( $postarr );
	}

	/**
	 * {@inheritdoc}
	 */
	public function filter_postarr_for_update( array $postarr, $post_id ) {
		if ( isset( $postarr['meta_input'] ) ) {
			$postarr = $this->filter_meta_input( $postarr, $post_id );
		}

		return parent::filter_postarr_for_update( $postarr, $post_id );
	}

	/**
	 * Filters the tickets data from the input so we can properly save the cart items.
	 *
	 * @since TBD
	 *
	 * @param array    $postarr  Data set that needs filtering.
	 * @param null|int $post_id  When we are dealing with an Update we have an ID here.
	 *
	 * @return array
	 */
	protected function filter_tickets_into_cart_input( $postarr, $post_id = null ) {
		$meta    = Arr::get( $postarr, 'meta_input', [] );
		$tickets = Arr::get( $meta, 'tickets', [] );

		foreach ( $tickets as $ticket_id => $cart_data ) {
			$cart_item = [
				'ticket_id' => $ticket_id,
			];

			if ( is_numeric( $cart_data ) ) {
				$cart_item['qty'] = min( 1, (int) $cart_data );
			} else {
				$cart_item['qty']  = min( 1, (int) Arr::get( $cart_data, 'qty', 1 ) );
				$cart_item['data'] = Arr::get( $cart_data, 'data', [] );
			}
			$cart_items[] = $cart_item;
		}

		if ( ! empty( $cart_items ) ) {
			$postarr['meta_input'][ Order_Manager::$cart_items_meta_key ] = $cart_items;
		}

		unset( $postarr['meta_input']['tickets'] );

		return $postarr;
	}

	/**
	 * Filters the Purchaser data from the input so we can properly save the data.
	 *
	 * @since TBD
	 *
	 * @param array    $postarr  Data set that needs filtering.
	 * @param null|int $post_id  When we are dealing with an Update we have an ID here.
	 *
	 * @return array
	 */
	protected function filter_purchaser_input( $postarr, $post_id = null ) {
		$meta      = Arr::get( $postarr, 'meta_input', [] );
		$purchaser = Arr::get( $meta, 'purchaser', [] );

		if ( is_numeric( $purchaser ) && $user = get_userdata( $purchaser ) ) {
			$full_name  = $user->display_name;
			$first_name = $user->first_name;
			$last_name  = $user->last_name;
			$email      = $user->user_email;
		} else {
			$full_name  = Arr::get( $purchaser, 'full_name' );
			$first_name = Arr::get( $purchaser, 'first_name' );
			$last_name  = Arr::get( $purchaser, 'last_name' );
			$email      = Arr::get( $purchaser, 'email' );
		}

		// Maybe set the first / last name.
		if ( empty( $first_name ) || empty( $last_name ) ) {
			$first_name = $full_name;
			$last_name  = '';

			// Get first name and last name.
			if ( false !== strpos( $full_name, ' ' ) ) {
				$name_parts = explode( ' ', $full_name );

				// First name is first text.
				$first_name = array_shift( $name_parts );

				// Last name is everything the first text.
				$last_name = implode( ' ', $name_parts );
			}
		}

		$postarr['meta_input'][ Order_Manager::$purchaser_email_meta_key ]      = $email;
		$postarr['meta_input'][ Order_Manager::$purchaser_full_name_meta_key ]  = $full_name;
		$postarr['meta_input'][ Order_Manager::$purchaser_first_name_meta_key ] = $first_name;
		$postarr['meta_input'][ Order_Manager::$purchaser_last_name_meta_key ]  = $last_name;

		unset( $postarr['meta_input']['purchaser'] );

		return $postarr;
	}

	/**
	 * Filters and updates the order meta to make sure it makes sense.
	 *
	 * @since TBD
	 *
	 * @param array $postarr The update post array, passed entirely for context purposes.
	 * @param int   $post_id The ID of the event that's being updated.
	 *
	 * @return array The filtered postarr array.
	 */
	protected function filter_meta_input( array $postarr, $post_id = null ) {
		if ( ! empty( $postarr['meta_input']['purchaser'] ) ) {
			$postarr = $this->filter_purchaser_input( $postarr, $post_id );
		}

		if ( ! empty( $postarr['meta_input']['tickets'] ) ) {
			$postarr = $this->filter_tickets_into_cart_input( $postarr, $post_id );
		}

		return $postarr;
	}
}