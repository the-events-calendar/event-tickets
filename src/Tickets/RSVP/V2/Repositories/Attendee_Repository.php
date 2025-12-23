<?php
/**
 * V2 Attendee Repository for TC-RSVP attendees.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2\Repositories
 */

namespace TEC\Tickets\RSVP\V2\Repositories;

use TEC\Tickets\Commerce;
use TEC\Tickets\Commerce\Attendee;
use TEC\Tickets\Repositories\Traits\Get_Field;
use TEC\Tickets\RSVP\Contracts\Attendee_Repository_Interface;
use Tribe__Repository__Query_Filters as Query_Filters;
use WP_Post;
use Tribe__Tickets__Attendee_Repository as Base_Repository;

/**
 * Class Attendee_Repository
 *
 * Repository for querying TC-RSVP attendees.
 * Extends the base repository and provides filters for RSVP-specific queries
 * including going/not-going status.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2\Repositories
 */
class Attendee_Repository extends Base_Repository implements Attendee_Repository_Interface {
	use Get_Field;

	/**
	 * RSVP status meta key.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const RSVP_STATUS_META_KEY = '_tec_tickets_commerce_rsvp_status';

	/**
	 * The unique fragment that will be used to identify this repository filters.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $filter_name = 'tc_rsvp_attendees';

	/**
	 * Key name to use when limiting lists of keys.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $key_name = 'tribe-commerce';

	/**
	 * Constructor.
	 *
	 * @since TBD
	 */
	public function __construct() {
		parent::__construct();

		// Set the default create args.
		$this->create_args['post_type'] = Attendee::POSTTYPE;

		// By default pick Attendees that have the going/not-going status meta key assigned.
		$this->default_args['meta_query'] = [
			'tc-rsvp-type' => [
				'key'     => self::RSVP_STATUS_META_KEY,
				'compare' => 'EXISTS'
			]
		];

		// Some schema entries need to be redirected to the correct meta keys.
		$this->add_simple_meta_schema_entry( 'user', '_tribe_tickets_attendee_user_id', 'meta_in' );
		$this->add_simple_meta_schema_entry( 'user', Attendee::$user_relation_meta_key, 'meta_in' );
		$this->add_simple_meta_schema_entry( 'user__not_in', Attendee::$user_relation_meta_key, 'meta_not_in' );
		$this->add_simple_meta_schema_entry( 'price', Attendee::$price_paid_meta_key );

		/*
		 * The `$this->schema` entry from the base repository is not overridden as it's based on methods
		 * (`filter_by_...`) that use methods to fetch keys and types. Those methods are overridden in
		 * this class.
		 */

		// Override the base repository aliases with the ones specific to Tickets Commerce RSVP.
		$this->update_fields_aliases = array_merge(
			$this->update_fields_aliases,
			[
				'ticket_id'      => Attendee::$ticket_relation_meta_key,
				'event_id'       => Attendee::$event_relation_meta_key,
				'post_id'        => Attendee::$event_relation_meta_key,
				'security_code'  => Attendee::$security_code_meta_key,
				'order_id'       => Attendee::$order_relation_meta_key,
				'optout'         => Attendee::$optout_meta_key,
				'user_id'        => Attendee::$user_relation_meta_key,
				'price_paid'     => Attendee::$price_paid_meta_key,
				'price_currency' => Attendee::$currency_meta_key,
				'full_name'      => Attendee::$full_name_meta_key,
				'email'          => Attendee::$email_meta_key,
				'check_in'       => current( $this->checked_in_keys() ),
				'rsvp_status'    => self::RSVP_STATUS_META_KEY,
			]
		);
	}

	/**
	 * Overrides the original, generic repository method to include only the ones related with RSVP Attendees.
	 *
	 * The function will be called by the original constructor.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function init_order_statuses() {
		if ( ! empty( static::$order_statuses ) ) {
			// Already set up.
			return;
		}

		// For RSVP tickets the order status is the going status
		$statuses                     = [ 'yes', 'no' ];
		self::$order_statuses         = $statuses;
		self::$private_order_statuses = array_diff( $statuses, self::$public_order_statuses );
	}

	/**
	 * Get attendees by email address for privacy operations.
	 *
	 * @since TBD
	 *
	 * @param string $email    The email address to search for.
	 * @param int    $page     The page number (1-indexed).
	 * @param int    $per_page Number of results per page.
	 *
	 * @return array{posts: WP_Post[], has_more: bool}
	 */
	public function get_attendees_by_email( string $email, int $page, int $per_page ): array {
		$posts = $this->by( 'purchaser_email', $email )
						->by( 'meta_exists', self::RSVP_STATUS_META_KEY )
						->per_page( $per_page )
						->page( $page )
						->order_by( 'ID' )
						->order( 'ASC' )
						->all();

		return [
			'posts'    => $posts,
			'has_more' => count( $posts ) >= $per_page,
		];
	}

	/**
	 * Delete an attendee for privacy erasure.
	 *
	 * Uses force delete (bypass trash) to ensure complete removal of personal data
	 * as required for GDPR compliance.
	 *
	 * @since TBD
	 *
	 * @param int $attendee_id The attendee post ID to delete.
	 *
	 * @return array{success: bool, event_id: int|null}
	 */
	public function delete_attendee( int $attendee_id ): array {
		$event_id = get_post_meta( $attendee_id, Attendee::$event_relation_meta_key, true );
		$deleted  = wp_delete_post( $attendee_id, true );

		return [
			'success'  => (bool) $deleted,
			'event_id' => $event_id ? (int) $event_id : null,
		];
	}

	/**
	 * Get the ticket/product ID for an attendee.
	 *
	 * @since TBD
	 *
	 * @param int $attendee_id The attendee post ID.
	 *
	 * @return int The ticket/product ID, or 0 if not found.
	 */
	public function get_ticket_id( int $attendee_id ): int {
		$ticket_id = get_post_meta( $attendee_id, Attendee::$ticket_relation_meta_key, true );

		return $ticket_id ? (int) $ticket_id : 0;
	}

	/**
	 * Overrides the base repository method to return the Tickets Commerce Attendee post type.
	 *
	 * @since TBD
	 *
	 * @return array<string, string> The array of Attendee post types supported by this repository.
	 */
	public function attendee_types() {
		return [ Commerce::PROVIDER => Attendee::POSTTYPE ];
	}

	/**
	 * Overrides the base repository method to return the Tickets Commerce Attendee to Event relation meta key.
	 *
	 * @since TBD
	 *
	 * @return array<string,string> The array of Attendee to Event relation meta keys supported by this repository.
	 */
	public function attendee_to_event_keys() {
		return [ Commerce::PROVIDER => Attendee::$event_relation_meta_key ];
	}

	/**
	 * Overrides the base repository method to return the Tickets Commerce Attendee to Ticket relation meta key.
	 *
	 * @since TBD
	 *
	 * @return array<string,string> The array of Attendee to Ticket relation meta keys supported by this repository.
	 */
	public function attendee_to_ticket_keys() {
		return [ Commerce::PROVIDER => Attendee::$ticket_relation_meta_key ];
	}

	/**
	 * Overrides the base repository method to return the Tickets Commerce Attendee to Order relation meta key.
	 *
	 * @since TBD
	 *
	 * @return array<string,string> The array of Attendee to Order relation meta keys supported by this repository.
	 */
	protected function attendee_to_order_keys() {
		return [ Commerce::PROVIDER => Attendee::$order_relation_meta_key ];
	}

	/**
	 * Overrides the base repository method to return the Tickets Commerce Attendee purchaser name meta key.
	 *
	 * @since TBD
	 *
	 * @return array<string,string> The array of Attendee purchaser name meta keys supported by this repository.
	 */
	public function purchaser_name_keys() {
		return [ Commerce::PROVIDER => Attendee::$full_name_meta_key ];
	}

	/**
	 * Overrides the base repository method to return the Tickets Commerce Attendee purchaser email meta key.
	 *
	 * @since TBD
	 *
	 * @return array<string,string> The array of Attendee purchaser email meta keys supported by this repository.
	 */
	public function purchaser_email_keys() {
		return [ Commerce::PROVIDER => Attendee::$email_meta_key ];
	}

	/**
	 * Overrides the base repository method to return the Tickets Commerce Attendee holder name meta key.
	 *
	 * @since TBD
	 *
	 * @return array<string,string> The array of Attendee holder name meta keys supported by this repository.
	 */
	public function holder_name_keys() {
		return [ Commerce::PROVIDER => Attendee::$full_name_meta_key ];
	}

	/**
	 * Overrides the base repository method to return the Tickets Commerce Attendee holder email meta key.
	 *
	 * @since TBD
	 *
	 * @return array<string,string> The array of Attendee holder email meta keys supported by this repository.
	 */
	public function holder_email_keys() {
		return [ Commerce::PROVIDER => Attendee::$email_meta_key ];
	}

	/**
	 * Overrides the base repository method to return the Tickets Commerce Attendee security code meta key.
	 *
	 * @since TBD
	 *
	 * @return array<string,string> The array of Attendee security code meta keys supported by this repository.
	 */
	public function security_code_keys() {
		return [ Commerce::PROVIDER => Attendee::$security_code_meta_key ];
	}

	/**
	 * Overrides the base repository method to return the Tickets Commerce Attendee optout meta key.
	 *
	 * @since TBD
	 *
	 * @return array<string,string> The array of Attendee optout meta keys supported by this repository.
	 */
	public function attendee_optout_keys() {
		return [ Commerce::PROVIDER => Attendee::$optout_meta_key ];
	}

	/**
	 * Overrides the base repository method to return the Tickets Commerce Attendee checked in meta key.
	 *
	 * @since TBD
	 *
	 * @return array<string,string> The array of Attendee checked in meta keys supported by this repository.
	 */
	public function checked_in_keys() {
		return [ Commerce::PROVIDER => Attendee::$checked_in_meta_key ];
	}

	/**
	 * Overrides the base repository method to add a filter on the RSVP status using the correct meta key.
	 *
	 * @since TBD
	 *
	 * @param string $rsvp_status The RSVP status to filter by.
	 *
	 * @return array<string,mixed> The filtered query arguments.
	 */
	public function filter_by_rsvp_status( $rsvp_status ) {
		return Query_Filters::meta_in(
			self::RSVP_STATUS_META_KEY,
			$rsvp_status,
			'by-rsvp-status'
		);
	}

	/**
	 * Overrides the base repository method to add a filter on the RSVP status using the correct meta key.
	 *
	 * @since TBD
	 *
	 * @param string $rsvp_status The RSVP status to filter by.
	 *
	 * @return array<string,mixed> The filtered query arguments.
	 */
	public function filter_by_rsvp_status_or_none( $rsvp_status ) {
		return Query_Filters::meta_in_or_not_exists(
			self::RSVP_STATUS_META_KEY,
			$rsvp_status,
			'by-rsvp-status-or-none'
		);
	}
}
