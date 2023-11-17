<?php
/**
 * Conditional Warnings for the Editor.
 */

namespace Tribe\Tickets\Editor;

use Tribe__Events__Main as TEC;
use WP_Post;
use TEC\Events\Custom_Tables\V1\Migration\State as Migration_State;

/**
 * Warnings handling class.
 *
 * @since 5.0.4
 */
class Warnings {

	/**
	 * Hooks actions for showing warnings
	 *
	 * @since 5.0.4
	 */
	public function hook() {
		add_action( 'tribe_events_tickets_new_ticket_warnings', [ $this, 'show_recurring_event_warning_message' ] );
		add_action( 'tribe_events_tickets_new_ticket_warnings', [ $this, 'add_commerce_provider_warning' ] );
		add_filter( 'tec_tickets_panel_list_helper_text', [ $this, 'filter_tickets_panel_list_helper_text' ], 10, 2 );
		add_action( 'tribe_events_tickets_after_new_ticket_panel', [ $this, 'render_hidden_recurring_warning_for_ticket_meta_box' ] );
	}

	/**
	 * Show the Recurring Event warning message.
	 *
	 * @since 5.6.7 Remove rendering the warning message for CE.
	 * @since 5.6.4 Remove dependency on `#tribe-recurrence-active`.
	 * @since 5.6.2 Added 'recurring_event_warning' as an $additionalClasses.
	 * @since 5.0.4
	 *
	 * @param int $post_id Post ID.
	 */
	public function show_recurring_event_warning_message( $post_id ) {

		if ( ! $this->should_display_recurring_warning_for_tickets( $post_id ) ) {
			return;
		}

		if ( ! function_exists( 'tribe_is_recurring_event' ) ) {
			return;
		}

		if ( \Tribe__Events__Main::POSTTYPE != get_post_type( $post_id ) && ! tribe_is_frontend() ) {
			return;
		}

		if ( class_exists( '\TEC\Events\Custom_Tables\V1\Migration\State' ) ) {
			$migrated = tribe( \TEC\Events\Custom_Tables\V1\Migration\State::class )->is_migrated();

			if ( ! $migrated ) {
				return;
			}
		}

		$this->render_notice( $this->get_recurring_event_warning_message(), 'info', null, null, [ 'recurring_event_warning' ] );
	}

	/**
	 * Add Provider missing warning for tickets.
	 *
	 * @since 5.6.2 Added 'provider_warning' as an $additionalClasses.
	 * @since 5.0.4
	 */
	public function add_commerce_provider_warning() {
		$available_modules = array_diff_key( \Tribe__Tickets__Tickets::modules(), [ 'Tribe__Tickets__RSVP' => true ] );

		if ( count( $available_modules ) > 0 ) {
			return;
		}

		$this->render_notice( $this->get_commerce_provider_missing_warning_message(), 'info', '', '', [ 'provider-warning' ] );
	}

	/**
	 * Get the Commerce Provider missing warning message.
	 *
	 * @since 5.0.4
	 *
	 * @return string The Commerce Provider missing message.
	 */
	public function get_commerce_provider_missing_warning_message() {
		$kb_url = 'https://evnt.is/1ao5';

		/* translators: %1$s: URL for help link, %2$s: Label for help link. */
		$link = sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
			esc_url( $kb_url ),
			esc_html_x( 'Learn More', 'Helper link in Ticket Editor', 'event-tickets' )
		);

		return wp_kses_post(
			sprintf(
				/* translators: %1$s: link to help article. */
				__( 'There is no payment gateway configured. To create tickets, you\'ll need to enable and configure an ecommerce solution. %1$s', 'event-tickets' ),
				$link
			)
		);
	}

	/**
	 * Get the Recurring Event warning message.
	 *
	 * @since 5.0.4
	 * @since TBD Removed `class` attribute, dynamize ticket and rsvp labels.
	 *
	 * @return string The Recurring Event warning message.
	 */
	public function get_recurring_event_warning_message() {
		return sprintf(
			// Translators: %1$s: dynamic "tickets" text, %2$s: dynamic "RSVP" text, %3$s opening tag <a> of link,  %4$s closing tag </a> of link
			__( 'Single %1$s and %2$s are not yet supported on recurring events. %3$s Read about our plans for future features %4$s', 'event-tickets' ),
			tribe_get_ticket_label_plural_lowercase(),
			tribe_get_rsvp_label_plural(),
			'<a href="https://evnt.is/1b7a" target="_blank" rel="noopener noreferrer">',
			'</a>'
		);
	}

	/**
	 * Render the notice block.
	 *
	 * @since 5.6.2 added the `$additionalClasses` attribute to allow customizing the notice.
	 * @since 5.0.4
	 *
	 * @param string $message           The message to show.
	 * @param string $type              Type of message. Default is 'info'.
	 * @param string $depends_on        Dependency selector. Default is empty.
	 * @param string $condition         Dependency condition like 'checked' | 'not-checked' | 'numeric'. Default is empty.
	 * @param array  $additionalClasses Additional CSS classes to add to the notice block. Default is an empty array.
	 */
	public function render_notice( $message, $type = 'info', $depends_on = '', $condition = '', $additionalClasses = [] ) {
		$has_dependency = empty( $depends_on ) ? '' : 'tribe-dependent';
		$condition_attr = empty( $condition ) ? '' : 'data-condition-is-' . $condition;

		$classes = [
			'ticket-editor-notice',
			$type,
			$has_dependency
		];
		$classes = array_merge( $classes, $additionalClasses );

		?>
		<div <?php tribe_classes( $classes ); ?>
			<?php if ( $depends_on ) { ?>
				data-depends="<?php echo esc_attr( $depends_on ); ?>"
			<?php } ?>
			<?php echo esc_attr( $condition_attr ); ?>
		>
			<span class="message"><?php echo wp_kses_post( $message ); ?></span>
		</div>
		<?php
	}

	/**
	 * Check whether the recurring warning should be displayed or not.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The current edited post id.
	 *
	 * @return bool
	 */
	protected function should_display_recurring_warning_for_tickets( int $post_id ): bool {
		if ( ! class_exists( 'Tribe__Events__Pro__Main', false ) || ! class_exists( TEC::class, false ) ) {
			return false;
		}

		if ( ! function_exists( 'tribe_is_recurring_event' ) || ! tribe_is_recurring_event( $post_id ) ) {
			return false;
		}

		if ( TEC::POSTTYPE != get_post_type( $post_id ) && ! tribe_is_frontend() ) {
			return false;
		}

		if ( class_exists( Migration_State::class, false ) ) {
			$migrated = tribe( Migration_State::class )->is_migrated();

			if ( ! $migrated ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Filter tickets panel helper text to inject recurring ticket warning.
	 *
	 * @since TBD
	 *
	 * @param string $text The helper text.
	 * @param WP_Post $post The Post Object.
	 *
	 * @return string The helper text.
	 */
	public function filter_tickets_panel_list_helper_text( string $text, WP_Post $post ): string {
		if ( ! $this->should_display_recurring_warning_for_tickets( $post->ID ) ) {
			return $text;
		}

		return $this->get_recurring_event_warning_message();
	}

	/**
	 * Render hidden recurring warning message for new post/event creation page.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The current post ID.
	 *
	 * @return void
	 */
	public function render_hidden_recurring_warning_for_ticket_meta_box( int $post_id ): void {
		// Only render when recurring is available and for events post-type.
		if ( ! function_exists( 'tribe_is_recurring_event' )
			 || TEC::POSTTYPE !== get_post_type( $post_id )
			 || tribe_is_recurring_event( $post_id )
		) {
			return;
		}

		$html  = '<p class="tec_ticket-panel__hidden-recurring-warning" style="display: none">';
		$html .= $this->get_recurring_event_warning_message();
		$html .= '<p>';

		echo $html;
	}
}
