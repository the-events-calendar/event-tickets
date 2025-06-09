<?php
/**
 * Event Item object for Square synchronization.
 *
 * This class represents an Event as an Item in Square's catalog. It handles
 * the mapping between a WordPress Event post and its representation in Square.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects
 */

namespace TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects;

use Tribe__Tickets__Ticket_Object as Ticket_Object;
use WP_Post;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Controller as Sync_Controller;
use TEC\Tickets\Commerce\Meta as Commerce_Meta;

/**
 * Class Event_Item
 *
 * Handles the representation of a WordPress Event as a Square catalog item.
 * Events in Square contain tickets as variations.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects
 */
class Event_Item extends Item {
	/**
	 * The type of Square catalog item this class represents.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	protected const ITEM_TYPE = 'ITEM';

	/**
	 * The meta key for the latest object snapshot.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	public const SQUARE_LATEST_OBJECT_SNAPSHOT = '_tec_tickets_commerce_square_latest_object_snapshot_%s';

	// phpcs:disable Squiz.PHP.CommentedOutCode.Found, Squiz.Commenting.InlineComment.InvalidEndChar
	/**
	 * The data structure for the Square catalog item.
	 *
	 * @since 5.24.0
	 *
	 * @var array
	 */
	protected array $data = [
		'event_id'                 => null,
		'type'                     => self::ITEM_TYPE,
		'id'                       => null,
		'is_deleted'               => false,
		'present_at_all_locations' => false,
		'present_at_location_ids'  => [],
		'item_data'                => [
			'name'                 => '', // max 512 characters
			// 'abbreviation'         => '', // max 24 chars only first 5 are visible in POS.
			// 'label_color'          => '',
			// 'is_taxable'           => true,
			// 'tax_ids'              => [],
			'variations'           => [],
			'product_type'         => 'EVENT',
			'skip_modifier_screen' => true,
			// 'categories'           => [],
		],
	];

	// phpcs:enable Squiz.PHP.CommentedOutCode.Found, Squiz.Commenting.InlineComment.InvalidEndChar

	/**
	 * The WordPress event post.
	 *
	 * @since 5.24.0
	 *
	 * @var WP_Post|null
	 */
	protected ?WP_Post $event = null;

	/**
	 * Constructor.
	 *
	 * @since 5.24.0
	 *
	 * @param int   $post_id The event post ID.
	 * @param array $tickets The tickets associated with this event.
	 */
	public function __construct( int $post_id, array $tickets = [] ) {
		$this->data['event_id'] = $post_id;
		$this->event            = get_post( $post_id );

		if ( empty( $tickets ) ) {
			$tickets = Sync_Controller::get_sync_able_tickets_of_event( $post_id );
		}

		$this->set_tickets( $tickets );
		$this->register_hooks();
	}

	/**
	 * Get the WordPress ID of the event.
	 *
	 * @since 5.24.0
	 *
	 * @return int The event post ID.
	 */
	public function get_wp_id(): int {
		return $this->data['event_id'];
	}

	/**
	 * Set the object values for synchronization with Square.
	 *
	 * @since 5.24.0
	 *
	 * @return array The data array prepared for Square synchronization.
	 */
	protected function set_object_values(): array {
		$this->set( 'is_deleted', null === $this->event || $this->event->post_status === 'trash' );
		$this->set_item_data( 'name', $this->event->post_title ? $this->event->post_title : __( 'Untitled Event', 'event-tickets' ) );
		$this->set_description();

		/**
		 * Filters the event item data before it is sent to Square.
		 *
		 * @since 5.24.0
		 *
		 * @param array $data The event item data.
		 * @param WP_Post $event The event post object.
		 *
		 * @return array The filtered event item data.
		 */
		$data = (array) apply_filters( 'tec_tickets_commerce_square_event_item_data', $this->data, $this->event );

		unset( $data['event_id'] );

		return $data;
	}

	/**
	 * Set the tickets as variations of this event item.
	 *
	 * @since 5.24.0
	 *
	 * @param array $tickets An array of Ticket_Object instances to set as variations.
	 *
	 * @return void
	 */
	public function set_tickets( array $tickets ): void {
		$this->data['item_data']['variations'] = array_map(
			static fn( Ticket_Object $ticket ) => new Ticket_Item( $ticket ),
			$tickets
		);
	}

	/**
	 * Set the description for the event item.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	protected function set_description(): void {
		// We are doing what core does in wp_trim_excerpt() - but we target the content instead of the excerpt.
		$text = get_the_content( '', false, $this->event );
		$text = strip_shortcodes( $text );
		$text = excerpt_remove_blocks( $text );
		$text = excerpt_remove_footnotes( $text );

		/*
		 * Temporarily unhook wp_filter_content_tags() since any tags
		 * within the excerpt are stripped out. Modifying the tags here
		 * is wasteful and can lead to bugs in the image counting logic.
		 */
		$filter_image_removed = remove_filter( 'the_content', 'wp_filter_content_tags', 12 );

		/*
		 * Temporarily unhook do_blocks() since excerpt_remove_blocks( $text )
		 * handles block rendering needed for excerpt.
		 */
		$filter_block_removed = remove_filter( 'the_content', 'do_blocks', 9 );

		/** This filter is documented in wp-includes/post-template.php */
		$text = apply_filters( 'the_content', $text );
		$text = str_replace( ']]>', ']]&gt;', $text );

		// Restore the original filter if removed.
		if ( $filter_block_removed ) {
			add_filter( 'the_content', 'do_blocks', 9 );
		}

		/*
		 * Only restore the filter callback if it was removed above. The logic
		 * to unhook and restore only applies on the default priority of 10,
		 * which is generally used for the filter callback in WordPress core.
		 */
		if ( $filter_image_removed ) {
			add_filter( 'the_content', 'wp_filter_content_tags', 12 );
		}

		$text = trim( $text );

		if ( strlen( $text ) <= 65535 ) {
			// We are good to go - no need to trim anything.
			$this->set_item_data( 'description_html', $text ? wpautop( $text ) : get_the_excerpt( $this->event ) );
			return;
		}

		/**
		 * If bigger than the limit of 65535, we need to trim it smartly with WP's `wp_trim_words()` function.
		 *
		 * Average number of letters per word we assume is 6.
		 * For further safety we will use 65635 * 0.8 as the limit.
		 *
		 * So maximum allowed words become => 65535 * 0.8 / 6 = 8736 words.
		 *
		 * Lets make it a round number and use 8700.
		 *
		 * @since 5.24.0
		 *
		 * @param int $max_words The maximum number of words allowed in the description.
		 */
		$max_words = (int) apply_filters( 'tec_tickets_commerce_square_event_item_description_max_words', 8700 );

		$text = wp_trim_words( $text, $max_words, ' [&hellip;]' );

		$this->set_item_data( 'description_html', wpautop( $text ) );
	}

	/**
	 * Handle object sync from Square.
	 *
	 * @since 5.24.0
	 *
	 * @param array $square_object The Square object data.
	 *
	 * @return void
	 */
	public function on_sync_object( array $square_object ): void {
		parent::on_sync_object( $square_object );

		Commerce_Meta::set( $this->get_wp_id(), self::SQUARE_LATEST_OBJECT_SNAPSHOT, md5( wp_json_encode( $this ) ) );
	}

	/**
	 * Delete the remote data for a post.
	 *
	 * @since 5.24.0
	 *
	 * @param int $id The ID.
	 *
	 * @return void
	 */
	public static function delete( int $id ): void {
		parent::delete( $id );
		Commerce_Meta::delete( $id, self::SQUARE_LATEST_OBJECT_SNAPSHOT );
	}

	/**
	 * Whether the object needs to be synced.
	 *
	 * @since 5.24.0
	 *
	 * @return bool Whether the object needs to be synced.
	 */
	public function needs_sync(): bool {
		$latest_snapshot = Commerce_Meta::get( $this->get_wp_id(), self::SQUARE_LATEST_OBJECT_SNAPSHOT );

		if ( ! $latest_snapshot ) {
			return true;
		}

		return $latest_snapshot !== md5( wp_json_encode( $this ) );
	}

	/**
	 * Get the WordPress controlled fields for a given Square object.
	 *
	 * @since 5.24.0
	 *
	 * @param array $square_object The Square object.
	 *
	 * @return array The WordPress controlled fields.
	 */
	public function get_wp_controlled_fields( array $square_object ): array {
		$object = parent::get_wp_controlled_fields( $square_object );
		// Remove ticket data.
		unset( $object['item_data']['variations'] );

		return $object;
	}
}
