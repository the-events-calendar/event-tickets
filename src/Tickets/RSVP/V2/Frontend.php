<?php
/**
 * Frontend delegate for RSVP V2.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */

namespace TEC\Tickets\RSVP\V2;

use TEC\Tickets\Commerce\Module;
use Tribe__Tickets__Editor__Template as Tickets_Editor_Template;
use Tribe__Tickets__RSVP as RSVP_V1_Tickets_Handler;
use Tribe__Tickets__Tickets as Tickets_Handler;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use TEC\Tickets\Commerce\Attendee;
use TEC\Tickets\Commerce\Ticket;
use WP_Post;

/**
 * Class Frontend
 *
 * Handles frontend rendering and assets for RSVP V2.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */
class Frontend {
	/**
	 * A reference to the Tickets Commerce module instance.
	 *
	 * @since TBD
	 *
	 * @var Module
	 */
	private Module $module;

	/**
	 * Frontend constructor.
	 *
	 * @since TBD
	 *
	 * @param Module $module A reference to the Tickets Commerce module instance.
	 */
	public function __construct( Module $module ) {
		$this->module = $module;
	}

	/**
	 * Render V2 RSVP template for TC-RSVP tickets on the frontend.
	 *
	 * Hooks into `tec_tickets_front_end_rsvp_form_template_content` to render
	 * the V2 commerce RSVP template instead of the generic RSVP block template.
	 *
	 * @since TBD
	 *
	 * @param string                  $content  The template content to be rendered.
	 * @param array<string,mixed>     $args     The RSVP block arguments.
	 * @param Tickets_Editor_Template $template The template object.
	 * @param WP_Post                 $post     The post object.
	 * @param bool                    $should_echo Whether to echo the output.
	 *
	 * @return string The modified HTML or original if not TC-RSVP.
	 */
	public function render_rsvp_template(
		string $content,
		array $args,
		Tickets_Editor_Template $template,
		WP_Post $post,
		bool $should_echo
	): string {
		$active_rsvps = $args['active_rsvps'] ?? [];

		// Find the first TC-RSVP ticket in the active RSVPs.
		$rsvp = null;
		foreach ( $active_rsvps as $ticket ) {
			if ( $ticket->type() === Constants::TC_RSVP_TYPE ) {
				$rsvp = $ticket;
				break;
			}
		}

		// Only process if we have a TC-RSVP ticket.
		if ( $rsvp === null ) {
			return $content;
		}

		$rsvp_template_args = [
			'rsvp'          => $rsvp,
			'post_id'       => $post->ID,
			'block_html_id' => Constants::TC_RSVP_TYPE . uniqid( '', true ),
			'step'          => '',
			'active_rsvps'  => $rsvp->date_in_range() ? [ $rsvp ] : [],
			'must_login'    => ! is_user_logged_in() && $this->login_required(),
		];

		$content .= $template->template( 'v2/commerce/rsvp', $rsvp_template_args, $should_echo );

		return $content;
	}

	/**
	 * Enqueue RSVP assets on the frontend.
	 *
	 * Assets are only enqueued when viewing a single post/event that has TC-RSVP tickets.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function enqueue_rsvp_assets(): void {
		// Only enqueue on singular posts.
		if ( ! is_singular() ) {
			return;
		}

		$post_id = get_the_ID();

		// Only enqueue if the post has TC-RSVP tickets.
		if ( ! $this->post_has_tc_rsvp_tickets( $post_id ) ) {
			return;
		}

		// Enqueue the asset group.
		tribe_asset_enqueue_group( 'tec-tickets-commerce-rsvp' );
	}

	/**
	 * Removes the RSVP hooks that would render the RSVP v1 form on the frontend.
	 *
	 * The original code hooks as part of the construction, to avoid having to update all the existing code
	 * unhook the RSVP v1 hooks right after they are added.
	 *
	 * @since TBD
	 *
	 * @param Tickets_Handler $tickets_handler  The tickets handler instance.
	 * @param string          $ticket_form_hook The ticket form hook.
	 *
	 * @return void
	 */
	public function do_not_display_rsvp_v1_tickets_form( Tickets_Handler $tickets_handler, string $ticket_form_hook ): void {
		if ( ! $tickets_handler instanceof RSVP_V1_Tickets_Handler ) {
			return;
		}

		remove_action( $ticket_form_hook, [ $tickets_handler, 'maybe_add_front_end_tickets_form' ], 5 );
		remove_filter( $ticket_form_hook, [ $tickets_handler, 'show_tickets_unavailable_message' ], 6 );
		remove_filter( 'the_content', [ $tickets_handler, 'front_end_tickets_form_in_content' ], 11 );
		remove_filter( 'the_content', [ $tickets_handler, 'show_tickets_unavailable_message_in_content' ], 12 );
	}

	/**
	 * Update the RSVP values for this user.
	 *
	 * Note that, within this method, $attendee_id refers to the attendee or ticket ID
	 * (it does not refer to an "order" in the sense of a transaction that may include
	 * multiple tickets, as is the case in some other methods for instance).
	 *
	 * @param array $attendee_data Information that we are trying to save.
	 * @param int   $attendee_id   The attendee ID.
	 */
	public function update_attendee_data( $attendee_data, $attendee_id ) {
		if ( empty( $attendee_data['order_status'] ) ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			return;
		}

		$user_id = get_current_user_id();

		$attendee = tribe( Attendee::class )->load_attendee_data( get_post( $attendee_id ) );

		if ( ! $attendee instanceof WP_Post ) {
			return;
		}

		$ticket_id = $attendee->product_id;

		/** @var Ticket $ticket_data */
		$ticket_data = tribe( Ticket::class );
		$ticket      = $ticket_data->load_ticket_object( $ticket_id );

		if ( ! $ticket instanceof Ticket_Object ) {
			return;
		}

		if ( $ticket->type() !== Constants::TC_RSVP_TYPE ) {
			return;
		}

		$order = tec_tc_get_order( $attendee->post_parent );

		if ( ! $order instanceof WP_Post ) {
			return;
		}

		$order_user_id = $order->purchaser['user_id'] ?? 0;

		if ( $order_user_id !== $user_id ) {
			return;
		}

		$attendee_status = 'going' === $attendee_data['order_status'] ? 'yes' : 'no';

		$current_status = metadata_exists( 'post', $attendee_id, Constants::RSVP_STATUS_META_KEY ) ? get_post_meta( $attendee_id, Constants::RSVP_STATUS_META_KEY, true ) : 'yes';

		if ( tribe_is_truthy( $current_status ) === tribe_is_truthy( $attendee_status ) ) {
			return;
		}

		update_post_meta( $attendee_id, Constants::RSVP_STATUS_META_KEY, $attendee_status );
	}

	/**
	 * Render the RSVP ticket status on the My Tickets page.
	 *
	 * Hooked to `tec_tickets_my_tickets_ticket_information_after_ticket_name`.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $attendee The attendee data.
	 */
	public function render_my_tickets_ticket_status( array $attendee ): void {
		if ( empty( $attendee['ticket_type'] ) || Constants::TC_RSVP_TYPE !== $attendee['ticket_type'] ) {
			return;
		}

		$ticket_id         = (int) ( $attendee['product_id'] ?? 0 );
		$attendee_is_going = metadata_exists( 'post', $attendee['ID'], Constants::RSVP_STATUS_META_KEY )
			? tribe_is_truthy( get_post_meta( $attendee['ID'], Constants::RSVP_STATUS_META_KEY, true ) )
			: true;
		$show_not_going    = false;

		if ( $ticket_id ) {
			$show_not_going = tribe_is_truthy( get_post_meta( $ticket_id, Constants::SHOW_NOT_GOING_META_KEY, true ) );
		}

		tribe( 'tickets.editor.template' )->template(
			'v2/commerce/rsvp/my-tickets/ticket-status',
			[
				'attendee_is_going' => $attendee_is_going,
				'show_not_going'    => $show_not_going,
				'attendee_id'       => $attendee['ID'],
			]
		);
	}

	/**
	 * Check if a post has TC-RSVP tickets.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post ID to check.
	 *
	 * @return bool True if the post has TC-RSVP tickets, false otherwise.
	 */
	private function post_has_tc_rsvp_tickets( int $post_id ): bool {
		$tickets = $this->module->get_tickets( $post_id );

		foreach ( $tickets as $ticket ) {
			$ticket_type = get_post_meta( $ticket->ID, '_type', true );

			if ( Constants::TC_RSVP_TYPE === $ticket_type ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns whether the RSVP form requires login.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the RSVP form requires login.
	 */
	private function login_required(): bool {
		$requirements = (array) tribe_get_option( 'ticket-authentication-requirements', [] );

		return in_array( 'event-tickets_rsvp', $requirements, true );
	}
}
