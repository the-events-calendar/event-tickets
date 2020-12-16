<?php

use Tribe__Utils__Array as Arr;

/**
 * The ORM/Repository class for Tribe Commerce (PayPal) attendees.
 *
 * @since 4.10.6
 *
 * @property Tribe__Tickets__Commerce__PayPal__Main $attendee_provider
 */
class Tribe__Tickets__Repositories__Attendee__Commerce extends Tribe__Tickets__Attendee_Repository {

	/**
	 * Key name to use when limiting lists of keys.
	 *
	 * @var string
	 */
	protected $key_name = 'tribe-commerce';

	/**
	 * {@inheritdoc}
	 */
	public function __construct() {
		parent::__construct();

		$this->attendee_provider = tribe( 'tickets.commerce.paypal' );

		$this->create_args['post_type'] = $this->attendee_provider->attendee_object;

		// Use a regular variable so we can get constants from it PHP <7.0
		$attendee_provider = $this->attendee_provider;

		// Add object specific aliases.
		$this->update_fields_aliases = array_merge( $this->update_fields_aliases, [
			'ticket_id'       => $attendee_provider::ATTENDEE_PRODUCT_KEY,
			'event_id'        => $attendee_provider::ATTENDEE_EVENT_KEY,
			'post_id'         => $attendee_provider::ATTENDEE_EVENT_KEY,
			'security_code'   => $attendee_provider->security_code,
			'order_id'        => $attendee_provider->order_key,
			'optout'          => $attendee_provider->attendee_optout_key,
			'user_id'         => $attendee_provider->attendee_user_id,
			'price_paid'      => '_paid_price',
			'price_currency'  => '_price_currency_symbol',
			'full_name'       => $attendee_provider->full_name,
			'email'           => $attendee_provider->email,
			'attendee_status' => $attendee_provider->attendee_tpp_key,
			'refund_order_id' => $attendee_provider->refund_order_key,
		] );

		add_filter( 'tribe_tickets_attendee_repository_set_attendee_args_' . $this->key_name, [ $this, 'filter_set_attendee_args' ], 10, 3 );
	}

	/**
	 * {@inheritdoc}
	 */
	public function attendee_types() {
		return $this->limit_list( $this->key_name, parent::attendee_types() );
	}

	/**
	 * {@inheritdoc}
	 */
	public function attendee_to_event_keys() {
		return $this->limit_list( $this->key_name, parent::attendee_to_event_keys() );
	}

	/**
	 * {@inheritdoc}
	 */
	public function attendee_to_ticket_keys() {
		return $this->limit_list( $this->key_name, parent::attendee_to_ticket_keys() );
	}

	/**
	 * {@inheritdoc}
	 */
	public function attendee_to_order_keys() {
		return $this->limit_list( $this->key_name, parent::attendee_to_order_keys() );
	}

	/**
	 * {@inheritdoc}
	 */
	public function purchaser_name_keys() {
		return $this->limit_list( $this->key_name, parent::purchaser_name_keys() );
	}

	/**
	 * {@inheritdoc}
	 */
	public function purchaser_email_keys() {
		return $this->limit_list( $this->key_name, parent::purchaser_email_keys() );
	}

	/**
	 * {@inheritdoc}
	 */
	public function security_code_keys() {
		return $this->limit_list( $this->key_name, parent::security_code_keys() );
	}

	/**
	 * {@inheritdoc}
	 */
	public function attendee_optout_keys() {
		return $this->limit_list( $this->key_name, parent::attendee_optout_keys() );
	}

	/**
	 * {@inheritdoc}
	 */
	public function checked_in_keys() {
		return $this->limit_list( $this->key_name, parent::checked_in_keys() );
	}

	/**
	 * Filter the arguments to set for the attendee for this provider.
	 *
	 * @since TBD
	 *
	 * @param array                         $args          List of arguments to set for the attendee.
	 * @param array                         $attendee_data List of additional attendee data.
	 * @param Tribe__Tickets__Ticket_Object $ticket        The ticket object or null if not relying on it.
	 *
	 * @return array List of arguments to set for the attendee.
	 */
	public function filter_set_attendee_args( $args, $attendee_data, $ticket = null ) {
		// Set default attendee status.
		if ( ! isset( $args['attendee_status'] ) ) {
			$args['attendee_status'] = 'completed';
		}

		// Set default currency symbol.
		if ( ! isset( $args['price_currency'] ) && $ticket ) {
			/** @var Tribe__Tickets__Commerce__Currency $currency */
			$currency        = tribe( 'tickets.commerce.currency' );
			$currency_symbol = $currency->get_currency_symbol( $ticket->ID, true );

			$args['price_currency'] = $currency_symbol;
		}

		return $args;
	}

	/**
	 * Handle backwards compatible actions for Tribe Commerce.
	 *
	 * @since TBD
	 *
	 * @param WP_Post                       $attendee      The attendee object.
	 * @param array                         $attendee_data List of additional attendee data.
	 * @param Tribe__Tickets__Ticket_Object $ticket        The ticket object.
	 */
	public function trigger_create_actions( $attendee, $attendee_data, $ticket ) {
		parent::trigger_create_actions( $attendee, $attendee_data, $ticket );

		$attendee_id           = $attendee->ID;
		$post_id               = Arr::get( $attendee_data, 'post_id' );
		$order_id              = Arr::get( $attendee_data, 'order_id' );
		$product_id            = $ticket->ID;
		$order_attendee_id     = Arr::get( $attendee_data, 'order_attendee_id' );
		$attendee_order_status = $attendee_data['attendee_status'];

		/**
		 * Action fired when an PayPal attendee ticket is created
		 *
		 * @since 4.7
		 *
		 * @param int    $attendee_id           Attendee post ID
		 * @param string $order_id              PayPal Order ID
		 * @param int    $product_id            PayPal ticket post ID
		 * @param int    $order_attendee_id     Attendee number in submitted order
		 * @param string $attendee_order_status The order status for the attendee.
		 */
		do_action( 'event_tickets_tpp_attendee_created', $attendee_id, $order_id, $product_id, $order_attendee_id, $attendee_order_status );

		if ( $post_id ) {
			$global_stock    = new Tribe__Tickets__Global_Stock( $post_id );
			$shared_capacity = false;

			if ( $global_stock->is_enabled() ) {
				$shared_capacity = true;
			}

			switch ( $attendee_order_status ) {
				case Tribe__Tickets__Commerce__PayPal__Stati::$completed:
					$this->attendee_provider->increase_ticket_sales_by( $product_id, 1, $shared_capacity, $global_stock );
					break;
				case Tribe__Tickets__Commerce__PayPal__Stati::$refunded:
					$this->attendee_provider->decrease_ticket_sales_by( $product_id, 1, $shared_capacity, $global_stock );
					break;
				default:
					break;
			}
		}

		/**
		 * Action fired when an PayPal attendee ticket is updated.
		 *
		 * This action will fire both when the attendee is created and
		 * when the attendee is updated.
		 * Hook into the `event_tickets_tpp_attendee_created` action to
		 * only act on the attendee creation.
		 *
		 * @since 4.7
		 *
		 * @param int    $attendee_id           Attendee post ID
		 * @param string $order_id              PayPal Order ID
		 * @param int    $product_id            PayPal ticket post ID
		 * @param int    $order_attendee_id     Attendee number in submitted order
		 * @param string $attendee_order_status The order status for the attendee.
		 */
		do_action( 'event_tickets_tpp_attendee_updated', $attendee_id, $order_id, $product_id, $order_attendee_id, $attendee_order_status );
	}
}
