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
	 * Prevents the rendering of some RSVP templates in the context of the RSVP v2 implementation.
	 *
	 * @since TBD
	 *
	 * @param string|null     $done Whether the template has been rendered or not.
	 * @param string|string[] $name The template name in the form of a string or an array of strings.
	 *
	 * @return string|null An empty string to prevent template rendering if required, or the original value.
	 */
	public function prevent_template_render( $done, $name ) {
		if ( null !== $done ) {
			return $done;
		}

		$do_not_render = [
			'v2/commerce/rsvp/attendees',
			'v2/commerce/rsvp/attendees/attendee',
			'v2/commerce/rsvp/attendees/attendee/name',
			'v2/commerce/rsvp/attendees/attendee/rsvp',
			'v2/commerce/rsvp/attendees/title',
		];

		if ( in_array( $name, $do_not_render, true ) ) {
			// Return a non-null value to indicate the template was done.
			return '';
		}

		return $done;
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
